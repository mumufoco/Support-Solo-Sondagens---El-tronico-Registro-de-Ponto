<?php
/**
 * Teste básico do ambiente
 */

echo "=== TESTE BÁSICO DO AMBIENTE ===\n\n";

// 1. Teste de PHP
echo "✅ PHP Version: " . phpversion() . "\n";

// 2. Teste de extensões necessárias
$required_extensions = ['mysqli', 'pdo_mysql', 'mbstring', 'intl', 'json', 'xml'];
echo "\n--- Extensões PHP ---\n";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "$status $ext\n";
}

// 3. Teste de diretórios writable
echo "\n--- Diretórios Writable ---\n";
$writable_dirs = [
    'writable/logs',
    'writable/session',
    'writable/uploads',
    'writable/biometric',
    'writable/exports'
];

foreach ($writable_dirs as $dir) {
    $status = is_writable($dir) ? '✅' : '❌';
    echo "$status $dir\n";
}

// 4. Teste de arquivo .env
echo "\n--- Configuração ---\n";
if (file_exists('.env')) {
    echo "✅ Arquivo .env existe\n";
} else {
    echo "❌ Arquivo .env não encontrado\n";
}

// 5. Teste de criptografia
echo "\n--- Criptografia ---\n";
try {
    $data = "Teste de criptografia";
    $key = random_bytes(32);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    echo "✅ OpenSSL funcionando (AES-256-CBC)\n";
} catch (Exception $e) {
    echo "❌ Erro em OpenSSL: " . $e->getMessage() . "\n";
}

// 6. Teste de hashing de senha
echo "\n--- Password Hashing ---\n";
try {
    $hash = password_hash("test_password", PASSWORD_BCRYPT, ['cost' => 12]);
    $verify = password_verify("test_password", $hash);
    echo $verify ? "✅ BCrypt funcionando (cost 12)\n" : "❌ Erro em BCrypt\n";
} catch (Exception $e) {
    echo "❌ Erro em password_hash: " . $e->getMessage() . "\n";
}

// 7. Teste de conexão MySQL (se disponível)
echo "\n--- Banco de Dados ---\n";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'mysql');
    if ($mysqli->connect_error) {
        echo "❌ MySQL: " . $mysqli->connect_error . "\n";
    } else {
        echo "✅ MySQL conectado (servidor disponível)\n";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ MySQL não disponível: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
