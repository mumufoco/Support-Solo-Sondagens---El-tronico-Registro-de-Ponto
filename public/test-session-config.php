<?php
/**
 * Session Configuration Test
 *
 * Este arquivo verifica se as configurações de sessão estão corretas
 * para evitar o warning "session.gc_divisor must be greater than 0"
 *
 * Acesse: http://seudominio.com/test-session-config.php
 * DELETE após verificação!
 */

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Configuração de Sessão</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 40px; }
        .section { margin-bottom: 30px; }
        .section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        table th {
            background: #34495e;
            color: white;
            font-weight: 600;
        }
        table tr:hover { background: #f8f9fa; }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.ok { background: #2ecc71; color: white; }
        .status.warning { background: #f39c12; color: white; }
        .status.error { background: #e74c3c; color: white; }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d5f4e6; color: #27ae60; border-left: 4px solid #27ae60; }
        .alert-warning { background: #fef5e7; color: #f39c12; border-left: 4px solid #f39c12; }
        .alert-error { background: #fadbd8; color: #e74c3c; border-left: 4px solid #e74c3c; }
        .footer {
            background: #ecf0f1;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }
        code {
            background: #ecf0f1;
            padding: 3px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
        }
        .recommendation {
            background: #e8f5ff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Teste de Configuração de Sessão</h1>
            <p>Verificação de session.gc_divisor e outras configurações</p>
        </div>

        <div class="content">
            <?php
            // Get session configuration
            $gc_probability = ini_get('session.gc_probability');
            $gc_divisor = ini_get('session.gc_divisor');
            $gc_maxlifetime = ini_get('session.gc_maxlifetime');

            // Check for the warning condition
            $has_gc_divisor_issue = ($gc_divisor <= 0);

            // Calculate probability if divisor is valid
            $cleanup_percentage = 0;
            if ($gc_divisor > 0) {
                $cleanup_percentage = ($gc_probability / $gc_divisor) * 100;
            }
            ?>

            <!-- Status Summary -->
            <div class="section">
                <h2>📊 Status Geral</h2>
                <?php if ($has_gc_divisor_issue): ?>
                    <div class="alert alert-error">
                        <strong>❌ PROBLEMA DETECTADO!</strong><br>
                        O valor de <code>session.gc_divisor</code> é <?= $gc_divisor ?>, mas deve ser maior que 0.
                        <br><br>
                        <strong>Solução:</strong> Configurar <code>session.gc_divisor = 100</code> e <code>session.gc_probability = 1</code>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>✓ CONFIGURAÇÃO OK!</strong><br>
                        As configurações de garbage collector de sessão estão corretas.
                        <br><br>
                        Probabilidade de limpeza: <strong><?= number_format($cleanup_percentage, 2) ?>%</strong> por requisição
                    </div>
                <?php endif; ?>
            </div>

            <!-- Session Garbage Collector Settings -->
            <div class="section">
                <h2>🗑️ Configurações do Garbage Collector</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Configuração</th>
                            <th>Valor Atual</th>
                            <th>Valor Recomendado</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>session.gc_probability</code></td>
                            <td><?= $gc_probability ?></td>
                            <td>1</td>
                            <td>
                                <?php if ($gc_probability == 1): ?>
                                    <span class="status ok">✓ OK</span>
                                <?php else: ?>
                                    <span class="status warning">⚠ Diferente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>session.gc_divisor</code></td>
                            <td><?= $gc_divisor ?></td>
                            <td>100</td>
                            <td>
                                <?php if ($gc_divisor > 0 && $gc_divisor <= 1000): ?>
                                    <span class="status ok">✓ OK</span>
                                <?php elseif ($gc_divisor > 1000): ?>
                                    <span class="status warning">⚠ Muito Alto</span>
                                <?php else: ?>
                                    <span class="status error">✗ ERRO</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>session.gc_maxlifetime</code></td>
                            <td><?= $gc_maxlifetime ?> segundos (<?= round($gc_maxlifetime / 60) ?> minutos)</td>
                            <td>7200 (2 horas)</td>
                            <td>
                                <?php if ($gc_maxlifetime >= 3600): ?>
                                    <span class="status ok">✓ OK</span>
                                <?php else: ?>
                                    <span class="status warning">⚠ Curto</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="recommendation">
                    <strong>ℹ️ Como funciona o Garbage Collector:</strong><br><br>
                    O PHP limpa sessões antigas automaticamente com base na fórmula:<br>
                    <code>Probabilidade = session.gc_probability / session.gc_divisor</code><br><br>

                    <?php if ($gc_divisor > 0): ?>
                        <strong>Sua configuração atual:</strong><br>
                        Probabilidade = <?= $gc_probability ?> / <?= $gc_divisor ?> = <strong><?= number_format($cleanup_percentage, 2) ?>%</strong><br>
                        Isso significa que a cada 100 requisições, aproximadamente <?= round($cleanup_percentage) ?> delas irão limpar sessões expiradas.
                    <?php else: ?>
                        <strong style="color: #e74c3c;">⚠️ Atenção:</strong> Com gc_divisor = 0, o garbage collector não funciona!
                    <?php endif; ?>
                </div>
            </div>

            <!-- Other Session Settings -->
            <div class="section">
                <h2>⚙️ Outras Configurações de Sessão</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Configuração</th>
                            <th>Valor</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>session.save_handler</code></td>
                            <td><?= ini_get('session.save_handler') ?></td>
                            <td>Método de armazenamento das sessões</td>
                        </tr>
                        <tr>
                            <td><code>session.save_path</code></td>
                            <td><?= ini_get('session.save_path') ?: '(padrão do sistema)' ?></td>
                            <td>Diretório onde sessões são salvas</td>
                        </tr>
                        <tr>
                            <td><code>session.use_strict_mode</code></td>
                            <td><?= ini_get('session.use_strict_mode') ? 'On' : 'Off' ?></td>
                            <td>Modo estrito (recomendado: On)</td>
                        </tr>
                        <tr>
                            <td><code>session.cookie_httponly</code></td>
                            <td><?= ini_get('session.cookie_httponly') ? 'On' : 'Off' ?></td>
                            <td>Previne acesso via JavaScript (recomendado: On)</td>
                        </tr>
                        <tr>
                            <td><code>session.cookie_secure</code></td>
                            <td><?= ini_get('session.cookie_secure') ? 'On' : 'Off' ?></td>
                            <td>Cookies apenas via HTTPS (produção: On)</td>
                        </tr>
                        <tr>
                            <td><code>session.cookie_samesite</code></td>
                            <td><?= ini_get('session.cookie_samesite') ?: 'None' ?></td>
                            <td>Proteção CSRF (recomendado: Lax ou Strict)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Recommendations -->
            <?php if ($has_gc_divisor_issue): ?>
            <div class="section">
                <h2>💡 Ações Recomendadas</h2>
                <div class="alert alert-warning">
                    <strong>Para corrigir o warning "session.gc_divisor must be greater than 0":</strong><br><br>

                    <strong>1. Via .user.ini (PHP-FPM):</strong><br>
                    Adicione no arquivo <code>.user.ini</code> na raiz do projeto:<br>
                    <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; margin: 10px 0; overflow-x: auto;">session.gc_probability = 1
session.gc_divisor = 100
session.gc_maxlifetime = 7200</pre>

                    <strong>2. Via .htaccess (mod_php):</strong><br>
                    Adicione no arquivo <code>.htaccess</code>:<br>
                    <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; margin: 10px 0; overflow-x: auto;">&lt;IfModule mod_php.c&gt;
    php_value session.gc_probability 1
    php_value session.gc_divisor 100
    php_value session.gc_maxlifetime 7200
&lt;/IfModule&gt;</pre>

                    <strong>3. Via php.ini (se tiver acesso):</strong><br>
                    <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; margin: 10px 0; overflow-x: auto;">session.gc_probability = 1
session.gc_divisor = 100
session.gc_maxlifetime = 7200</pre>

                    <br>
                    <strong>⏱️ Nota:</strong> Mudanças em <code>.user.ini</code> podem levar até 5 minutos para entrar em vigor.
                </div>
            </div>
            <?php endif; ?>

            <!-- PHP Info -->
            <div class="section">
                <h2>📋 Informações do PHP</h2>
                <table>
                    <tbody>
                        <tr>
                            <td><strong>Versão do PHP:</strong></td>
                            <td><?= PHP_VERSION ?></td>
                        </tr>
                        <tr>
                            <td><strong>SAPI:</strong></td>
                            <td><?= php_sapi_name() ?></td>
                        </tr>
                        <tr>
                            <td><strong>Sistema Operacional:</strong></td>
                            <td><?= PHP_OS ?></td>
                        </tr>
                        <tr>
                            <td><strong>zlib.output_compression:</strong></td>
                            <td><?= ini_get('zlib.output_compression') ? 'On' : 'Off' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Test Session Creation -->
            <div class="section">
                <h2>🧪 Teste de Criação de Sessão</h2>
                <?php
                try {
                    // Try to start session
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Set test value
                    $_SESSION['test_session'] = 'Sistema de Ponto Eletrônico - ' . date('Y-m-d H:i:s');
                    $session_id = session_id();

                    echo '<div class="alert alert-success">';
                    echo '<strong>✓ Sessão criada com sucesso!</strong><br>';
                    echo 'ID da Sessão: <code>' . htmlspecialchars($session_id) . '</code><br>';
                    echo 'Valor de teste: <code>' . htmlspecialchars($_SESSION['test_session']) . '</code>';
                    echo '</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-error">';
                    echo '<strong>✗ Erro ao criar sessão:</strong><br>';
                    echo htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
                ?>
            </div>

        </div>

        <div class="footer">
            <strong style="color: #e74c3c;">⚠️ IMPORTANTE: DELETE este arquivo após verificação!</strong><br>
            Execute: <code>rm public/test-session-config.php</code><br><br>
            Sistema de Ponto Eletrônico © <?= date('Y') ?>
        </div>
    </div>
</body>
</html>
<?php
// End output buffering
ob_end_flush();
?>
