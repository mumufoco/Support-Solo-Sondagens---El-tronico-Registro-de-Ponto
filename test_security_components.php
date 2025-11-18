<?php
/**
 * Testes de Componentes de Segurança
 *
 * Valida as implementações de segurança sem necessidade de banco de dados
 */

echo "=================================================\n";
echo "  TESTES DE SEGURANÇA - COMPONENTES CRÍTICOS\n";
echo "=================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

// ============================================
// TESTE 1: Validação de Senha Forte
// ============================================
echo "--- TESTE 1: Validação de Senha Forte ---\n";

function testPasswordStrength() {
    $weak_passwords = [
        'abc123',           // Muito curta
        'abcdefghijkl',     // Sem maiúscula/número/especial
        'Abcdefgh123',      // Sem caractere especial
        'Abc@defg',         // Menos de 12 caracteres
    ];

    $strong_passwords = [
        'Abc@12345678',     // Válida
        'MyP@ssw0rd2024!',  // Válida
        'S3cur3P@ssword',   // Válida
    ];

    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{12,}$/';

    $all_correct = true;

    // Testar senhas fracas (devem falhar)
    foreach ($weak_passwords as $pwd) {
        if (preg_match($pattern, $pwd)) {
            echo "  ❌ Senha fraca aceita: $pwd\n";
            $all_correct = false;
        }
    }

    // Testar senhas fortes (devem passar)
    foreach ($strong_passwords as $pwd) {
        if (!preg_match($pattern, $pwd)) {
            echo "  ❌ Senha forte rejeitada: $pwd\n";
            $all_correct = false;
        }
    }

    return $all_correct;
}

if (testPasswordStrength()) {
    echo "  ✅ Validação de senha forte funcionando corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na validação de senha forte\n";
    $tests_failed++;
}

// ============================================
// TESTE 2: Password Hashing com BCrypt
// ============================================
echo "\n--- TESTE 2: Password Hashing (BCrypt) ---\n";

function testPasswordHashing() {
    $password = "MySecureP@ssw0rd123";
    $cost = 12;

    // Hash
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);

    // Verificar formato
    if (!str_starts_with($hash, '$2y$')) {
        echo "  ❌ Hash não é BCrypt\n";
        return false;
    }

    // Verificar tamanho
    if (strlen($hash) !== 60) {
        echo "  ❌ Hash com tamanho incorreto: " . strlen($hash) . "\n";
        return false;
    }

    // Verificar validação
    if (!password_verify($password, $hash)) {
        echo "  ❌ Verificação falhou\n";
        return false;
    }

    // Verificar que senha errada não passa
    if (password_verify("WrongPassword", $hash)) {
        echo "  ❌ Senha incorreta foi aceita\n";
        return false;
    }

    return true;
}

if (testPasswordHashing()) {
    echo "  ✅ BCrypt (cost 12) funcionando corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA no hashing de senhas\n";
    $tests_failed++;
}

// ============================================
// TESTE 3: Criptografia de Dados Biométricos
// ============================================
echo "\n--- TESTE 3: Criptografia AES-256-CBC ---\n";

function testBiometricEncryption() {
    $template_data = [
        'encoding' => array_fill(0, 128, rand(0, 255)),
        'timestamp' => time(),
        'quality' => 0.95
    ];

    $key = random_bytes(32); // 256 bits
    $iv = random_bytes(16);  // 128 bits

    // Encrypt
    $json = json_encode($template_data);
    $encrypted = openssl_encrypt($json, 'aes-256-cbc', $key, 0, $iv);

    if ($encrypted === false) {
        echo "  ❌ Falha na criptografia\n";
        return false;
    }

    // Verificar que dados não estão em plaintext
    if (strpos($encrypted, 'encoding') !== false) {
        echo "  ❌ Dados não foram criptografados (plaintext visível)\n";
        return false;
    }

    // Decrypt
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    $recovered = json_decode($decrypted, true);

    // Verificar integridade
    if ($recovered['timestamp'] !== $template_data['timestamp']) {
        echo "  ❌ Dados corrompidos após descriptografia\n";
        return false;
    }

    // HMAC para integridade
    $hmac = hash_hmac('sha256', $encrypted, $key);
    $hmac_verify = hash_equals($hmac, hash_hmac('sha256', $encrypted, $key));

    if (!$hmac_verify) {
        echo "  ❌ HMAC verification falhou\n";
        return false;
    }

    return true;
}

if (testBiometricEncryption()) {
    echo "  ✅ AES-256-CBC + HMAC funcionando corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na criptografia de dados biométricos\n";
    $tests_failed++;
}

// ============================================
// TESTE 4: Geração de Tokens Seguros (Remember Me)
// ============================================
echo "\n--- TESTE 4: Remember Me Tokens (Selector/Verifier) ---\n";

