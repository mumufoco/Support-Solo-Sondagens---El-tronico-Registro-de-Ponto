<?php
/**
 * INSTALADOR AUTOMÁTICO - SISTEMA DE PONTO ELETRÔNICO
 * 
 * Este instalador configura automaticamente:
 * - Verificação de requisitos do sistema
 * - Criação do banco de dados
 * - Importação da estrutura de tabelas
 * - Geração do arquivo .env
 * - Criação do primeiro usuário administrador
 * - Configuração de permissões
 * 
 * SEGURANÇA: Este arquivo deve ser REMOVIDO após a instalação!
 * 
 * @version 2.0.0
 * @author Sistema de Ponto Eletrônico
 */

// Previne execução após instalação completada
if (file_exists('../.env') && filesize('../.env') > 100) {
    die('
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Instalação já Concluída</title>
        <style>
            body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; text-align: center; }
            h1 { color: #667eea; margin-bottom: 20px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; text-align: left; }
            .btn { background: #dc3545; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Instalação já Concluída</h1>
            <p>O sistema já foi instalado anteriormente.</p>
            <div class="warning">
                <strong>⚠️ ATENÇÃO DE SEGURANÇA:</strong><br>
                Por motivos de segurança, você deve <strong>DELETAR</strong> o arquivo <code>install.php</code> imediatamente.
                <br><br>
                Execute: <code>rm public/install.php</code>
            </div>
            <a href="/" class="btn">Ir para Login</a>
        </div>
    </body>
    </html>
    ');
}

// Inicia sessão para mensagens
session_start();

// Configurações
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Função para gerar senha segura
function generateSecurePassword($length = 20) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*-_+=';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

// Função para verificar requisitos do sistema
function checkRequirements() {
    $requirements = [
        'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'Extension: MySQLi' => extension_loaded('mysqli'),
        'Extension: JSON' => extension_loaded('json'),
        'Extension: MBString' => extension_loaded('mbstring'),
        'Extension: OpenSSL' => extension_loaded('openssl'),
        'Extension: GD' => extension_loaded('gd'),
        'Extension: cURL' => extension_loaded('curl'),
        'Extension: Intl' => extension_loaded('intl'),
        'Writable: /writable' => is_writable(ROOT_PATH . DS . 'writable'),
        'Writable: /writable/session' => is_writable(ROOT_PATH . DS . 'writable' . DS . 'session'),
        'Writable: /writable/logs' => is_writable(ROOT_PATH . DS . 'writable' . DS . 'logs'),
        'Writable: /writable/cache' => is_writable(ROOT_PATH . DS . 'writable' . DS . 'cache'),
        'Writable: /storage' => is_writable(ROOT_PATH . DS . 'storage'),
    ];
    
    return $requirements;
}

// Processa o formulário
$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // STEP 2: Configuração do Banco de Dados
    if ($step == 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $dbCreate = isset($_POST['db_create']);
        
        if (empty($dbName) || empty($dbUser)) {
            $errors[] = 'Nome do banco de dados e usuário são obrigatórios.';
        } else {
            // Tenta conectar ao MySQL
            $conn = @new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
            
            if ($conn->connect_error) {
                $errors[] = 'Erro ao conectar ao MySQL: ' . $conn->connect_error;
            } else {
                // Verifica se o banco existe
                $result = $conn->query("SHOW DATABASES LIKE '$dbName'");
                $dbExists = $result->num_rows > 0;
                
                if (!$dbExists && $dbCreate) {
                    // Cria o banco de dados
                    if ($conn->query("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                        $success[] = "Banco de dados '$dbName' criado com sucesso!";
                    } else {
                        $errors[] = 'Erro ao criar banco: ' . $conn->error;
                    }
                } elseif (!$dbExists) {
                    $errors[] = "Banco de dados '$dbName' não existe. Marque a opção para criar automaticamente.";
                }
                
                // Se tudo ok, salva na sessão
                if (empty($errors)) {
                    $_SESSION['db_config'] = [
                        'host' => $dbHost,
                        'port' => $dbPort,
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass,
                    ];
                    
                    // Importa o database.sql
                    $conn->select_db($dbName);
                    $sqlFile = PUBLIC_PATH . DS . 'database.sql';
                    
                    if (file_exists($sqlFile)) {
                        $sql = file_get_contents($sqlFile);
                        
                        // Remove comentários e divide em queries
                        $sql = preg_replace('/^-- .*$/m', '', $sql);
                        $sql = preg_replace('/^\/\*.*?\*\//ms', '', $sql);
                        
                        // Executa query por query
                        $conn->multi_query($sql);
                        
                        // Aguarda todas as queries terminarem
                        do {
                            if ($result = $conn->store_result()) {
                                $result->free();
                            }
                        } while ($conn->next_result());
                        
                        $success[] = 'Estrutura do banco de dados importada com sucesso!';
                        
                        // Vai para próximo passo
                        header('Location: install.php?step=3');
                        exit;
                    } else {
                        $errors[] = 'Arquivo database.sql não encontrado em /public/';
                    }
                }
                
                $conn->close();
            }
        }
    }
    
    // STEP 3: Configuração da Aplicação
    if ($step == 3) {
        $appUrl = trim($_POST['app_url'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $companyCnpj = trim($_POST['company_cnpj'] ?? '');
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = trim($_POST['admin_password'] ?? '');
        $adminCpf = trim($_POST['admin_cpf'] ?? '');
        
        if (empty($appUrl) || empty($companyName) || empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
            $errors[] = 'Todos os campos obrigatórios devem ser preenchidos.';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail do administrador inválido.';
        } elseif (strlen($adminPassword) < 8) {
            $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
        } else {
            // Gera chave de criptografia
            $encryptionKey = base64_encode(random_bytes(32));
            
            // Cria o arquivo .env
            $envContent = <<<ENV
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = '$appUrl'
app.indexPage = ''
app.forceGlobalSecureRequests = true

# Encryption key (32 bytes for XChaCha20-Poly1305 AEAD)
encryption.key = base64:$encryptionKey

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = {$_SESSION['db_config']['host']}
database.default.database = {$_SESSION['db_config']['name']}
database.default.username = {$_SESSION['db_config']['user']}
database.default.password = {$_SESSION['db_config']['pass']}
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = {$_SESSION['db_config']['port']}

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------

security.csrfProtection = 'session'
security.tokenRandomize = true
security.tokenName = 'csrf_token'

#--------------------------------------------------------------------
# SESSION
#--------------------------------------------------------------------

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'ci_session'
session.expiration = 7200
session.savePath = writable/session
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = false

#--------------------------------------------------------------------
# COMPANY SETTINGS
#--------------------------------------------------------------------

company.name = '$companyName'
company.cnpj = '$companyCnpj'
ENV;

            // Salva o .env
            if (file_put_contents(ROOT_PATH . DS . '.env', $envContent)) {
                $success[] = 'Arquivo .env criado com sucesso!';
                
                // Conecta ao banco para criar o admin
                $dbConfig = $_SESSION['db_config'];
                $conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name'], $dbConfig['port']);
                
                if (!$conn->connect_error) {
                    // Hash da senha usando Argon2ID
                    $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
                    
                    // Gera código único
                    $uniqueCode = strtoupper(substr(md5(uniqid()), 0, 6));
                    
                    // Insere o administrador
                    $stmt = $conn->prepare("INSERT INTO employees (name, email, password, cpf, unique_code, role, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'admin', 1, NOW(), NOW())");
                    $stmt->bind_param('sssss', $adminName, $adminEmail, $passwordHash, $adminCpf, $uniqueCode);
                    
                    if ($stmt->execute()) {
                        $success[] = 'Usuário administrador criado com sucesso!';
                        $_SESSION['admin_code'] = $uniqueCode;
                        $_SESSION['admin_email'] = $adminEmail;
                        
                        // Vai para passo final
                        header('Location: install.php?step=4');
                        exit;
                    } else {
                        $errors[] = 'Erro ao criar administrador: ' . $stmt->error;
                    }
                    
                    $stmt->close();
                    $conn->close();
                } else {
                    $errors[] = 'Erro ao conectar ao banco: ' . $conn->connect_error;
                }
            } else {
                $errors[] = 'Erro ao criar arquivo .env. Verifique as permissões do diretório.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Ponto Eletrônico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 30px; 
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .steps { 
            display: flex; 
            justify-content: space-around; 
            padding: 20px; 
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .step { 
            text-align: center; 
            flex: 1; 
            position: relative;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            width: 80%;
            height: 2px;
            background: #dee2e6;
            z-index: 0;
        }
        .step:last-child::after { display: none; }
        .step-number { 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            background: #dee2e6; 
            color: #6c757d; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold; 
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        .step.active .step-number { background: #667eea; color: white; }
        .step.completed .step-number { background: #28a745; color: white; }
        .step-label { font-size: 12px; color: #6c757d; }
        .content { padding: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 600; 
            color: #333;
        }
        .form-group label .required { color: #dc3545; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ced4da; 
            border-radius: 5px; 
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group small { color: #6c757d; font-size: 12px; }
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            border-left: 4px solid;
        }
        .alert-danger { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .alert-success { background: #d4edda; border-color: #28a745; color: #155724; }
        .alert-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .alert ul { margin: 10px 0 0 20px; }
        .requirements { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            gap: 10px; 
            margin: 20px 0;
        }
        .requirement { 
            padding: 10px; 
            border-radius: 5px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .requirement.pass { background: #d4edda; color: #155724; }
        .requirement.fail { background: #f8d7da; color: #721c24; }
        .requirement .icon { font-size: 20px; }
        .btn { 
            padding: 12px 30px; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .footer { 
            padding: 20px; 
            background: #f8f9fa; 
            text-align: center; 
            color: #6c757d; 
            font-size: 14px;
        }
        .success-box { 
            text-align: center; 
            padding: 40px;
        }
        .success-box .icon { 
            font-size: 80px; 
            color: #28a745; 
            margin-bottom: 20px;
        }
        .success-box h2 { 
            color: #28a745; 
            margin-bottom: 20px;
        }
        .credential-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 20px 0; 
            text-align: left;
        }
        .credential-box strong { 
            color: #667eea; 
            font-size: 18px;
        }
        .security-warning { 
            background: #fff3cd; 
            border-left: 4px solid #ffc107; 
            padding: 15px; 
            margin: 20px 0;
        }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { 
            .two-columns { grid-template-columns: 1fr; }
            .steps { flex-direction: column; }
            .step::after { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🕐 Sistema de Ponto Eletrônico</h1>
            <p>Instalador Automático v2.0</p>
        </div>
        
        <div class="steps">
            <div class="step <?= $step >= 1 ? ($step == 1 ? 'active' : 'completed') : '' ?>">
                <div class="step-number">1</div>
                <div class="step-label">Requisitos</div>
            </div>
            <div class="step <?= $step >= 2 ? ($step == 2 ? 'active' : 'completed') : '' ?>">
                <div class="step-number">2</div>
                <div class="step-label">Banco de Dados</div>
            </div>
            <div class="step <?= $step >= 3 ? ($step == 3 ? 'active' : 'completed') : '' ?>">
                <div class="step-number">3</div>
                <div class="step-label">Configuração</div>
            </div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                <div class="step-number">4</div>
                <div class="step-label">Concluído</div>
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>⚠️ Erros Encontrados:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <strong>✅ Sucesso:</strong>
                    <ul>
                        <?php foreach ($success as $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- STEP 1: Verificação de Requisitos -->
                <h2>1️⃣ Verificação de Requisitos do Sistema</h2>
                <p>Verificando se o servidor atende aos requisitos mínimos...</p>
                
                <div class="requirements">
                    <?php 
                    $requirements = checkRequirements(); 
                    $allPassed = !in_array(false, $requirements);
                    foreach ($requirements as $req => $passed): 
                    ?>
                        <div class="requirement <?= $passed ? 'pass' : 'fail' ?>">
                            <span><?= $req ?></span>
                            <span class="icon"><?= $passed ? '✅' : '❌' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($allPassed): ?>
                    <div class="alert alert-success">
                        <strong>✅ Todos os requisitos foram atendidos!</strong><br>
                        Você pode prosseguir com a instalação.
                    </div>
                    <a href="install.php?step=2" class="btn btn-primary">Próximo: Configurar Banco de Dados →</a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>❌ Alguns requisitos não foram atendidos.</strong><br>
                        Corrija os problemas antes de continuar.
                    </div>
                <?php endif; ?>
                
            <?php elseif ($step == 2): ?>
                <!-- STEP 2: Configuração do Banco de Dados -->
                <h2>2️⃣ Configuração do Banco de Dados</h2>
                <p>Configure as credenciais do MySQL/MariaDB.</p>
                
                <form method="POST" action="install.php?step=2">
                    <div class="two-columns">
                        <div class="form-group">
                            <label>Host do Banco <span class="required">*</span></label>
                            <input type="text" name="db_host" value="<?= $_POST['db_host'] ?? 'localhost' ?>" required>
                            <small>Geralmente "localhost" ou "127.0.0.1"</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Porta do Banco <span class="required">*</span></label>
                            <input type="text" name="db_port" value="<?= $_POST['db_port'] ?? '3306' ?>" required>
                            <small>Porta padrão do MySQL: 3306</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nome do Banco de Dados <span class="required">*</span></label>
                        <input type="text" name="db_name" value="<?= $_POST['db_name'] ?? 'ponto_eletronico' ?>" required>
                        <small>Nome do banco que será criado/usado</small>
                    </div>
                    
                    <div class="two-columns">
                        <div class="form-group">
                            <label>Usuário do Banco <span class="required">*</span></label>
                            <input type="text" name="db_user" value="<?= $_POST['db_user'] ?? 'root' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Senha do Banco</label>
                            <input type="password" name="db_pass" value="<?= $_POST['db_pass'] ?? '' ?>">
                            <small>Deixe em branco se não houver senha</small>
                        </div>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="db_create" id="db_create" checked>
                        <label for="db_create">Criar banco de dados automaticamente se não existir</label>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>⚠️ Importante:</strong> O instalador irá importar automaticamente a estrutura completa do banco de dados (tabelas, índices, etc).
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Próximo: Importar Banco de Dados →</button>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- STEP 3: Configuração da Aplicação -->
                <h2>3️⃣ Configuração da Aplicação</h2>
                <p>Configure as informações da empresa e crie o primeiro usuário administrador.</p>
                
                <form method="POST" action="install.php?step=3">
                    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #667eea;">🌐 Configurações do Sistema</h3>
                    
                    <div class="form-group">
                        <label>URL da Aplicação <span class="required">*</span></label>
                        <input type="url" name="app_url" value="<?= $_POST['app_url'] ?? 'https://' . $_SERVER['HTTP_HOST'] ?>" required>
                        <small>URL completa onde o sistema será acessado (com https://)</small>
                    </div>
                    
                    <div class="two-columns">
                        <div class="form-group">
                            <label>Nome da Empresa <span class="required">*</span></label>
                            <input type="text" name="company_name" value="<?= $_POST['company_name'] ?? '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>CNPJ da Empresa</label>
                            <input type="text" name="company_cnpj" value="<?= $_POST['company_cnpj'] ?? '' ?>" placeholder="00.000.000/0001-00">
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #667eea;">👤 Primeiro Administrador</h3>
                    
                    <div class="two-columns">
                        <div class="form-group">
                            <label>Nome Completo <span class="required">*</span></label>
                            <input type="text" name="admin_name" value="<?= $_POST['admin_name'] ?? '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>CPF do Administrador</label>
                            <input type="text" name="admin_cpf" value="<?= $_POST['admin_cpf'] ?? '' ?>" placeholder="000.000.000-00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>E-mail de Login <span class="required">*</span></label>
                        <input type="email" name="admin_email" value="<?= $_POST['admin_email'] ?? '' ?>" required>
                        <small>Este será o e-mail usado para fazer login no sistema</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Senha de Acesso <span class="required">*</span></label>
                        <input type="password" name="admin_password" value="<?= $_POST['admin_password'] ?? '' ?>" required minlength="8">
                        <small>Mínimo de 8 caracteres. Use uma senha forte!</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>🔐 Segurança:</strong> A senha será criptografada usando Argon2ID, o algoritmo mais seguro disponível. Um código único será gerado automaticamente para o administrador.
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Finalizar Instalação →</button>
                </form>
                
            <?php elseif ($step == 4): ?>
                <!-- STEP 4: Conclusão -->
                <div class="success-box">
                    <div class="icon">🎉</div>
                    <h2>Instalação Concluída com Sucesso!</h2>
                    <p>O Sistema de Ponto Eletrônico está pronto para uso.</p>
                    
                    <div class="credential-box">
                        <h3 style="color: #667eea; margin-bottom: 15px;">📋 Credenciais de Acesso</h3>
                        <p><strong>E-mail:</strong> <?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?></p>
                        <p><strong>Código Único:</strong> <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 5px; font-size: 20px; font-weight: bold;"><?= $_SESSION['admin_code'] ?? '' ?></span></p>
                        <p style="margin-top: 10px;"><small>⚠️ Anote estas credenciais em local seguro!</small></p>
                    </div>
                    
                    <div class="security-warning">
                        <h3 style="color: #856404; margin-bottom: 10px;">🔒 ATENÇÃO DE SEGURANÇA</h3>
                        <p><strong>Por motivos de segurança, você DEVE deletar o arquivo install.php IMEDIATAMENTE!</strong></p>
                        <p style="margin-top: 10px;">Execute o seguinte comando:</p>
                        <code style="background: #fff; padding: 10px; display: block; margin-top: 10px; border-radius: 5px;">rm <?= PUBLIC_PATH ?>/install.php</code>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <h3 style="color: #667eea; margin-bottom: 15px;">📝 Próximos Passos</h3>
                        <ol style="text-align: left; max-width: 500px; margin: 0 auto;">
                            <li>Deletar o arquivo install.php</li>
                            <li>Configurar o e-mail em .env (para notificações)</li>
                            <li>Configurar o cron para backups automáticos</li>
                            <li>Acessar o sistema e personalizar as configurações</li>
                            <li>Cadastrar os funcionários</li>
                        </ol>
                    </div>
                    
                    <a href="/" class="btn btn-success" style="margin-top: 30px; font-size: 18px;">Acessar o Sistema →</a>
                </div>
                
                <?php 
                // Limpa a sessão
                session_destroy();
                ?>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            Sistema de Ponto Eletrônico v2.0 | Conforme Portaria MTE 671/2021
        </div>
    </div>
</body>
</html>
