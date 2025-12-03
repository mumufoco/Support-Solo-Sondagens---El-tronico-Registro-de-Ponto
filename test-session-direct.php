<?php
/**
 * Teste DIRETO de sessão - simula o fluxo completo de login
 * sem precisar de servidor HTTP
 */

// Definir ambiente ANTES de qualquer output
putenv('CI_ENVIRONMENT=development');
$_SERVER['CI_ENVIRONMENT'] = 'development';

// Configurar sessão ANTES de qualquer output
if (session_status() === PHP_SESSION_NONE) {
    session_name('ci_session');

    $sessionPath = __DIR__ . '/writable/session';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
}

// Carregar CodeIgniter ANTES de qualquer output
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

require_once __DIR__ . '/vendor/autoload.php';

// Boot CodeIgniter
require_once __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();

require $paths->systemDirectory . '/bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

// AGORA sim podemos fazer output
echo "====================================================================\n";
echo "  TESTE DIRETO DE SESSÃO - SIMULANDO FLUXO DE LOGIN\n";
echo "====================================================================\n\n";

echo "📋 STEP 1: Sessão configurada\n";
echo "  ✓ session_name: " . session_name() . "\n";
echo "  ✓ session_save_path: " . session_save_path() . "\n";
echo "\n";

echo "📋 STEP 2: CodeIgniter inicializado\n";

echo "  ✓ CodeIgniter inicializado\n";
echo "\n";

// ====================================================================
// SIMULAR LOGIN
// ====================================================================

echo "📋 STEP 3: Simulando processo de LOGIN...\n";

// Carregar serviços necessários
$session = \Config\Services::session();
$request = \Config\Services::request();

echo "  ✓ Session service carregado\n";
echo "  ✓ Session ID inicial: " . session_id() . "\n";
echo "  ✓ Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "\n";

// Simular dados de login
echo "📋 STEP 4: Carregando usuário mockado...\n";

$userModel = new \App\Models\UserModel();
$user = $userModel->findByEmail('admin@test.com');

if (!$user) {
    echo "  ❌ ERRO: Usuário não encontrado!\n";
    exit(1);
}

echo "  ✓ Usuário encontrado: {$user->email}\n";
echo "  ✓ Role: {$user->role}\n";
echo "\n";

// Verificar senha
echo "📋 STEP 5: Verificando senha...\n";

$passwordCorrect = password_verify('admin123', $user->password);

if (!$passwordCorrect) {
    echo "  ❌ ERRO: Senha incorreta!\n";
    exit(1);
}

echo "  ✓ Senha correta!\n";
echo "\n";

// Setar dados na sessão (EXATAMENTE como o LoginController faz)
echo "📋 STEP 6: Setando dados na sessão...\n";

$sessionData = [
    'user_id' => $user->id,
    'user_name' => $user->name,
    'user_email' => $user->email,
    'user_role' => $user->role,
    'user_active' => $user->active,
    'logged_in' => true,
    'last_activity' => time()
];

// SETAR NA SESSÃO
$session->set($sessionData);

echo "  ✓ Dados setados na sessão\n";
echo "  → Session ID após set: " . session_id() . "\n";
echo "\n";

// Verificar IMEDIATAMENTE se foi gravado
echo "📋 STEP 7: Verificando se dados foram gravados...\n";

$verificar = [
    'user_id' => $session->get('user_id'),
    'user_role' => $session->get('user_role'),
    'logged_in' => $session->get('logged_in')
];

echo "  → user_id na sessão: " . ($verificar['user_id'] ?? 'NULL') . "\n";
echo "  → user_role na sessão: " . ($verificar['user_role'] ?? 'NULL') . "\n";
echo "  → logged_in na sessão: " . ($verificar['logged_in'] ? 'TRUE' : 'FALSE/NULL') . "\n";

if ($verificar['user_id'] && $verificar['user_role']) {
    echo "  ✅ DADOS GRAVADOS CORRETAMENTE!\n";
} else {
    echo "  ❌ DADOS NÃO FORAM GRAVADOS!\n";
}

echo "\n";

// Verificar arquivos de sessão
echo "📋 STEP 8: Verificando arquivos de sessão no disco...\n";

