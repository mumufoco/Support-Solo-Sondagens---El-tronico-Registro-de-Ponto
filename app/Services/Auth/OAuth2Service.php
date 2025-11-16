<?php

namespace App\Services\Auth;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Config\Services;
use App\Services\Security\EncryptionService;

/**
 * OAuth 2.0 Service
 *
 * Implements OAuth 2.0 token-based authentication for mobile API
 *
 * Features:
 * - Access token generation and validation
 * - Refresh token support
 * - Token revocation
 * - Device fingerprinting
 * - Scope management
 * - Token expiration handling
 *
 * Grant Types Supported:
 * - Password Grant (Resource Owner Password Credentials)
 * - Refresh Token Grant
 *
 * @package App\Services\Auth
 */
class OAuth2Service
{
    /**
     * Database connection
     * @var ConnectionInterface
     */
    protected ConnectionInterface $db;

    /**
     * Encryption service
     * @var EncryptionService
     */
    protected EncryptionService $encryption;

    /**
     * Access token lifetime (in seconds)
     * Default: 1 hour
     *
     * @var int
     */
    protected int $accessTokenLifetime = 3600;

    /**
     * Refresh token lifetime (in seconds)
     * Default: 30 days
     *
     * @var int
     */
    protected int $refreshTokenLifetime = 2592000;

    /**
     * Token length (bytes)
     *
     * @var int
     */
    protected int $tokenLength = 32;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Services::database()->connect();
        $this->encryption = new EncryptionService();

        // Load lifetimes from environment
        $accessLifetime = getenv('OAUTH_ACCESS_TOKEN_LIFETIME');
        if ($accessLifetime !== false && is_numeric($accessLifetime)) {
            $this->accessTokenLifetime = (int) $accessLifetime;
        }