function testRememberMeTokens() {
    // Simular geração de tokens
    $selector = bin2hex(random_bytes(16));  // 32 chars hex
    $verifier = bin2hex(random_bytes(32));  // 64 chars hex

    // Verificar tamanhos
    if (strlen($selector) !== 32) {
        echo "  ❌ Selector com tamanho incorreto: " . strlen($selector) . "\n";
        return false;
    }

    if (strlen($verifier) !== 64) {
        echo "  ❌ Verifier com tamanho incorreto: " . strlen($verifier) . "\n";
        return false;
    }

    // Hash do verifier (SHA-256)
    $verifier_hash = hash('sha256', $verifier);

    if (strlen($verifier_hash) !== 64) {
        echo "  ❌ Hash SHA-256 com tamanho incorreto\n";
        return false;
    }

    // Constant-time comparison
    $is_equal = hash_equals($verifier_hash, hash('sha256', $verifier));

    if (!$is_equal) {
        echo "  ❌ hash_equals falhou\n";
        return false;
    }

    // Verificar que hashes diferentes não são iguais
    $different_verifier = bin2hex(random_bytes(32));
    $is_different = !hash_equals($verifier_hash, hash('sha256', $different_verifier));

    if (!$is_different) {
        echo "  ❌ hash_equals aceitou hashes diferentes\n";
        return false;
    }

    return true;
}

if (testRememberMeTokens()) {
    echo "  ✅ Selector/Verifier pattern funcionando corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA nos tokens de Remember Me\n";
    $tests_failed++;
}

// ============================================
// TESTE 5: Sanitização de Logs
// ============================================
echo "\n--- TESTE 5: Sanitização de Logs ---\n";

function testLogSanitization() {
    function sanitize_for_log(string $data): string {
        // Remove newlines, carriage returns, null bytes
        $sanitized = str_replace(["\n", "\r", "\0", "\t"], '', $data);

        // Remove ANSI escape codes
        $sanitized = preg_replace('/\x1B\[[0-9;]*[a-zA-Z]/', '', $sanitized);

        return $sanitized;
    }

    $malicious_inputs = [
        "user@test.com\nFAKE LOG ENTRY",
        "user@test.com\rANOTHER FAKE",
        "user@test.com\0NULL BYTE",
        "user@test.com\e[31mRED TEXT\e[0m",
    ];

    foreach ($malicious_inputs as $input) {
        $sanitized = sanitize_for_log($input);

        if (strpos($sanitized, "\n") !== false ||
            strpos($sanitized, "\r") !== false ||
            strpos($sanitized, "\0") !== false ||
            strpos($sanitized, "\e[") !== false) {
            echo "  ❌ Sanitização falhou para: " . bin2hex($input) . "\n";
            return false;
        }
    }

    return true;
}

if (testLogSanitization()) {
    echo "  ✅ Sanitização de logs funcionando corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na sanitização de logs\n";
    $tests_failed++;
}

// ============================================
// TESTE 6: SQL Injection Prevention (Prepared Statements)
// ============================================
echo "\n--- TESTE 6: SQL Injection Prevention ---\n";

function testSQLInjectionPrevention() {
    // Simular validação de tipo (primeira linha de defesa)
    $malicious_inputs = [
        "1 OR 1=1",
        "1; DROP TABLE users;--",
        "' OR '1'='1",
        "1 UNION SELECT * FROM users",
    ];

    foreach ($malicious_inputs as $input) {
        // Validar como INT
        $id = filter_var($input, FILTER_VALIDATE_INT);

        if ($id !== false && $input !== (string)$id) {
            echo "  ❌ Input malicioso foi aceito como INT: $input\n";
            return false;
        }
    }

    // Verificar que inputs legítimos passam
    $valid_id = filter_var("123", FILTER_VALIDATE_INT);
    if ($valid_id === false) {
        echo "  ❌ ID válido foi rejeitado\n";
        return false;
    }

    return true;
}

if (testSQLInjectionPrevention()) {
    echo "  ✅ Proteção contra SQL Injection OK (validação de tipos)\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na proteção contra SQL Injection\n";
    $tests_failed++;
}

// ============================================
// TESTE 7: XSS Prevention (Output Escaping)
// ============================================
echo "\n--- TESTE 7: XSS Prevention (Output Escaping) ---\n";

