<?php
/**
 * Teste de Configuração zlib.output_compression
 *
 * IMPORTANTE: REMOVA ESTE ARQUIVO APÓS O TESTE!
 *
 * Uso: Acesse http://seu-dominio.com/test-zlib.php
 */

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste zlib.output_compression</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste de Configuração PHP - zlib.output_compression</h1>

        <?php
        $zlibStatus = ini_get('zlib.output_compression');
        $isOff = empty($zlibStatus) || $zlibStatus === 'Off' || $zlibStatus === '0' || $zlibStatus === false;
        ?>

        <div class="status <?php echo $isOff ? 'success' : 'error'; ?>">
            <?php if ($isOff): ?>
                ✅ SUCESSO: zlib.output_compression está DESABILITADO
            <?php else: ?>
                ❌ ERRO: zlib.output_compression está HABILITADO (<?php echo $zlibStatus; ?>)
            <?php endif; ?>
        </div>

        <?php if (!$isOff): ?>
            <div class="warning">
                <strong>⚠️ AÇÃO NECESSÁRIA:</strong><br>
                O zlib.output_compression ainda está habilitado. Siga os passos em <code>FIX_ZLIB_ERROR.md</code>
            </div>
        <?php endif; ?>

        <h2>📊 Configurações PHP Relevantes</h2>
        <table>
            <thead>
                <tr>
                    <th>Diretiva</th>
                    <th>Valor Atual</th>
                    <th>Recomendado</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>zlib.output_compression</td>
                    <td><?php echo ini_get('zlib.output_compression') ?: 'Off'; ?></td>
                    <td>Off</td>
                    <td><?php echo $isOff ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <td>output_buffering</td>
                    <td><?php echo ini_get('output_buffering'); ?></td>
                    <td>4096</td>
                    <td><?php echo ini_get('output_buffering') >= 4096 ? '✅' : '⚠️'; ?></td>
                </tr>
                <tr>
                    <td>max_execution_time</td>
                    <td><?php echo ini_get('max_execution_time'); ?></td>
                    <td>300</td>
                    <td><?php echo ini_get('max_execution_time') >= 300 ? '✅' : '⚠️'; ?></td>
                </tr>
                <tr>
                    <td>memory_limit</td>
                    <td><?php echo ini_get('memory_limit'); ?></td>
                    <td>256M</td>
                    <td><?php echo ini_get('memory_limit') >= '256M' ? '✅' : '⚠️'; ?></td>
                </tr>
                <tr>
                    <td>post_max_size</td>
                    <td><?php echo ini_get('post_max_size'); ?></td>
                    <td>64M</td>
                    <td><?php echo ini_get('post_max_size') >= '64M' ? '✅' : '⚠️'; ?></td>
                </tr>
                <tr>
                    <td>upload_max_filesize</td>
                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                    <td>64M</td>
                    <td><?php echo ini_get('upload_max_filesize') >= '64M' ? '✅' : '⚠️'; ?></td>
                </tr>
            </tbody>
        </table>

        <h2>📂 Arquivos de Configuração Detectados</h2>
        <table>
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Status</th>
                    <th>Localização</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $configFiles = [
                    [
                        'name' => '.user.ini',
                        'path' => dirname(__DIR__) . '/.user.ini'
                    ],
                    [
                        'name' => '.htaccess',
                        'path' => dirname(__DIR__) . '/.htaccess'
                    ],
                    [
                        'name' => 'php.ini (local)',
                        'path' => dirname(__DIR__) . '/php.ini'
                    ]
                ];

                foreach ($configFiles as $file) {
                    $exists = file_exists($file['path']);
                    echo "<tr>";
                    echo "<td><code>{$file['name']}</code></td>";
                    echo "<td>" . ($exists ? '✅ Existe' : '❌ Não existe') . "</td>";
                    echo "<td><small>{$file['path']}</small></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <h2>ℹ️ Informações do Sistema</h2>
        <table>
            <tbody>
                <tr>
                    <td><strong>PHP Version</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Server API</strong></td>
                    <td><?php echo php_sapi_name(); ?></td>
                </tr>
                <tr>
                    <td><strong>Loaded Configuration File</strong></td>
                    <td><?php echo php_ini_loaded_file() ?: 'Nenhum'; ?></td>
                </tr>
                <tr>
                    <td><strong>Additional .ini files</strong></td>
                    <td><?php echo php_ini_scanned_files() ?: 'Nenhum'; ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($isOff): ?>
            <div class="info">
                <strong>✅ Próximos Passos:</strong>
                <ol>
                    <li>Remova este arquivo de teste: <code>rm public/test-zlib.php</code></li>
                    <li>Acesse a aplicação em: <a href="/">http://seu-dominio.com/</a></li>
                    <li>Verifique se não há mais erros nos logs</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Próximos Passos:</strong>
                <ol>
                    <li>Consulte o arquivo <code>FIX_ZLIB_ERROR.md</code></li>
                    <li>Aguarde 5 minutos após criar/editar .user.ini</li>
                    <li>Se persistir, contate seu provedor de hospedagem</li>
                    <li>Recarregue esta página para verificar novamente</li>
                </ol>
            </div>
        <?php endif; ?>

        <p style="text-align: center; color: #666; margin-top: 30px;">
            <small>
                Sistema de Ponto Eletrônico | Support Solo Sondagens 🇧🇷<br>
                <strong>⚠️ REMOVA ESTE ARQUIVO APÓS O TESTE!</strong>
            </small>
        </p>
    </div>
</body>
</html>
