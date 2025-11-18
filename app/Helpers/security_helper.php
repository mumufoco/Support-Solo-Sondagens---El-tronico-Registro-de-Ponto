<?php

/**
 * Security Helper
 *
 * Functions for security, tokens, sanitization, etc.
 */

if (!function_exists('generate_token')) {
    /**
     * Generate a secure random token
     *
     * @param int $length
     * @return string
     */
    function generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('generate_qr_code_data')) {
    /**
     * Generate QR code data for employee punch
     *
     * @param int $employeeId
     * @param int $expiresIn Seconds until expiration (default 300 = 5 minutes)
     * @return string
     */
    function generate_qr_code_data(int $employeeId, int $expiresIn = 300): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $employeeId . $timestamp, env('app.encryption.key', 'default-key'));

        return "EMP-{$employeeId}-{$timestamp}-{$signature}";
    }
}

if (!function_exists('verify_qr_code_data')) {
    /**
     * Verify QR code data
     *
     * @param string $qrData
     * @param int $maxAge Maximum age in seconds (default 300 = 5 minutes)
     * @return array ['valid' => bool, 'employee_id' => int|null, 'error' => string|null]
     */
    function verify_qr_code_data(string $qrData, int $maxAge = 300): array
    {
        $parts = explode('-', $qrData);

        if (count($parts) !== 4 || $parts[0] !== 'EMP') {
            return [
                'valid' => false,
                'employee_id' => null,
                'error' => 'QR Code inválido.',
            ];
        }

        $employeeId = (int) $parts[1];
        $timestamp = (int) $parts[2];
        $signature = $parts[3];

        // Check expiration
        if (time() - $timestamp > $maxAge) {
            return [
                'valid' => false,
                'employee_id' => $employeeId,
                'error' => 'QR Code expirado.',
            ];
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $employeeId . $timestamp, env('app.encryption.key', 'default-key'));

        if (!hash_equals($expectedSignature, $signature)) {
            return [
                'valid' => false,
                'employee_id' => null,
                'error' => 'QR Code inválido (assinatura).',
            ];
        }

        return [
            'valid' => true,
            'employee_id' => $employeeId,
            'error' => null,
        ];
    }
}

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize user input
     *
     * @param string $input
     * @param bool $allowHtml
     * @return string
     */
    function sanitize_input(string $input, bool $allowHtml = false): string
    {
        if ($allowHtml) {
            // Allow only safe HTML tags
            return strip_tags($input, '<p><br><strong><em><u><a><ul><ol><li>');
        }

        // Remove all HTML tags
        return strip_tags($input);
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename
     *
     * @param string $filename
     * @return string
     */
    function sanitize_filename(string $filename): string
    {
        // Remove path information
        $filename = basename($filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = substr($name, 0, 255 - strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }

        return $filename;
    }
}

if (!function_exists('hash_data')) {
    /**
     * Create SHA-256 hash of data (for integrity verification)
     *
     * @param mixed $data
     * @return string
     */
    function hash_data($data): string
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        return hash('sha256', $data);
    }
}

if (!function_exists('verify_password_strength')) {
    /**
     * Verify password strength
     *
     * @param string $password
     * @return array ['valid' => bool, 'score' => int, 'errors' => array]
     */
    function verify_password_strength(string $password): array
    {
        $errors = [];
        $score = 0;

        // Check minimum length
        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
        } else {
            $score += 1;
        }

        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula.';
        } else {
            $score += 1;
        }

        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra minúscula.';
        } else {
            $score += 1;
        }

        // Check for numbers
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um número.';
        } else {
            $score += 1;
        }

        // Check for special characters
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um caractere especial.';
        } else {
            $score += 1;
        }

        return [
            'valid' => empty($errors),
            'score' => $score,
            'errors' => $errors,
        ];
    }
}

if (!function_exists('mask_email')) {
    /**
     * Mask email for privacy (user@example.com -> u***@example.com)
     *
     * @param string $email
     * @return string
     */
    function mask_email(string $email): string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            return $email;
        }

        $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 1);

        return $maskedUsername . '@' . $domain;
    }
}

if (!function_exists('mask_data')) {
    /**
     * Mask sensitive data for display
     *
     * @param string $data
     * @param int $visibleStart
     * @param int $visibleEnd
     * @param string $maskChar
     * @return string
     */
    function mask_data(string $data, int $visibleStart = 3, int $visibleEnd = 3, string $maskChar = '*'): string
    {
        $length = strlen($data);

        if ($length <= ($visibleStart + $visibleEnd)) {
            return $data;
        }

        $start = substr($data, 0, $visibleStart);
        $end = substr($data, -$visibleEnd);
        $maskLength = $length - $visibleStart - $visibleEnd;

        return $start . str_repeat($maskChar, $maskLength) . $end;
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * Get client IP address (considering proxies)
     *
     * @return string
     */
    function get_client_ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle proxy IPs (take first IP)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }
}

if (!function_exists('get_user_agent')) {
    /**
     * Get user agent string
     *
     * @return string
     */
    function get_user_agent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}

