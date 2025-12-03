<?php
// Teste simples de sessão PHP nativo + CodeIgniter Session
// Sem inicializar framework completo

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "TESTE 1: PHP Session Nativo\n";
echo str_repeat("=", 50) . "\n";

// Configurar sessão
session_name('ci_session');
$sessionPath = __DIR__ . '/writable/session';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0755, true);
session_save_path($sessionPath);

echo "session_name: " . session_name() . "\n";
echo "session_save_path: " . session_save_path() . "\n";

session_start();
$sid1 = session_id();
echo "Session ID: $sid1\n";

// Setar dados
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['logged_in'] = true;

echo "Dados setados: user_id=1, user_role=admin\n";

// Verificar imediatamente
echo "Leitura imediata: user_id=" . ($_SESSION['user_id'] ?? 'NULL') . "\n";

// Salvar
session_write_close();
echo "Sessão salva\n";

// Verificar arquivo
$files = glob($sessionPath . '/ci_session*');
echo "Arquivos de sessão: " . count($files) . "\n";
if (count($files) > 0) {
    $content = file_get_contents($files[0]);
    echo "Conteúdo tem user_id: " . (strpos($content, 'user_id') !== false ? 'SIM' : 'NÃO') . "\n";
}

echo "\n";

// Simular nova requisição
echo "TESTE 2: Nova Requisição (Simular Redirect)\n";
echo str_repeat("=", 50) . "\n";

session_start();
$sid2 = session_id();
echo "Session ID: $sid2\n";
echo "IDs iguais: " . ($sid1 === $sid2 ? 'SIM' : 'NÃO') . "\n";

// Ler dados
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;
$loggedIn = $_SESSION['logged_in'] ?? null;

echo "user_id lido: " . ($userId ?? 'NULL') . "\n";
echo "user_role lido: " . ($userRole ?? 'NULL') . "\n";
echo "logged_in lido: " . ($loggedIn ? 'TRUE' : 'NULL') . "\n";

if ($userId === 1 && $userRole === 'admin' && $loggedIn === true) {
    echo "\n✅ SESSÃO PERSISTIU CORRETAMENTE!\n";
} else {
    echo "\n❌ SESSÃO NÃO PERSISTIU!\n";
    echo "PROBLEMA: Dados não foram lidos na segunda requisição\n";
}

session_write_close();