        $refreshLifetime = getenv('OAUTH_REFRESH_TOKEN_LIFETIME');
        if ($refreshLifetime !== false && is_numeric($refreshLifetime)) {
            $this->refreshTokenLifetime = (int) $refreshLifetime;
        }
    }

    /**
     * Generate access token for employee
     *
     * @param int $employeeId Employee ID
     * @param string $deviceFingerprint Device fingerprint
     * @param array $scopes Token scopes
     * @return array ['access_token' => string, 'refresh_token' => string, 'expires_in' => int]
     */
    public function generateTokens(int $employeeId, string $deviceFingerprint, array $scopes = []): array
    {
        // Generate tokens
        $accessToken = $this->generateSecureToken();
        $refreshToken = $this->generateSecureToken();

        // Calculate expiration times
        $accessExpiresAt = date('Y-m-d H:i:s', time() + $this->accessTokenLifetime);
        $refreshExpiresAt = date('Y-m-d H:i:s', time() + $this->refreshTokenLifetime);

        // Hash tokens for storage
        $accessTokenHash = $this->hashToken($accessToken);
        $refreshTokenHash = $this->hashToken($refreshToken);

        // Store access token
        $this->db->table('oauth_access_tokens')->insert([
            'employee_id' => $employeeId,
            'token_hash' => $accessTokenHash,
            'device_fingerprint' => $deviceFingerprint,
            'scopes' => json_encode($scopes),
            'expires_at' => $accessExpiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $accessTokenId = $this->db->insertID();

        // Store refresh token
        $this->db->table('oauth_refresh_tokens')->insert([
            'employee_id' => $employeeId,
            'access_token_id' => $accessTokenId,
            'token_hash' => $refreshTokenHash,
            'device_fingerprint' => $deviceFingerprint,
            'expires_at' => $refreshExpiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        log_message('info', "OAuth tokens generated for employee ID: {$employeeId}, device: {$deviceFingerprint}");

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenLifetime,
            'scope' => implode(' ', $scopes),
        ];
    }

    /**
     * Validate access token
     *
     * @param string $accessToken Access token
     * @param string|null $deviceFingerprint Device fingerprint (optional validation)
     * @return array|null Token data if valid, null if invalid
     */
    public function validateAccessToken(string $accessToken, ?string $deviceFingerprint = null): ?array
    {
        $tokenHash = $this->hashToken($accessToken);

        $builder = $this->db->table('oauth_access_tokens')
            ->where('token_hash', $tokenHash)
            ->where('revoked', false)
            ->where('expires_at >', date('Y-m-d H:i:s'));

        // Validate device fingerprint if provided
        if ($deviceFingerprint !== null) {
            $builder->where('device_fingerprint', $deviceFingerprint);
        }

        $token = $builder->get()->getRow();

        if (!$token) {
            return null;
        }

        // Update last used timestamp
        $this->db->table('oauth_access_tokens')
            ->where('id', $token->id)
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        return [
            'id' => $token->id,
            'employee_id' => $token->employee_id,
            'device_fingerprint' => $token->device_fingerprint,
            'scopes' => json_decode($token->scopes, true) ?? [],
            'expires_at' => $token->expires_at,
        ];
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $refreshToken Refresh token
     * @param string $deviceFingerprint Device fingerprint
     * @return array|null New tokens if valid, null if invalid
     */
    public function refreshAccessToken(string $refreshToken, string $deviceFingerprint): ?array
    {
        $tokenHash = $this->hashToken($refreshToken);

        // Find refresh token
        $token = $this->db->table('oauth_refresh_tokens')
            ->where('token_hash', $tokenHash)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('revoked', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();

        if (!$token) {
            log_message('warning', "Invalid refresh token attempt from device: {$deviceFingerprint}");
            return null;
        }

        // Get original access token to retrieve scopes
        $oldAccessToken = $this->db->table('oauth_access_tokens')
            ->where('id', $token->access_token_id)
            ->get()
            ->getRow();

        $scopes = $oldAccessToken ? json_decode($oldAccessToken->scopes, true) ?? [] : [];

        // Revoke old tokens
        $this->revokeAccessToken($token->access_token_id);
        $this->revokeRefreshToken($token->id);

        // Generate new tokens
        $newTokens = $this->generateTokens($token->employee_id, $deviceFingerprint, $scopes);

        log_message('info', "OAuth tokens refreshed for employee ID: {$token->employee_id}");

        return $newTokens;
    }

    /**
     * Revoke access token by ID
     *
     * @param int $accessTokenId Access token ID
     * @return bool
     */
    public function revokeAccessToken(int $accessTokenId): bool
    {
        $result = $this->db->table('oauth_access_tokens')
            ->where('id', $accessTokenId)
            ->update(['revoked' => true, 'revoked_at' => date('Y-m-d H:i:s')]);

        return $result > 0;
    }

    /**
     * Revoke refresh token by ID
     *
     * @param int $refreshTokenId Refresh token ID
     * @return bool
     */
    public function revokeRefreshToken(int $refreshTokenId): bool
    {
        $result = $this->db->table('oauth_refresh_tokens')
            ->where('id', $refreshTokenId)
            ->update(['revoked' => true, 'revoked_at' => date('Y-m-d H:i:s')]);

        return $result > 0;
    }

    /**
     * Revoke all tokens for an employee
     *
     * @param int $employeeId Employee ID
     * @param string|null $exceptDeviceFingerprint Exclude device from revocation
     * @return int Number of tokens revoked
     */
    public function revokeAllTokens(int $employeeId, ?string $exceptDeviceFingerprint = null): int
    {
        $builder = $this->db->table('oauth_access_tokens')
            ->where('employee_id', $employeeId)
            ->where('revoked', false);

        if ($exceptDeviceFingerprint !== null) {
            $builder->where('device_fingerprint !=', $exceptDeviceFingerprint);
        }

        $count = $builder->update([
            'revoked' => true,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        // Also revoke refresh tokens
        $builder = $this->db->table('oauth_refresh_tokens')
            ->where('employee_id', $employeeId)
            ->where('revoked', false);

        if ($exceptDeviceFingerprint !== null) {
            $builder->where('device_fingerprint !=', $exceptDeviceFingerprint);
        }

        $builder->update([
            'revoked' => true,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        log_message('info', "Revoked {$count} tokens for employee ID: {$employeeId}");

        return $count;
    }

    /**
     * Clean up expired tokens
     *
     * @return int Number of tokens cleaned up
     */
    public function cleanupExpiredTokens(): int
    {
        $now = date('Y-m-d H:i:s');

        // Delete expired access tokens
        $accessCount = $this->db->table('oauth_access_tokens')
            ->where('expires_at <', $now)
            ->where('revoked', true)
            ->delete();

        // Delete expired refresh tokens
        $refreshCount = $this->db->table('oauth_refresh_tokens')
            ->where('expires_at <', $now)
            ->where('revoked', true)
            ->delete();

        $total = $accessCount + $refreshCount;

        if ($total > 0) {
            log_message('info', "Cleaned up {$total} expired OAuth tokens ({$accessCount} access, {$refreshCount} refresh)");
        }

        return $total;
    }

    /**
     * Get active tokens for employee
     *
     * @param int $employeeId Employee ID
     * @return array
     */
    public function getActiveTokens(int $employeeId): array
    {
        $tokens = $this->db->table('oauth_access_tokens')
            ->select('id, device_fingerprint, scopes, created_at, last_used_at, expires_at')
            ->where('employee_id', $employeeId)
            ->where('revoked', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResult();

        return array_map(function ($token) {
            return [
                'id' => $token->id,
                'device_fingerprint' => $token->device_fingerprint,
                'scopes' => json_decode($token->scopes, true) ?? [],
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
            ];
        }, $tokens);
    }

    /**
     * Generate secure random token
     *
     * @return string
     */
    protected function generateSecureToken(): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }

    /**
     * Hash token for storage
     *
     * @param string $token
     * @return string
     */
    protected function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Check if token has specific scope
     *
     * @param array $tokenData Token data from validateAccessToken
     * @param string $requiredScope Required scope
     * @return bool
     */
    public function hasScope(array $tokenData, string $requiredScope): bool
    {
        $scopes = $tokenData['scopes'] ?? [];
        return in_array($requiredScope, $scopes, true) || in_array('*', $scopes, true);
    }

    /**
     * Set access token lifetime
     *
     * @param int $seconds Lifetime in seconds
     * @return void
     */
    public function setAccessTokenLifetime(int $seconds): void
    {
        $this->accessTokenLifetime = $seconds;
    }

    /**
     * Set refresh token lifetime
     *
     * @param int $seconds Lifetime in seconds
     * @return void
     */
    public function setRefreshTokenLifetime(int $seconds): void
    {
        $this->refreshTokenLifetime = $seconds;
    }

    /**
     * Get access token lifetime
     *
     * @return int
     */
    public function getAccessTokenLifetime(): int
    {
        return $this->accessTokenLifetime;
    }

    /**
     * Get refresh token lifetime
     *
     * @return int
     */
    public function getRefreshTokenLifetime(): int
    {
        return $this->refreshTokenLifetime;
    }

    /**
     * Generate device fingerprint from request
     *
     * @return string
     */
    public static function generateDeviceFingerprint(): string
    {
        $request = Services::request();

        $components = [
            $request->getUserAgent()->getAgentString(),
            $request->getIPAddress(),
            $request->getHeaderLine('Accept-Language'),
        ];

        return hash('sha256', implode('|', $components));
    }
}
