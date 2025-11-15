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
