<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Instalador Web do Sistema de Ponto Eletrônico
 *
 * Este controller gerencia todo o processo de instalação:
 * 1. Verificação de requisitos
 * 2. Configuração do banco de dados (com teste de conexão)
 * 3. Criação do arquivo .env
 * 4. Execução das migrations
 * 5. Inserção de dados iniciais
 * 6. Finalização
 *
 * @package App\Controllers
 * @author Support Solo Sondagens
 * @version 1.0.0
 */
class InstallController extends Controller
{
    protected $session;
    protected $installLockFile;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->session = \Config\Services::session();
        $this->installLockFile = WRITEPATH . 'installed.lock';

        // Se já instalado, redirecionar para home
        if (file_exists($this->installLockFile) && $request->getUri()->getSegment(2) !== 'force-reinstall') {
            return redirect()->to('/')->send();
        }
    }

    /**
     * Página inicial do instalador
     */
    public function index()
    {
        return view('install/welcome');
    }

    /**
     * Etapa 1: Verificação de Requisitos do Sistema
     */
    public function requirements()
    {
        $requirements = $this->checkRequirements();

        return view('install/requirements', [
            'requirements' => $requirements,
            'canProceed' => $requirements['canProceed']
        ]);
    }

    /**
     * Etapa 2: Configuração do Banco de Dados
     */
    public function database()
    {
        return view('install/database');
    }

    /**
     * AJAX: Testar Conexão com Banco de Dados
     * Esta é a função crítica que valida antes de prosseguir
     */
    public function testDatabaseConnection()
    {
        $response = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            $host = $this->request->getPost('db_host');
            $port = $this->request->getPost('db_port') ?: '3306';
            $database = $this->request->getPost('db_database');
            $username = $this->request->getPost('db_username');
            $password = $this->request->getPost('db_password');

            // Validar campos obrigatórios
            if (empty($host) || empty($database) || empty($username)) {
                $response['message'] = 'Preencha todos os campos obrigatórios.';
                return $this->response->setJSON($response);
            }

            // Tentar conexão
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";

            $response['details'][] = "Tentando conectar em {$host}:{$port}...";

            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            $response['details'][] = "✅ Conexão com MySQL estabelecida!";

            // Verificar versão do MySQL
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            $response['details'][] = "Versão do MySQL: {$version}";

            // Verificar se o banco de dados existe
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
            $dbExists = $stmt->rowCount() > 0;

            if (!$dbExists) {
                // Tentar criar o banco de dados
                $response['details'][] = "Banco de dados '{$database}' não existe. Tentando criar...";

                $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                $response['details'][] = "✅ Banco de dados '{$database}' criado com sucesso!";
            } else {
                $response['details'][] = "✅ Banco de dados '{$database}' já existe.";
            }

            // Conectar ao banco específico para verificar permissões
            $pdo = new \PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);

            // Testar permissões CREATE TABLE
            $testTable = '_install_test_' . time();
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$testTable}` (id INT)");
            $pdo->exec("DROP TABLE IF EXISTS `{$testTable}`");

            $response['details'][] = "✅ Permissões de CREATE/DROP validadas.";

            // Testar permissões INSERT/SELECT
            $pdo->exec("CREATE TEMPORARY TABLE _temp_test (id INT)");
            $pdo->exec("INSERT INTO _temp_test VALUES (1)");
            $result = $pdo->query("SELECT * FROM _temp_test")->fetch();

            $response['details'][] = "✅ Permissões de INSERT/SELECT validadas.";

            // Verificar configurações importantes
            $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
            $maxConn = $stmt->fetch(\PDO::FETCH_ASSOC);
            $response['details'][] = "Max Connections: {$maxConn['Value']}";

            // Salvar configurações na sessão
            $this->session->set('install_db_config', [
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password
            ]);

            $response['success'] = true;
            $response['message'] = '✅ Conexão testada com sucesso! Todas as permissões validadas.';

        } catch (\PDOException $e) {
            $response['message'] = '❌ Erro de conexão: ' . $e->getMessage();

            // Adicionar dicas baseadas no erro
            $errorCode = $e->getCode();

            if ($errorCode == 1045) {
                $response['details'][] = "⚠️ Usuário ou senha incorretos.";
                $response['details'][] = "Verifique as credenciais do MySQL.";
            } elseif ($errorCode == 2002) {
                $response['details'][] = "⚠️ Não foi possível conectar ao servidor MySQL.";
                $response['details'][] = "Verifique se o MySQL está rodando: sudo systemctl status mysql";
                $response['details'][] = "Verifique o host e porta.";
            } elseif ($errorCode == 1044 || $errorCode == 1142) {
                $response['details'][] = "⚠️ Usuário sem permissões suficientes.";
                $response['details'][] = "Execute: GRANT ALL PRIVILEGES ON {$database}.* TO '{$username}'@'%';";
            } else {
                $response['details'][] = "Código do erro: {$errorCode}";
            }

        } catch (\Exception $e) {
            $response['message'] = '❌ Erro inesperado: ' . $e->getMessage();
        }

        return $this->response->setJSON($response);
    }

    /**
     * Etapa 3: Salvar Configuração e Criar .env
     */
    public function saveConfiguration()
    {
        $dbConfig = $this->session->get('install_db_config');

        if (!$dbConfig) {
            return redirect()->to('/install/database')->with('error', 'Configuração do banco não encontrada. Teste a conexão novamente.');
        }

        try {
            // Gerar encryption key
            $encryptionKey = 'base64:' . base64_encode(random_bytes(32));

            // Criar conteúdo do .env
            $envContent = $this->generateEnvFile($dbConfig, $encryptionKey);

            // Salvar arquivo .env
            $envPath = ROOTPATH . '.env';

            if (file_put_contents($envPath, $envContent) === false) {
                throw new \Exception('Não foi possível criar o arquivo .env. Verifique as permissões.');
            }

            // Salvar na sessão que configuração foi salva
            $this->session->set('install_config_saved', true);

            return redirect()->to('/install/migrations')->with('success', 'Configuração salva com sucesso!');

        } catch (\Exception $e) {
            return redirect()->to('/install/database')->with('error', 'Erro ao salvar configuração: ' . $e->getMessage());
        }
    }

    /**
     * Etapa 4: Executar Migrations
     */
    public function migrations()
    {
        if (!$this->session->get('install_config_saved')) {
            return redirect()->to('/install/database')->with('error', 'Configure o banco de dados primeiro.');
        }

        return view('install/migrations');
    }

    /**
     * AJAX: Executar Migrations
     */
    public function runMigrations()
    {
        $response = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            // Recarregar configurações do .env
            $this->reloadEnvironment();

            $response['details'][] = "Iniciando execução das migrations...";

            // Conectar ao banco
            $db = \Config\Database::connect();
            $db->query('SELECT 1'); // Testar conexão

            $response['details'][] = "✅ Conexão com banco estabelecida.";

            // Listar arquivos de migration
            $migrationsPath = APPPATH . 'Database/Migrations/';
            $migrationFiles = glob($migrationsPath . '*.php');

            $response['details'][] = "Encontradas " . count($migrationFiles) . " migrations.";

            // Executar migrations usando CodeIgniter
            $migrate = \Config\Services::migrations();

            try {
                $migrate->latest();
                $response['details'][] = "✅ Todas as migrations executadas com sucesso!";

                // Verificar tabelas criadas
                $tables = $db->listTables();
                $response['details'][] = "Tabelas criadas: " . implode(', ', $tables);

                $this->session->set('install_migrations_done', true);

                $response['success'] = true;
                $response['message'] = '✅ Estrutura do banco de dados criada com sucesso!';

            } catch (\Exception $e) {
                throw new \Exception('Erro ao executar migrations: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            $response['message'] = '❌ Erro: ' . $e->getMessage();
            $response['details'][] = "Verifique os logs em writable/logs/";
        }

        return $this->response->setJSON($response);
    }

    /**
     * Etapa 5: Inserir Dados Iniciais
     */
    public function seedData()
    {
        if (!$this->session->get('install_migrations_done')) {
            return redirect()->to('/install/migrations')->with('error', 'Execute as migrations primeiro.');
        }

        return view('install/seed');
    }

    /**
     * AJAX: Inserir Dados Iniciais
     */
    public function runSeeder()
    {
        $response = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            $this->reloadEnvironment();
            $db = \Config\Database::connect();

            $response['details'][] = "Iniciando inserção de dados iniciais...";

            // Criar usuário administrador
            $adminData = [
                'name' => $this->request->getPost('admin_name') ?: 'Administrador',
                'email' => $this->request->getPost('admin_email') ?: 'admin@teste.com',
                'password' => password_hash($this->request->getPost('admin_password') ?: 'Admin@123456', PASSWORD_BCRYPT, ['cost' => 12]),
                'role' => 'admin',
                'cpf' => '00000000000',
                'admission_date' => date('Y-m-d'),
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->table('employees')->insert($adminData);
            $response['details'][] = "✅ Usuário administrador criado: {$adminData['email']}";

            // Criar alguns dados de exemplo se solicitado
            if ($this->request->getPost('include_sample_data') === 'yes') {
                // Gestor
                $db->table('employees')->insert([
                    'name' => 'Gestor Teste',
                    'email' => 'gestor@teste.com',
                    'password' => password_hash('Gestor@123456', PASSWORD_BCRYPT, ['cost' => 12]),
                    'role' => 'gestor',
                    'cpf' => '11111111111',
                    'admission_date' => date('Y-m-d'),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // Funcionário
                $db->table('employees')->insert([
                    'name' => 'Funcionário Teste',
                    'email' => 'funcionario@teste.com',
                    'password' => password_hash('Func@123456', PASSWORD_BCRYPT, ['cost' => 12]),
                    'role' => 'funcionario',
                    'cpf' => '22222222222',
                    'admission_date' => date('Y-m-d'),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $response['details'][] = "✅ Dados de exemplo criados.";
            }

            $this->session->set('install_seed_done', true);

            $response['success'] = true;
            $response['message'] = '✅ Dados iniciais inseridos com sucesso!';

        } catch (\Exception $e) {
            $response['message'] = '❌ Erro: ' . $e->getMessage();
        }

        return $this->response->setJSON($response);
    }

    /**
     * Etapa 6: Finalização
     */
    public function finish()
    {
        if (!$this->session->get('install_seed_done')) {
            return redirect()->to('/install/seed')->with('error', 'Insira os dados iniciais primeiro.');
        }

        // Criar arquivo de lock para impedir reinstalação
        $lockContent = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'installer_version' => '2.0.0'
        ];

        file_put_contents($this->installLockFile, json_encode($lockContent, JSON_PRETTY_PRINT));

        // Limpar dados da sessão
        $this->session->remove('install_db_config');
        $this->session->remove('install_config_saved');
        $this->session->remove('install_migrations_done');
        $this->session->remove('install_seed_done');

        return view('install/finish');
    }

    /**
     * Forçar reinstalação (apenas em desenvolvimento)
     */
    public function forceReinstall()
    {
        if (ENVIRONMENT === 'production') {
            die('Reinstalação não permitida em produção.');
        }

        if (file_exists($this->installLockFile)) {
            unlink($this->installLockFile);
        }

        $this->session->remove('install_db_config');
        $this->session->remove('install_config_saved');
        $this->session->remove('install_migrations_done');
        $this->session->remove('install_seed_done');

        return redirect()->to('/install');
    }

    /**
     * Verificar requisitos do sistema
     */
    private function checkRequirements(): array
    {
        $requirements = [
            'php_version' => [
                'name' => 'Versão do PHP',
                'required' => '8.1.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
            ],
            'extensions' => []
        ];

        $requiredExtensions = [
            'intl' => 'Internacionalização',
            'mbstring' => 'Multibyte String',
            'json' => 'JSON',
            'mysqli' => 'MySQL Improved',
            'pdo_mysql' => 'PDO MySQL',
            'openssl' => 'OpenSSL',
            'curl' => 'cURL',
            'gd' => 'GD (Imagens)',
            'zip' => 'ZIP'
        ];

        foreach ($requiredExtensions as $ext => $desc) {
            $requirements['extensions'][$ext] = [
                'name' => $desc,
                'status' => extension_loaded($ext)
            ];
        }

        // Verificar permissões de escrita
        $requirements['writable'] = [
            'writable' => [
                'name' => 'Diretório writable/',
                'status' => is_writable(WRITEPATH)
            ],
            'env' => [
                'name' => 'Arquivo .env (criar)',
                'status' => is_writable(ROOTPATH)
            ]
        ];

        // Determinar se pode prosseguir
        $canProceed = $requirements['php_version']['status'];

        foreach ($requirements['extensions'] as $ext) {
            if (!$ext['status']) {
                $canProceed = false;
                break;
            }
        }

        foreach ($requirements['writable'] as $perm) {
            if (!$perm['status']) {
                $canProceed = false;
                break;
            }
        }

        $requirements['canProceed'] = $canProceed;

        return $requirements;
    }

    /**
     * Gerar conteúdo do arquivo .env
     */
    private function generateEnvFile(array $dbConfig, string $encryptionKey): string
    {
        $template = <<<ENV
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = 'http://localhost/'
app.forceGlobalSecureRequests = false
app.CSPEnabled = true

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = {$dbConfig['host']}
database.default.database = {$dbConfig['database']}
database.default.username = {$dbConfig['username']}
database.default.password = {$dbConfig['password']}
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = {$dbConfig['port']}

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------

encryption.key = {$encryptionKey}

#--------------------------------------------------------------------
# SESSION
#--------------------------------------------------------------------

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'ci_session'
session.expiration = 7200
session.savePath = writable/session
session.matchIP = true
session.timeToUpdate = 300
session.regenerateDestroy = true

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------

security.csrfProtection = 'session'
security.tokenRandomize = true
security.tokenName = 'csrf_token_name'
security.headerName = 'X-CSRF-TOKEN'
security.cookieName = 'csrf_cookie_name'
security.expires = 7200
security.regenerate = true

#--------------------------------------------------------------------
# COOKIE
#--------------------------------------------------------------------

cookie.prefix = ''
cookie.expires = 0
cookie.path = '/'
cookie.domain = ''
cookie.secure = false
cookie.httponly = true
cookie.samesite = 'Lax'

ENV;

        return $template;
    }

    /**
     * Recarregar variáveis de ambiente do .env
     */
    private function reloadEnvironment(): void
    {
        $envPath = ROOTPATH . '.env';

        if (!file_exists($envPath)) {
            throw new \Exception('Arquivo .env não encontrado.');
        }

        // Ler arquivo .env
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsear linha
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remover aspas
                $value = trim($value, '"\'');

                // Definir no ambiente
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
