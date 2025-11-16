<?php

namespace Tests\Unit\Services\Security;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Security\EncryptionService;

/**
 * Encryption Service Test
 *
 * Tests for EncryptionService functionality
 */
class EncryptionServiceTest extends CIUnitTestCase
{
    protected $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a test encryption key
        putenv('ENCRYPTION_KEY=' . EncryptionService::generateKey());
        putenv('ENCRYPTION_KEY_VERSION=1');

        $this->encryptionService = new EncryptionService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('ENCRYPTION_KEY=');
        putenv('ENCRYPTION_KEY_VERSION=');
    }

    public function testEncryptDecrypt()
    {
        $plaintext = 'This is a secret message';

        $encrypted = $this->encryptionService->encrypt($plaintext);

        // Encrypted should be different from plaintext
        $this->assertNotEquals($plaintext, $encrypted);

        // Encrypted should be base64
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $encrypted);

        // Decrypt should return original
        $decrypted = $this->encryptionService->decrypt($encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptEmptyStringThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->encryptionService->encrypt('');
    }

    public function testDecryptInvalidBase64ThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->encryptionService->decrypt('not-valid-base64!!!');
    }

    public function testDecryptTooShortThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        // Base64 of a string too short (< 41 bytes)
        $this->encryptionService->decrypt(base64_encode('short'));
    }

    public function testDecryptCorruptedDataThrowsException()
    {
        $plaintext = 'Test message';
        $encrypted = $this->encryptionService->encrypt($plaintext);

        // Corrupt the encrypted data
        $decoded = base64_decode($encrypted);
        $corrupted = substr($decoded, 0, -5) . 'xxxxx'; // Change last 5 bytes
        $corruptedEncrypted = base64_encode($corrupted);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $this->encryptionService->decrypt($corruptedEncrypted);
    }

    public function testEncryptJsonDecryptJson()
    {
        $data = [
            'api_key' => 'secret-key-123',
            'api_secret' => 'very-secret-456',
            'nested' => [
                'value' => 'nested-secret'
            ]
        ];

        $encrypted = $this->encryptionService->encryptJson($data);

        // Should be base64 string
        $this->assertIsString($encrypted);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $encrypted);

        $decrypted = $this->encryptionService->decryptJson($encrypted);

        // Should match original array
        $this->assertEquals($data, $decrypted);
    }

    public function testEncryptJsonAsObject()
    {
        $data = ['key' => 'value'];
        $encrypted = $this->encryptionService->encryptJson($data);

        $decryptedAsObject = $this->encryptionService->decryptJson($encrypted, false);

        $this->assertIsObject($decryptedAsObject);
        $this->assertEquals('value', $decryptedAsObject->key);
    }

    public function testHash()
    {
        $password = 'MySecurePassword123!';
        $hash = $this->encryptionService->hash($password);

        // Hash should be different from password
        $this->assertNotEquals($password, $hash);

        // Hash should start with $argon2id$
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testVerifyHash()
    {
        $password = 'MySecurePassword123!';
        $hash = $this->encryptionService->hash($password);

        // Correct password should verify
        $this->assertTrue($this->encryptionService->verifyHash($password, $hash));

        // Wrong password should not verify
        $this->assertFalse($this->encryptionService->verifyHash('WrongPassword', $hash));
    }

    public function testNeedsRehash()
    {
        $password = 'Test123!';
        $hash = $this->encryptionService->hash($password);

        // Fresh hash should not need rehash
        $this->assertFalse($this->encryptionService->needsRehash($hash));
    }

    public function testSecureCompare()
    {
        $a = 'secret-token-123';
        $b = 'secret-token-123';
        $c = 'different-token';

        // Same strings should match
        $this->assertTrue($this->encryptionService->secureCompare($a, $b));

        // Different strings should not match
        $this->assertFalse($this->encryptionService->secureCompare($a, $c));

        // Different lengths should not match
        $this->assertFalse($this->encryptionService->secureCompare($a, 'short'));
    }

    public function testGenerateToken()
    {
        $token = $this->encryptionService->generateToken();

        // Should be base64 string
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $token);

        // Should be 32 bytes (43-44 chars in base64)
        $decoded = base64_decode($token);
        $this->assertEquals(32, strlen($decoded));

        // Two tokens should be different
        $token2 = $this->encryptionService->generateToken();
        $this->assertNotEquals($token, $token2);
    }

    public function testGenerateTokenCustomLength()
    {
        $token = $this->encryptionService->generateToken(64);

        $decoded = base64_decode($token);
        $this->assertEquals(64, strlen($decoded));
    }

    public function testGenerateTokenMinimumLength()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->encryptionService->generateToken(8); // Less than 16
    }

    public function testGenerateKey()
    {
        $key = EncryptionService::generateKey();

        // Should be base64 string
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $key);

        // Should decode to 32 bytes
        $decoded = base64_decode($key, true);
        $this->assertNotFalse($decoded);
        $this->assertEquals(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, strlen($decoded));

        // Two keys should be different
        $key2 = EncryptionService::generateKey();
        $this->assertNotEquals($key, $key2);
    }

    public function testEncryptDecryptMultipleTimes()
    {
        $plaintext = 'Test message for multiple encryptions';

        // Encrypt multiple times with same key
        $encrypted1 = $this->encryptionService->encrypt($plaintext);
        $encrypted2 = $this->encryptionService->encrypt($plaintext);
        $encrypted3 = $this->encryptionService->encrypt($plaintext);

        // Each encryption should be unique (due to random nonce)
        $this->assertNotEquals($encrypted1, $encrypted2);
        $this->assertNotEquals($encrypted2, $encrypted3);
        $this->assertNotEquals($encrypted1, $encrypted3);

        // But all should decrypt to same plaintext
        $this->assertEquals($plaintext, $this->encryptionService->decrypt($encrypted1));
        $this->assertEquals($plaintext, $this->encryptionService->decrypt($encrypted2));
        $this->assertEquals($plaintext, $this->encryptionService->decrypt($encrypted3));
    }

    public function testEncryptLargeData()
    {
        // Test with 1MB of data
        $plaintext = str_repeat('A', 1024 * 1024);

        $encrypted = $this->encryptionService->encrypt($plaintext);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptUnicodeData()
    {
        $plaintext = '你好世界 🌍 Olá Mundo مرحبا بالعالم';

        $encrypted = $this->encryptionService->encrypt($plaintext);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }
}