$sessionFiles = glob(__DIR__ . '/writable/session/ci_session*');
echo "  → Arquivos de sessão encontrados: " . count($sessionFiles) . "\n";

if (count($sessionFiles) > 0) {
    $latestFile = $sessionFiles[0];
    echo "  → Arquivo mais recente: " . basename($latestFile) . "\n";

    $content = file_get_contents($latestFile);
    echo "  → Tamanho: " . strlen($content) . " bytes\n";
    echo "  → Conteúdo (primeiros 200 chars):\n";
    echo "    " . substr($content, 0, 200) . "...\n";

    // Procurar por user_id no conteúdo
    if (strpos($content, 'user_id') !== false) {
        echo "  ✅ 'user_id' ENCONTRADO no arquivo de sessão!\n";
    } else {
        echo "  ❌ 'user_id' NÃO ENCONTRADO no arquivo de sessão!\n";
    }
} else {
    echo "  ❌ NENHUM arquivo de sessão criado!\n";
}

echo "\n";

// ====================================================================
// SIMULAR REDIRECT E NOVA REQUISIÇÃO (como o AuthFilter)
// ====================================================================

echo "📋 STEP 9: Simulando nova requisição (como após redirect)...\n";
echo "  (Isto simula o que acontece quando o usuário é redirecionado para /dashboard)\n";
echo "\n";

// DESTRUIR o objeto session atual (simula nova requisição)
unset($session);

// CRIAR NOVA INSTÂNCIA da sessão (como acontece na nova requisição)
$session2 = \Config\Services::session();

echo "  ✓ Nova instância de sessão criada\n";
echo "  → Session ID: " . session_id() . "\n";
echo "\n";

// Tentar LER os dados (como o AuthFilter faz)
echo "📋 STEP 10: Lendo dados na NOVA requisição (AuthFilter)...\n";

$userId = $session2->get('user_id');
$userRole = $session2->get('user_role');
$loggedIn = $session2->get('logged_in');

echo "  → user_id lido: " . ($userId ?? 'NULL') . "\n";
echo "  → user_role lido: " . ($userRole ?? 'NULL') . "\n";
echo "  → logged_in lido: " . ($loggedIn ? 'TRUE' : 'FALSE/NULL') . "\n";
echo "\n";

// ====================================================================
// RESULTADO FINAL
// ====================================================================

echo "====================================================================\n";
echo "  RESULTADO DO TESTE\n";
echo "====================================================================\n\n";

if ($userId && $userRole && $loggedIn) {
    echo "✅ SUCESSO: Sessão persistiu corretamente!\n";
    echo "   → Login funcionaria sem loop\n";
    echo "   → AuthFilter veria o usuário logado\n";
    echo "   → Redirecionaria para dashboard\n";
} else {
    echo "❌ FALHA: Sessão NÃO persistiu!\n";
    echo "   → Este é o problema do LOOP!\n";
    echo "   → AuthFilter não vê usuário logado\n";
    echo "   → Redireciona de volta para /login\n";
    echo "\n";
    echo "🔍 ANÁLISE:\n";

    if ($userId === null) {
        echo "   → user_id está NULL na segunda leitura\n";
        echo "   → Sessão foi gravada mas não foi lida corretamente\n";
        echo "   → Possível problema: session handler, cookie, ou regenerate\n";
    }
}

echo "\n";

// Mostrar configuração de sessão do CodeIgniter
echo "📋 CONFIGURAÇÃO DE SESSÃO DO CODEIGNITER:\n";
$sessionConfig = config('Session');
echo "  → Driver: " . $sessionConfig->driver . "\n";
echo "  → CookieName: " . $sessionConfig->cookieName . "\n";
echo "  → Expiration: " . $sessionConfig->expiration . "\n";
echo "  → SavePath: " . $sessionConfig->savePath . "\n";
echo "  → MatchIP: " . ($sessionConfig->matchIP ? 'true' : 'false') . "\n";
echo "  → TimeToUpdate: " . $sessionConfig->timeToUpdate . "\n";
echo "  → RegenerateDestroy: " . ($sessionConfig->regenerateDestroy ? 'true' : 'false') . "\n";
echo "\n";
