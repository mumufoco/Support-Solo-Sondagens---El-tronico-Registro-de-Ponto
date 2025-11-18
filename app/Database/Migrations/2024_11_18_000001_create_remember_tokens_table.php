<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create Remember Tokens Table
 *
 * SECURITY FIX: Implement secure "Remember Me" functionality
 * This table stores authentication tokens for persistent login sessions
 */
class CreateRememberTokensTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Employee who owns this token',
            ],
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'SHA-256 hash of the token (not stored in plaintext)',
            ],
            'selector' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'comment'    => 'Public token selector for efficient lookup',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'comment'    => 'IP address where token was created',
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
                'comment'    => 'User agent string for device identification',
            ],
            'expires_at' => [
                'type'    => 'DATETIME',
                'comment' => 'When this token expires',
            ],
            'last_used_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Last time this token was used for authentication',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // Primary key
        $this->forge->addKey('id', true);

        // Indexes
        $this->forge->addKey('employee_id');
        $this->forge->addKey('selector'); // For efficient token lookup
        $this->forge->addKey('expires_at'); // For cleanup of expired tokens
        $this->forge->addUniqueKey(['selector', 'token_hash']); // Prevent duplicates

        // Foreign key
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('remember_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('remember_tokens');
    }
}
