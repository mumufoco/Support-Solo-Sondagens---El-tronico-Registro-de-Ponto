<?php
/**
 * Session Debug Tool - Diagnóstico de Sessão
 *
 * Este script testa se as sessões estão funcionando corretamente
 * e identifica problemas de persistência.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug de Sessão</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #2d3748; }
        .success { background: #f0fff4; border-left: 4px solid #38a169; padding: 15px; margin: 10px 0; color: #22543d; }
        .error { background: #fff5f5; border-left: 4px solid #e53e3e; padding: 15px; margin: 10px 0; color: #742a2a; }
        .info { background: #ebf8ff; border-left: 4px solid #4299e1; padding: 15px; margin: 10px 0; color: #2c5282; }
        code { background: #edf2f7; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn { background: #4299e1; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        pre { background: #2d3748; color: #48bb78; padding: 15px; border-radius: 6px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug de Sessão</h1>

        <?php
        // Start session
        session_start();

        // Test 1: Session ID
        echo "<h2>1. Session ID</h2>";
        $sessionId = session_id();
        if ($sessionId) {
            echo "<div class='success'>✅ Session ID: <code>{$sessionId}</code></div>";
        } else {
            echo "<div class='error'>❌ Session ID não foi gerado!</div>";
        }

        // Test 2: Set and Get
        echo "<h2>2. Teste de Set/Get</h2>";

        if (!isset($_SESSION['test_counter'])) {
            $_SESSION['test_counter'] = 1;
            $_SESSION['test_time'] = time();
        } else {
            $_SESSION['test_counter']++;
        }

        echo "<div class='info'>";
        echo "Contador de recarregamentos: <strong>" . $_SESSION['test_counter'] . "</strong><br>";
        echo "Primeira visita: " . date('H:i:s', $_SESSION['test_time']) . "<br>";
        echo "Visita atual: " . date('H:i:s') . "<br>";
        echo "</div>";

        if ($_SESSION['test_counter'] > 1) {
            echo "<div class='success'>✅ Sessão está persistindo entre recarregamentos!</div>";
        } else {
            echo "<div class='info'>ℹ️ Primeira visita. Recarregue a página para testar persistência.</div>";
        }

        // Test 3: Session Save Path
        echo "<h2>3. Configuração de Sessão</h2>";
        echo "<table>";
        echo "<tr><th>Configuração</th><th>Valor</th></tr>";

        $configs = [
            'session.save_handler' => ini_get('session.save_handler'),
            'session.save_path' => ini_get('session.save_path'),
            'session.name' => ini_get('session.name'),
            'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
            'session.cookie_path' => ini_get('session.cookie_path'),
            'session.cookie_domain' => ini_get('session.cookie_domain'),
            'session.cookie_secure' => ini_get('session.cookie_secure'),
            'session.cookie_httponly' => ini_get('session.cookie_httponly'),
            'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        ];

        foreach ($configs as $key => $value) {
            echo "<tr><td><code>{$key}</code></td><td>" . ($value ?: '<em>vazio</em>') . "</td></tr>";
        }
        echo "</table>";

        // Test 4: Save Path Writable
        echo "<h2>4. Permissões do Diretório de Sessão</h2>";
        $savePath = session_save_path();

        if (empty($savePath)) {
            $savePath = sys_get_temp_dir();
            echo "<div class='info'>ℹ️ Usando diretório temporário do sistema: <code>{$savePath}</code></div>";
        }

        if (is_dir($savePath)) {
            if (is_writable($savePath)) {
                echo "<div class='success'>✅ Diretório existe e tem permissão de escrita: <code>{$savePath}</code></div>";

                // List session files
                $files = glob($savePath . '/sess_*');
                echo "<p><strong>Arquivos de sessão encontrados:</strong> " . count($files) . "</p>";

                if (count($files) > 0) {
                    echo "<details><summary>Ver arquivos (últimos 5)</summary><pre>";
                    $recentFiles = array_slice($files, -5);
                    foreach ($recentFiles as $file) {
                        $mtime = filemtime($file);
                        $size = filesize($file);
                        echo basename($file) . " - " . date('Y-m-d H:i:s', $mtime) . " - {$size} bytes\n";
                    }
                    echo "</pre></details>";
                }
            } else {
                echo "<div class='error'>❌ Diretório existe mas NÃO tem permissão de escrita: <code>{$savePath}</code></div>";
            }
        } else {
            echo "<div class='error'>❌ Diretório NÃO existe: <code>{$savePath}</code></div>";
        }

        // Test 5: Current Session Data
        echo "<h2>5. Dados da Sessão Atual</h2>";
        if (!empty($_SESSION)) {
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        } else {
            echo "<div class='info'>ℹ️ Nenhum dado na sessão</div>";
        }

        // Test 6: Cookies
        echo "<h2>6. Cookies Enviados</h2>";
        if (!empty($_COOKIE)) {
            echo "<table>";
            echo "<tr><th>Nome</th><th>Valor</th></tr>";
            foreach ($_COOKIE as $name => $value) {
                $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                echo "<tr><td><code>{$name}</code></td><td>{$displayValue}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Nenhum cookie foi enviado!</div>";
        }

        // Test 7: Headers
        echo "<h2>7. Headers HTTP</h2>";
        echo "<table>";
        echo "<tr><th>Header</th><th>Valor</th></tr>";

        $headers = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
            'HTTP_COOKIE' => $_SERVER['HTTP_COOKIE'] ?? '',
            'HTTPS' => $_SERVER['HTTPS'] ?? '',
            'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'] ?? '',
        ];

        foreach ($headers as $key => $value) {
            $displayValue = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
            echo "<tr><td><code>{$key}</code></td><td>" . ($displayValue ?: '<em>vazio</em>') . "</td></tr>";
        }
        echo "</table>";

        // Test 8: Simulate Login
        echo "<h2>8. Simulação de Login</h2>";

        if (isset($_POST['simulate_login'])) {
            // Regenerate session
            session_regenerate_id(true);

            // Set login data
            $_SESSION['user_id'] = 999;
            $_SESSION['user_name'] = 'Test User';
            $_SESSION['user_role'] = 'admin';
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Force save
            session_write_close();

            echo "<div class='success'>✅ Dados de login simulados e salvos!</div>";
            echo "<div class='info'>Recarregue a página para verificar se os dados persistiram</div>";
        } else if (isset($_SESSION['user_id'])) {
            echo "<div class='success'>✅ Login simulado ainda está ativo!</div>";
            echo "<div class='info'>";
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "Nome: " . $_SESSION['user_name'] . "<br>";
            echo "Role: " . $_SESSION['user_role'] . "<br>";
            echo "Login há: " . (time() - $_SESSION['login_time']) . " segundos";
            echo "</div>";

            echo "<form method='post'>";
            echo "<button type='submit' name='clear_session' class='btn' style='background: #e53e3e;'>Limpar Sessão</button>";
            echo "</form>";
        } else {
            echo "<div class='info'>ℹ️ Nenhum login simulado ativo</div>";
            echo "<form method='post'>";
            echo "<button type='submit' name='simulate_login' class='btn'>Simular Login</button>";
            echo "</form>";
        }

        if (isset($_POST['clear_session'])) {
            session_destroy();
            echo "<div class='success'>✅ Sessão destruída! Recarregue a página.</div>";
        }

        ?>

        <h2>Próximos Passos</h2>
        <div class="info">
            <strong>Se o contador NÃO aumentar ao recarregar:</strong><br>
            ❌ Sessões não estão persistindo → Problema crítico!<br><br>

            <strong>Se o contador AUMENTAR:</strong><br>
            ✅ Sessões funcionam → Problema está no código de login do CodeIgniter<br><br>

            <strong>Se simular login e dados persistirem:</strong><br>
            ✅ PHP Sessions OK → Problema é no framework<br><br>

            <strong>Se cookies estiverem vazios:</strong><br>
            ❌ Navegador não está aceitando cookies → Verificar configurações
        </div>

        <a href="?" class="btn">🔄 Recarregar Página</a>
        <a href="/auth/login" class="btn" style="background: #48bb78;">🔑 Ir para Login</a>
    </div>
</body>
</html>
