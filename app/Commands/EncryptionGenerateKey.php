<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\Security\EncryptionService;

/**
 * Generate Encryption Key Command
 *
 * Generates a secure encryption key for use with EncryptionService
 *
 * Usage: php spark encryption:generate-key
 */
class EncryptionGenerateKey extends BaseCommand
{
    /**
     * Command group
     *
     * @var string
     */
    protected $group = 'Security';

    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'encryption:generate-key';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Generate a secure encryption key for settings encryption';

    /**
     * Command usage
     *
     * @var string
     */
    protected $usage = 'encryption:generate-key [options]';

    /**
     * Command options
     *
     * @var array
     */
    protected $options = [
        '--show'  => 'Display the key instead of modifying files',
        '--force' => 'Overwrite existing key in .env file',
    ];

    /**
     * Run command
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::newLine();
        CLI::write('Generating encryption key...', 'yellow');
        CLI::newLine();

        try {
            // Generate key
            $key = EncryptionService::generateKey();

            CLI::write('✓ Key generated successfully!', 'green');
            CLI::newLine();

            // Show key
            if (CLI::getOption('show')) {
                CLI::write('Encryption Key:', 'cyan');
                CLI::write($key, 'yellow');
                CLI::newLine();
                CLI::write('Add this to your .env file:', 'cyan');
                CLI::write('ENCRYPTION_KEY=' . $key, 'white');
                CLI::newLine();
                return;
            }

            // Update .env file
            $envPath = ROOTPATH . '.env';

            if (!file_exists($envPath)) {
                CLI::error('❌ .env file not found at: ' . $envPath);
                CLI::write('Please create one from .env.example', 'yellow');
                CLI::newLine();
                CLI::write('Then add this line:', 'cyan');
                CLI::write('ENCRYPTION_KEY=' . $key, 'white');
                CLI::newLine();
                return;
            }

            $envContent = file_get_contents($envPath);

            // Check if key already exists
            if (preg_match('/^ENCRYPTION_KEY\s*=/m', $envContent)) {
                if (!CLI::getOption('force')) {
                    CLI::error('❌ ENCRYPTION_KEY already exists in .env');
                    CLI::write('Use --force to overwrite, or --show to display the new key', 'yellow');
                    CLI::newLine();
                    CLI::write('⚠️  WARNING: Changing the encryption key will make existing encrypted data unreadable!', 'red');
                    CLI::newLine();
                    return;
                }

                CLI::write('⚠️  Overwriting existing ENCRYPTION_KEY (--force used)', 'yellow');
                $envContent = preg_replace(
                    '/^ENCRYPTION_KEY\s*=.*$/m',
                    'ENCRYPTION_KEY = ' . $key,
                    $envContent
                );
            } else {
                // Append key to .env
                $envContent .= "\n#--------------------------------------------------------------------\n";
                $envContent .= "# ENCRYPTION\n";
                $envContent .= "#--------------------------------------------------------------------\n";
                $envContent .= "ENCRYPTION_KEY = " . $key . "\n";
                $envContent .= "ENCRYPTION_KEY_VERSION = 1\n";
            }

            // Write to .env
            if (file_put_contents($envPath, $envContent) === false) {
                CLI::error('❌ Failed to write to .env file');
                CLI::newLine();
                CLI::write('Manually add this line to your .env:', 'cyan');
                CLI::write('ENCRYPTION_KEY=' . $key, 'white');
                CLI::newLine();
                return;
            }

            CLI::write('✓ Encryption key added to .env file!', 'green');
            CLI::newLine();
            CLI::write('Key: ' . $key, 'white');
            CLI::newLine();
            CLI::write('⚠️  Keep this key secure! Loss of this key means loss of encrypted data.', 'yellow');
            CLI::write('⚠️  Add .env to .gitignore to prevent committing the key.', 'yellow');
            CLI::newLine();

        } catch (\Exception $e) {
            CLI::error('❌ Error: ' . $e->getMessage());
            CLI::newLine();
        }
    }
}
