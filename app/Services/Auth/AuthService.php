<?php

namespace App\Services\Auth;

use App\Models\EmployeeModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Serviço de Autenticação
 *
 * Gerencia login, proteção contra brute force, hash de senhas e JWT
 */
class AuthService
{
    protected EmployeeModel $employeeModel;

    /**
     * Máximo de tentativas de login antes de bloquear
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Tempo de bloqueio em segundos (15 minutos)
     */
    private const BLOCK_DURATION = 900;

    /**
     * Tempo de vida do JWT em segundos (1 hora)
     */
    private const JWT_EXPIRATION = 3600;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Realiza login do usuário
     *
     * @param string $email Email do usuário
     * @param string $password Senha em texto plano
     * @return array|false Dados do usuário ou false se falhar
     */
    public function login(string $email, string $password): array|false
    {
        // Verificar se está bloqueado por brute force
        if ($this->isBlocked($email)) {
            return false;
        }

        // Buscar usuário por email
        $user = $this->employeeModel->where('email', $email)->where('active', 1)->first();

        if (!$user) {
            $this->recordFailedAttempt($email);
            return false;
        }

        // Verificar senha
        if (!password_verify($password, $user['password'])) {
            $this->recordFailedAttempt($email);
            return false;
        }

        // Login bem-sucedido - limpar tentativas falhas
        $this->clearFailedAttempts($email);

        // Remover senha dos dados retornados
        unset($user['password']);

        return $user;
    }

    /**
     * Gera hash de senha usando Argon2id
     *
     * @param string $password Senha em texto plano
     * @return string Hash da senha
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verifica se um email está bloqueado por brute force
     *
     * @param string $email Email para verificar
     * @return bool True se bloqueado
     */
    public function isBlocked(string $email): bool
    {
        $attempts = $this->getFailedAttempts($email);

        if (count($attempts) < self::MAX_LOGIN_ATTEMPTS) {
            return false;
        }

        // Verificar se o bloqueio expirou
        $lastAttemptTime = max($attempts);
        $blockExpiration = $lastAttemptTime + self::BLOCK_DURATION;

        if (time() > $blockExpiration) {
            // Bloqueio expirou, limpar tentativas
            $this->clearFailedAttempts($email);
            return false;
        }

        return true;
    }

    /**
     * Registra uma tentativa de login falha
     *
     * @param string $email Email que tentou fazer login
     * @return void
     */
    public function recordFailedAttempt(string $email): void
    {
        $cache = \Config\Services::cache();
        $key = 'login_attempts_' . md5($email);

        $attempts = $cache->get($key) ?: [];
        $attempts[] = time();

        // Manter apenas as últimas 10 tentativas
        if (count($attempts) > 10) {
            $attempts = array_slice($attempts, -10);
        }

        // Armazenar por 1 hora
        $cache->save($key, $attempts, 3600);
    }

    /**
     * Obtém os timestamps das tentativas falhas de login
     *
     * @param string $email Email para verificar
     * @return array Array de timestamps
     */
    public function getFailedAttempts(string $email): array
    {
        $cache = \Config\Services::cache();
        $key = 'login_attempts_' . md5($email);

        $attempts = $cache->get($key) ?: [];

        // Filtrar tentativas antigas (mais de 15 minutos)
        $cutoffTime = time() - self::BLOCK_DURATION;
        $attempts = array_filter($attempts, function ($timestamp) use ($cutoffTime) {
            return $timestamp > $cutoffTime;
        });

        return array_values($attempts);
    }

    /**
     * Limpa as tentativas falhas de login
     *
     * @param string $email Email para limpar
     * @return void
     */
    protected function clearFailedAttempts(string $email): void
    {
        $cache = \Config\Services::cache();
        $key = 'login_attempts_' . md5($email);
        $cache->delete($key);
    }

    /**
     * Valida a força da senha
     *
     * @param string $password Senha para validar
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        // Mínimo 8 caracteres
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        // Pelo menos uma letra maiúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Pelo menos uma letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Pelo menos um número
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Pelo menos um caractere especial
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Gera um token JWT para o usuário
     *
     * @param array $userData Dados do usuário
     * @return string Token JWT
     */
    public function generateJWT(array $userData): string
    {
        $secretKey = getenv('JWT_SECRET_KEY') ?: 'default_secret_key_change_in_production';

        $payload = [
            'iss' => base_url(), // Issuer
            'iat' => time(), // Issued at
            'exp' => time() + self::JWT_EXPIRATION, // Expiration
            'sub' => $userData['id'], // Subject (user ID)
            'email' => $userData['email'],
            'name' => $userData['name'],
            'role' => $userData['role'] ?? 'employee',
        ];

        return JWT::encode($payload, $secretKey, 'HS256');
    }

    /**
     * Valida e decodifica um token JWT
     *
     * @param string $token Token JWT
     * @return object|false Dados do token ou false se inválido
     */
    public function validateJWT(string $token): object|false
    {
        try {
            $secretKey = getenv('JWT_SECRET_KEY') ?: 'default_secret_key_change_in_production';
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            return $decoded;
        } catch (\Exception $e) {
            log_message('error', 'JWT validation failed: ' . $e->getMessage());
            return false;
        }
    }
}
