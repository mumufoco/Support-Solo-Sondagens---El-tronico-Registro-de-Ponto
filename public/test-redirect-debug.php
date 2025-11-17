<?php
/**
 * Redirect Loop Debugger
 *
 * Este arquivo ajuda a diagnosticar loops de redirecionamento
 * ACESSE UMA VEZ E DELETE IMEDIATAMENTE!
 */

// Prevent any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Prevent any redirects
header('X-Debug: ON');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Redirect Loop</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .section { background: #2d2d2d; padding: 20px; margin: 20px 0; border-left: 4px solid #007acc; }
        h2 { color: #4ec9b0; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #3e3e3e; }
        td:first-child { color: #9cdcfe; width: 30%; }
        .ok { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        pre { background: #1e1e1e; padding: 15px; overflow-x: auto; border: 1px solid #3e3e3e; }
    </style>
</head>
<body>
    <h1>🔍 Debug - Redirect Loop Diagnostic</h1>

    <div class="section">
        <h2>1. Request Information</h2>
        <table>
            <tr>
                <td>Request Method:</td>
                <td><?= $_SERVER['REQUEST_METHOD'] ?></td>
            </tr>
            <tr>
                <td>Request URI:</td>
                <td><?= $_SERVER['REQUEST_URI'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>HTTP Host:</td>
                <td><?= $_SERVER['HTTP_HOST'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>Server Protocol:</td>
                <td><?= $_SERVER['SERVER_PROTOCOL'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>HTTPS:</td>
                <td><?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '<span class="ok">YES (HTTPS)</span>' : '<span class="warning">NO (HTTP)</span>' ?></td>
            </tr>
            <tr>
                <td>Server Software:</td>
                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>2. Session Status</h2>
        <table>
            <tr>
                <td>Session Status:</td>
                <td>
                    <?php
                    $status = session_status();
                    if ($status === PHP_SESSION_DISABLED) {
                        echo '<span class="error">DISABLED</span>';
                    } elseif ($status === PHP_SESSION_NONE) {
                        echo '<span class="warning">NOT STARTED</span>';
                    } else {
                        echo '<span class="ok">ACTIVE</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Session Save Path:</td>
                <td><?= session_save_path() ?: ini_get('session.save_path') ?: 'default' ?></td>
            </tr>
            <tr>
                <td>Session Writable:</td>
                <td>
                    <?php
                    $savePath = session_save_path() ?: ini_get('session.save_path');
                    if (empty($savePath)) {
                        $savePath = sys_get_temp_dir();
                    }
                    if (is_writable($savePath)) {
                        echo '<span class="ok">YES</span>';
                    } else {
                        echo '<span class="error">NO - This may cause redirect loops!</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Can Start Session:</td>
                <td>
                    <?php
                    try {
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $_SESSION['test'] = 'OK';
                        echo '<span class="ok">YES - Session working</span>';
                    } catch (Exception $e) {
                        echo '<span class="error">NO - ' . htmlspecialchars($e->getMessage()) . '</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>3. .env Configuration</h2>
        <?php
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            $baseURL = '';
            $forceHTTPS = '';

            if (preg_match('/app\.baseURL\s*=\s*[\'"]?([^\'"\\r\\n]+)[\'"]?/i', $envContent, $matches)) {
                $baseURL = trim($matches[1]);
            }
            if (preg_match('/app\.forceGlobalSecureRequests\s*=\s*([^\r\n]+)/i', $envContent, $matches)) {
                $forceHTTPS = trim($matches[1]);
            }

            echo '<table>';
            echo '<tr><td>app.baseURL:</td><td>' . htmlspecialchars($baseURL) . '</td></tr>';
            echo '<tr><td>Current URL:</td><td>' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/</td></tr>';
            echo '<tr><td>Match:</td><td>';

            $currentProtocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $currentURL = $currentProtocol . '://' . $_SERVER['HTTP_HOST'] . '/';

            if (strpos($baseURL, $currentProtocol) === 0) {
                echo '<span class="ok">✓ Protocol matches</span>';
            } else {
                echo '<span class="error">✗ PROTOCOL MISMATCH - This causes redirect loops!</span>';
            }
            echo '</td></tr>';
            echo '<tr><td>forceGlobalSecureRequests:</td><td>' . htmlspecialchars($forceHTTPS) . '</td></tr>';
            echo '</table>';
        } else {
            echo '<span class="error">.env file not found!</span>';
        }
        ?>
    </div>

    <div class="section">
        <h2>4. Writable Directory Check</h2>
        <table>
            <?php
            $dirs = [
                '../writable/session' => 'Session Directory',
                '../writable/cache' => 'Cache Directory',
                '../writable/logs' => 'Logs Directory',
            ];

            foreach ($dirs as $dir => $name) {
                $path = __DIR__ . '/' . $dir;
                echo '<tr>';
                echo '<td>' . $name . ':</td>';
                echo '<td>';
                if (is_dir($path)) {
                    if (is_writable($path)) {
                        echo '<span class="ok">✓ EXISTS and WRITABLE</span>';
                    } else {
                        echo '<span class="error">✗ EXISTS but NOT WRITABLE!</span>';
                    }
                } else {
                    echo '<span class="error">✗ DOES NOT EXIST!</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </div>

    <div class="section">
        <h2>5. Potential Issues</h2>
        <?php
        $issues = [];

        // Check protocol mismatch
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (preg_match('/app\.baseURL\s*=\s*[\'"]?([^\'"\\r\\n]+)[\'"]?/i', $envContent, $matches)) {
                $baseURL = trim($matches[1]);
                $currentProtocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                if (strpos($baseURL, $currentProtocol) !== 0) {
                    $issues[] = "CRITICAL: baseURL protocol mismatch (configured: $baseURL, current: $currentProtocol)";
                }
            }
        }

        // Check session directory
        $sessionPath = __DIR__ . '/../writable/session';
        if (!is_dir($sessionPath)) {
            $issues[] = "CRITICAL: Session directory does not exist: $sessionPath";
        } elseif (!is_writable($sessionPath)) {
            $issues[] = "CRITICAL: Session directory not writable: $sessionPath";
        }

        // Check if session failed to start
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $issues[] = "ERROR: Session could not be started";
        }

        if (empty($issues)) {
            echo '<span class="ok">✓ No obvious issues detected</span>';
        } else {
            echo '<ul style="color: #f48771; margin: 0;">';
            foreach ($issues as $issue) {
                echo '<li>' . htmlspecialchars($issue) . '</li>';
            }
            echo '</ul>';
        }
        ?>
    </div>

    <div class="section">
        <h2>6. Recommended Fix</h2>
        <pre><?php
        $currentProtocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $currentHost = $_SERVER['HTTP_HOST'];

        echo "Edit your .env file and update:\n\n";
        echo "app.baseURL = '$currentProtocol://$currentHost/'\n";
        echo "app.forceGlobalSecureRequests = false\n\n";
        echo "Then run:\n";
        echo "chmod -R 775 writable/\n";
        echo "chmod -R 664 writable/**/*.php\n";
        ?></pre>
    </div>

    <div class="section" style="background: #3e1e1e; border-left-color: #f48771;">
        <h2 style="color: #f48771;">⚠️ SECURITY WARNING</h2>
        <p style="color: #ce9178;">DELETE THIS FILE IMMEDIATELY AFTER DIAGNOSIS!</p>
        <pre>rm public/test-redirect-debug.php</pre>
    </div>

</body>
</html>
