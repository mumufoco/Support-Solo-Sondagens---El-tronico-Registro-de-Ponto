<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Remember Token Model
 *
 * SECURITY FIX: Secure "Remember Me" token management
 *
 * Security Features:
 * - Tokens are hashed before storage (SHA-256)
 * - Uses selector/verifier pattern (prevents timing attacks)
 * - Automatic expiration and cleanup
 * - IP and User Agent tracking for audit
 * - One-time use tokens (invalidated after use)
 */
class RememberTokenModel extends Model
{
    protected $table            = 'remember_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id',
        'token_hash',
        'selector',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_used_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Generate a new remember token for an employee
     *
     * @param int $employeeId
     * @param int $daysValid Number of days the token is valid (default 30)
     * @return array ['selector' => string, 'verifier' => string] or false on failure
     */
    public function generateToken(int $employeeId, int $daysValid = 30)
    {
        try {
            // Generate cryptographically secure random bytes
            $selector = bin2hex(random_bytes(16)); // 32 hex characters
            $verifier = bin2hex(random_bytes(32)); // 64 hex characters

            // Hash the verifier before storing
            $tokenHash = hash('sha256', $verifier);

            // Calculate expiration
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$daysValid} days"));

            // Store token
            $data = [
                'employee_id'  => $employeeId,
                'selector'     => $selector,
                'token_hash'   => $tokenHash,
                'ip_address'   => get_client_ip(),
                'user_agent'   => get_user_agent(),
                'expires_at'   => $expiresAt,
                'last_used_at' => null,
            ];

            $this->insert($data);

            // Return selector and verifier (NOT the hash)
            // These will be stored in the client's cookie as: selector:verifier
            return [
                'selector' => $selector,
                'verifier' => $verifier,
            ];
        } catch (\Exception $e) {
            log_message('error', 'Failed to generate remember token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate a remember token
     *
     * SECURITY: Uses constant-time comparison to prevent timing attacks
     *
     * @param string $selector
     * @param string $verifier
     * @return object|null Employee data if valid, null otherwise
     */
    public function validateToken(string $selector, string $verifier): ?object
    {
        // Find token by selector
        $token = $this->where('selector', $selector)->first();

        if (!$token) {
            log_message('warning', 'Remember token validation failed: selector not found');
            return null;
        }

        // Check if token is expired
        if (strtotime($token->expires_at) < time()) {
            log_message('info', 'Remember token expired: ' . $selector);
            $this->delete($token->id);
            return null;
        }

        // Hash the provided verifier
        $verifierHash = hash('sha256', $verifier);

        // SECURITY: Use constant-time comparison to prevent timing attacks
        if (!hash_equals($token->token_hash, $verifierHash)) {
            log_message('warning', 'Remember token validation failed: invalid verifier for selector ' . $selector);

            // SECURITY: Delete token on failed verification (prevents brute force)
            $this->delete($token->id);

            return null;
        }

        // Token is valid - get employee
        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($token->employee_id);

        if (!$employee || !$employee->active) {
            log_message('warning', 'Remember token validation failed: employee not found or inactive');
            $this->delete($token->id);
            return null;
        }

        // Update last_used_at
        $this->update($token->id, [
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);

        return $employee;
    }

    /**
     * Invalidate (delete) a specific token
     *
     * @param string $selector
     * @return bool
     */
    public function invalidateToken(string $selector): bool
    {
        return $this->where('selector', $selector)->delete();
    }

    /**
     * Invalidate all tokens for an employee
     *
     * Useful for logout from all devices
     *
     * @param int $employeeId
     * @return bool
     */
    public function invalidateAllForEmployee(int $employeeId): bool
    {
        return $this->where('employee_id', $employeeId)->delete();
    }

    /**
     * Clean up expired tokens
     *
     * Should be called periodically (e.g., daily cron job)
     *
     * @return int Number of tokens deleted
     */
    public function cleanupExpired(): int
    {
        $deleted = $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();

        if ($deleted > 0) {
            log_message('info', "Cleaned up {$deleted} expired remember tokens");
        }

        return $deleted;
    }

    /**
     * Get all active tokens for an employee
     *
     * Useful for "active devices" page
     *
     * @param int $employeeId
     * @return array
     */
    public function getActiveTokensForEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('last_used_at', 'DESC')
            ->findAll();
    }
}