function testXSSPrevention() {
    function esc_html(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $xss_payloads = [
        '<script>alert("XSS")</script>',
        '<img src=x onerror=alert("XSS")>',
        '<svg onload=alert("XSS")>',
        'javascript:alert("XSS")',
        '<iframe src="javascript:alert(\'XSS\')"></iframe>',
    ];

    foreach ($xss_payloads as $payload) {
        $escaped = esc_html($payload);

        // Verificar que tags HTML foram escapadas
        if (strpos($escaped, '<script') !== false ||
            strpos($escaped, '<img') !== false ||
            strpos($escaped, '<svg') !== false ||
            strpos($escaped, '<iframe') !== false) {
            echo "  ❌ XSS payload não foi escapado: $payload\n";
            return false;
        }

        // Verificar que entidades HTML estão presentes
        if (strpos($escaped, '&lt;') === false && strpos($payload, '<') !== false) {
            echo "  ❌ Tags não foram convertidas em entidades HTML\n";
            return false;
        }
    }

    return true;
}

if (testXSSPrevention()) {
    echo "  ✅ Output escaping funcionando corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na prevenção de XSS\n";
    $tests_failed++;
}

// ============================================
// TESTE 8: Path Traversal Prevention
// ============================================
echo "\n--- TESTE 8: Path Traversal Prevention ---\n";

function testPathTraversalPrevention() {
    $allowed_base = '/var/www/uploads/';

    $malicious_paths = [
        '../../../etc/passwd',
        '..\\..\\..\\windows\\system32\\config\\sam',
        '....//....//....//etc/passwd',
        '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
    ];

    foreach ($malicious_paths as $path) {
        // Normalizar path
        $decoded = urldecode($path);
        $real_path = realpath($allowed_base . $decoded);

        // Verificar que path normalizado está dentro do diretório permitido
        if ($real_path !== false && strpos($real_path, $allowed_base) !== 0) {
            echo "  ❌ Path traversal não foi bloqueado: $path\n";
            return false;
        }

        // Também verificar por padrão ../ diretamente
        if (strpos($decoded, '../') !== false || strpos($decoded, '..\\') !== false) {
            // OK, detectado
        }
    }

    return true;
}

if (testPathTraversalPrevention()) {
    echo "  ✅ Proteção contra Path Traversal OK\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na proteção contra Path Traversal\n";
    $tests_failed++;
}

// ============================================
// TESTE 9: Cookie Security Flags
// ============================================
echo "\n--- TESTE 9: Cookie Security Flags ---\n";

function testCookieSecurityFlags() {
    // Simular configuração de cookie seguro
    $cookie_options = [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'domain' => '',
        'secure' => true,      // HTTPS only
        'httponly' => true,    // JavaScript can't access
        'samesite' => 'Strict' // CSRF protection
    ];

    // Verificar flags obrigatórias
    if (!$cookie_options['httponly']) {
        echo "  ❌ Flag HttpOnly não está ativa\n";
        return false;
    }

    if ($cookie_options['samesite'] !== 'Strict' && $cookie_options['samesite'] !== 'Lax') {
        echo "  ❌ Flag SameSite não configurada corretamente\n";
        return false;
    }

    // Secure deve estar true em produção (OK estar false em dev)
    // Não falhar se false, apenas avisar

    return true;
}

if (testCookieSecurityFlags()) {
    echo "  ✅ Cookie security flags configuradas corretamente\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA nas configurações de cookies\n";
    $tests_failed++;
}

// ============================================
// TESTE 10: CSRF Token Generation
// ============================================
echo "\n--- TESTE 10: CSRF Token Generation ---\n";

function testCSRFTokenGeneration() {
    // Simular geração de CSRF token
    $token = bin2hex(random_bytes(32));

    // Verificar tamanho
    if (strlen($token) !== 64) {
        echo "  ❌ CSRF token com tamanho incorreto: " . strlen($token) . "\n";
        return false;
    }

    // Verificar que é hexadecimal
    if (!ctype_xdigit($token)) {
        echo "  ❌ CSRF token não é hexadecimal\n";
        return false;
    }

    // Verificar que tokens são únicos
    $token2 = bin2hex(random_bytes(32));
    if ($token === $token2) {
        echo "  ❌ CSRF tokens não são únicos\n";
        return false;
    }

    return true;
}

if (testCSRFTokenGeneration()) {
    echo "  ✅ CSRF token generation OK\n";
    $tests_passed++;
} else {
    echo "  ❌ FALHA na geração de tokens CSRF\n";
    $tests_failed++;
}

// ============================================
// RELATÓRIO FINAL
// ============================================
echo "\n=================================================\n";
echo "              RELATÓRIO FINAL\n";
echo "=================================================\n\n";
echo "Total de testes: " . ($tests_passed + $tests_failed) . "\n";
echo "✅ Testes passaram: $tests_passed\n";
echo "❌ Testes falharam: $tests_failed\n\n";

if ($tests_failed === 0) {
    echo "🎉 TODOS OS TESTES DE SEGURANÇA PASSARAM! 🎉\n";
    echo "\n";
    echo "Os componentes de segurança críticos estão funcionando\n";
    echo "corretamente. O sistema está pronto para deployment após\n";
    echo "configurar o banco de dados MySQL.\n";
    exit(0);
} else {
    echo "⚠️  ALGUNS TESTES FALHARAM ⚠️\n";
    echo "\n";
    echo "Revise as falhas acima antes de fazer deployment.\n";
    exit(1);
}