if (!function_exists('is_secure_connection')) {
    /**
     * Check if connection is HTTPS
     *
     * @return bool
     */
    function is_secure_connection(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }

        return false;
    }
}

if (!function_exists('generate_hash_signature')) {
    /**
     * Generate signature for data integrity (NSR hash)
     *
     * @param array $data
     * @return string
     */
    function generate_hash_signature(array $data): string
    {
        // Sort keys for consistent hashing
        ksort($data);

        // Convert to JSON
        $json = json_encode($data);

        // Create SHA-256 hash
        return hash('sha256', $json);
    }
}

if (!function_exists('verify_hash_signature')) {
    /**
     * Verify data integrity signature
     *
     * @param array $data
     * @param string $expectedHash
     * @return bool
     */
    function verify_hash_signature(array $data, string $expectedHash): bool
    {
        $actualHash = generate_hash_signature($data);

        return hash_equals($expectedHash, $actualHash);
    }
}

if (!function_exists('encrypt_data')) {
    /**
     * Encrypt data using CodeIgniter's encryption
     *
     * @param string $data
     * @return string|false
     */
    function encrypt_data(string $data)
    {
        try {
            $encrypter = \Config\Services::encrypter();
            return base64_encode($encrypter->encrypt($data));
        } catch (\Exception $e) {
            log_message('error', 'Encryption error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('decrypt_data')) {
    /**
     * Decrypt data using CodeIgniter's encryption
     *
     * @param string $encryptedData
     * @return string|false
     */
    function decrypt_data(string $encryptedData)
    {
        try {
            $encrypter = \Config\Services::encrypter();
            return $encrypter->decrypt(base64_decode($encryptedData));
        } catch (\Exception $e) {
            log_message('error', 'Decryption error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sanitize_for_log')) {
    /**
     * SECURITY FIX: Sanitize data before logging to prevent sensitive data leaks
     *
     * Removes or masks sensitive information before writing to logs.
     * This prevents accidental exposure of passwords, tokens, credit cards, etc.
     *
     * @param mixed $data The data to sanitize (string, array, or object)
     * @return mixed Sanitized data
     */
    function sanitize_for_log($data)
    {
        // Handle different data types
        if (is_string($data)) {
            return sanitize_string_for_log($data);
        }

        if (is_array($data)) {
            return sanitize_array_for_log($data);
        }

        if (is_object($data)) {
            return sanitize_array_for_log((array) $data);
        }

        return $data;
    }
}

if (!function_exists('sanitize_string_for_log')) {
    /**
     * Sanitize a string value for logging
     *
     * @param string $value
     * @return string
     */
    function sanitize_string_for_log(string $value): string
    {
        // List of patterns to detect and mask
        $sensitivePatterns = [
            // Credit card numbers (basic pattern)
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/' => '****-****-****-****',

            // CPF (Brazilian tax ID)
            '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '***.***.***-**',
            '/\b\d{11}\b/' => '***********',

            // Email addresses
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL_REDACTED]',

            // Tokens/API keys (long alphanumeric strings)
            '/\b[A-Za-z0-9]{32,}\b/' => '[TOKEN_REDACTED]',

            // IP addresses
            '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/' => '[IP_REDACTED]',
        ];

        foreach ($sensitivePatterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }
}

if (!function_exists('sanitize_array_for_log')) {
    /**
     * Sanitize an array for logging (recursively)
     *
     * @param array $data
     * @return array
     */
    function sanitize_array_for_log(array $data): array
    {
        // Sensitive keys to redact (case-insensitive)
        $sensitiveKeys = [
            'password',
            'passwd',
            'pwd',
            'secret',
            'token',
            'api_key',
            'apikey',
            'access_token',
            'refresh_token',
            'auth_token',
            'authorization',
            'csrf_token',
            'encryption_key',
            'private_key',
            'credit_card',
            'card_number',
            'cvv',
            'cvc',
            'ssn',
            'social_security',
            'biometric_data',
            'template_data',
            'remember_token',
        ];

        $sanitized = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);

            // Check if key is sensitive
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($keyLower, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                // Redact sensitive values
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$key] = sanitize_array_for_log($value);
            } elseif (is_object($value)) {
                // Convert object to array and sanitize
                $sanitized[$key] = sanitize_array_for_log((array) $value);
            } elseif (is_string($value)) {
                // Sanitize string values
                $sanitized[$key] = sanitize_string_for_log($value);
            } else {
                // Keep other types as-is
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

if (!function_exists('safe_log')) {
    /**
     * SECURITY FIX: Log message with automatic sanitization of sensitive data
     *
     * Wrapper around log_message() that automatically sanitizes data
     *
     * @param string $level Log level (error, warning, info, debug, etc.)
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    function safe_log(string $level, string $message, array $context = []): void
    {
        // Sanitize message
        $safeMessage = sanitize_string_for_log($message);

        // Sanitize context
        $safeContext = sanitize_array_for_log($context);

        // If context is provided, append it to message
        if (!empty($safeContext)) {
            $safeMessage .= ' | Context: ' . json_encode($safeContext);
        }

        // Log with CodeIgniter's log_message
        log_message($level, $safeMessage);
    }
}
