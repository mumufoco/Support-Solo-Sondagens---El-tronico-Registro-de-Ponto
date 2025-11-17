<?php
/**
 * Diagnóstico de Sessão - Instalador
 *
 * Use este arquivo para diagnosticar problemas de sessão no instalador
 * Acesse: http://seudominio.com/test-session-installer.php
 */

// Configure session to use writable/session directory
$sessionPath = __DIR__ . '/../writable/session';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
if (is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

session_start();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Sessão - Instalador</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-top: 0; }
        .status { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .ok { background: #d5f4e6; color: #27ae60; border-left: 4px solid #27ae60; }
        .error { background: #fadbd8; color: #e74c3c; border-left: 4px solid #e74c3c; }
        .warning { background: #fef5e7; color: #f39c12; border-left: 4px solid #f39c12; }
        .info { background: #e8f5ff; color: #3498db; border-left: 4px solid #3498db; }
        code { background: #ecf0f1; padding: 3px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        table th { background: #ecf0f1; font-weight: bold; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico de Sessão - Instalador</h1>
        <p>Este teste verifica se as sessões PHP estão funcionando corretamente para o instalador.</p>

        <?php
        // Test 1: Session Working
        if (!isset($_SESSION['test_counter'])) {
            $_SESSION['test_counter'] = 0;
        }
        $_SESSION['test_counter']++;

        $sessionWorking = $_SESSION['test_counter'] > 0;

        echo '<div class="status ' . ($sessionWorking ? 'ok' : 'error') . '">';
        echo '<strong>Teste 1: Sessão PHP</strong><br>';
        if ($sessionWorking) {
            echo '✅ FUNCIONANDO - Contador: ' . $_SESSION['test_counter'] . '<br>';
            echo 'As sessões estão salvando dados corretamente.';
        } else {
            echo '❌ FALHOU - As sessões não estão funcionando!<br>';
            echo 'O instalador NÃO funcionará até este problema ser resolvido.';
        }
        echo '</div>';

        // Test 2: Session ID
        $sessionId = session_id();
        echo '<div class="status info">';
        echo '<strong>Teste 2: Session ID</strong><br>';
        echo 'Session ID: <code>' . htmlspecialchars($sessionId) . '</code><br>';
        echo 'Session Name: <code>' . session_name() . '</code>';
        echo '</div>';

        // Test 3: Cookie Support
        $cookiesEnabled = isset($_COOKIE[session_name()]);
        echo '<div class="status ' . ($cookiesEnabled ? 'ok' : 'error') . '">';
        echo '<strong>Teste 3: Cookies do Navegador</strong><br>';
        if ($cookiesEnabled) {
            echo '✅ FUNCIONANDO - O navegador está aceitando cookies<br>';
            echo 'Cookie de sessão: <code>' . htmlspecialchars($_COOKIE[session_name()]) . '</code>';
        } else {
            echo '⚠️ AVISO - Cookie de sessão não encontrado<br>';
            echo 'Se esta é a primeira visita, recarregue a página. Se o problema persistir, os cookies estão bloqueados.';
        }
        echo '</div>';

        // Test 4: Session Save Path
        $savePath = session_save_path();
        $savePathWritable = is_writable($savePath);
        echo '<div class="status ' . ($savePathWritable ? 'ok' : 'error') . '">';
        echo '<strong>Teste 4: Diretório de Sessão</strong><br>';
        echo 'Caminho: <code>' . htmlspecialchars($savePath) . '</code><br>';
        if ($savePathWritable) {
            echo '✅ GRAVÁVEL - O diretório tem permissão de escrita';
        } else {
            echo '❌ NÃO GRAVÁVEL - Sem permissão de escrita!<br>';
            echo '<strong>Solução:</strong> Execute <code>chmod -R 775 ' . htmlspecialchars($savePath) . '</code>';
        }
        echo '</div>';

        // Test 5: Installer Session Data
        echo '<div class="status info">';
        echo '<strong>Teste 5: Dados do Instalador na Sessão</strong><br>';

        $installerKeys = ['db_host', 'db_name', 'db_user', 'db_tested', 'admin_email', 'installation_complete'];
        $foundData = false;

        echo '<table>';
        echo '<tr><th>Chave</th><th>Valor</th><th>Status</th></tr>';
        foreach ($installerKeys as $key) {
            $value = $_SESSION[$key] ?? null;
            $exists = isset($_SESSION[$key]);
            if ($exists) $foundData = true;

            echo '<tr>';
            echo '<td><code>' . $key . '</code></td>';
            echo '<td>' . ($exists ? htmlspecialchars(is_bool($value) ? ($value ? 'true' : 'false') : $value) : '<em>não definido</em>') . '</td>';
            echo '<td>' . ($exists ? '✓' : '-') . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if (!$foundData) {
            echo '<p><em>Nenhum dado do instalador encontrado na sessão (normal se você ainda não iniciou a instalação).</em></p>';
        }
        echo '</div>';

        // Configuration Info
        echo '<h2>Configuração do PHP</h2>';
        echo '<table>';
        echo '<tr><th>Configuração</th><th>Valor</th></tr>';
        echo '<tr><td>session.save_handler</td><td><code>' . ini_get('session.save_handler') . '</code></td></tr>';
        echo '<tr><td>session.cookie_lifetime</td><td><code>' . ini_get('session.cookie_lifetime') . '</code></td></tr>';
        echo '<tr><td>session.cookie_secure</td><td><code>' . (ini_get('session.cookie_secure') ? 'On' : 'Off') . '</code></td></tr>';
        echo '<tr><td>session.cookie_httponly</td><td><code>' . (ini_get('session.cookie_httponly') ? 'On' : 'Off') . '</code></td></tr>';
        echo '<tr><td>session.use_cookies</td><td><code>' . (ini_get('session.use_cookies') ? 'On' : 'Off') . '</code></td></tr>';
        echo '<tr><td>session.gc_maxlifetime</td><td><code>' . ini_get('session.gc_maxlifetime') . ' segundos</code></td></tr>';
        echo '</table>';

        // Overall Status
        $allOk = $sessionWorking && $savePathWritable;
        echo '<div class="status ' . ($allOk ? 'ok' : 'error') . '" style="margin-top: 30px; font-size: 16px;">';
        echo '<strong>DIAGNÓSTICO FINAL:</strong><br>';
        if ($allOk) {
            echo '✅ <strong>TUDO OK!</strong> As sessões estão funcionando corretamente.<br>';
            echo 'Você pode prosseguir com a instalação.';
        } else {
            echo '❌ <strong>PROBLEMAS DETECTADOS!</strong> Corrija os erros acima antes de instalar.<br><br>';
            echo '<strong>Soluções Comuns:</strong><br>';
            echo '1. Habilite cookies no navegador<br>';
            echo '2. Verifique permissões: <code>chmod -R 775 writable/session</code><br>';
            echo '3. Verifique se o diretório existe: <code>mkdir -p writable/session</code><br>';
            echo '4. Limpe o cache do navegador e tente novamente';
        }
        echo '</div>';

        // Action Buttons
        echo '<div style="margin-top: 30px; text-align: center;">';
        echo '<a href="test-session-installer.php" class="btn">🔄 Recarregar Teste</a>';
        echo '<a href="test-session-installer.php?clear=1" class="btn btn-danger">🗑️ Limpar Sessão</a>';
        if ($allOk) {
            echo '<a href="install.php" class="btn btn-success">→ Ir para Instalador</a>';
        }
        echo '</div>';

        // Clear session if requested
        if (isset($_GET['clear'])) {
            session_destroy();
            echo '<script>window.location.href="test-session-installer.php";</script>';
        }
        ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1; color: #7f8c8d; font-size: 12px;">
            <strong>Sobre este diagnóstico:</strong><br>
            Este script testa se o PHP consegue criar e manter sessões, que são essenciais para o instalador funcionar.
            Se algum teste falhar, o instalador mostrará a mensagem "Configure o banco de dados primeiro!" mesmo após configurar corretamente.
        </div>
    </div>
</body>
</html>
