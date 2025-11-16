<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create Push Notification Tokens Table
 *
 * Stores FCM device tokens for push notifications
 */
class CreatePushNotificationTokens extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => 'Employee who owns this device',
            ],
            'device_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'FCM device registration token',
            ],
            'platform' => [
                'type' => 'ENUM',
                'constraint' => ['android', 'ios', 'web'],
                'default' => 'android',
                'comment' => 'Device platform',
            ],
            'device_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Friendly device name',
            ],
            'is_valid' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'comment' => 'Token validity status',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'comment' => 'Token registration time',
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'comment' => 'Token last update time',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('device_token');
        $this->forge->addKey(['employee_id', 'is_valid']);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('push_notification_tokens');

        // Add unique constraint on device_token
        $this->db->query('CREATE UNIQUE INDEX idx_push_tokens_device ON push_notification_tokens(device_token)');

        // Add index for cleanup queries
        $this->db->query('CREATE INDEX idx_push_tokens_valid ON push_notification_tokens(is_valid)');
    }

    public function down()
    {
        // Drop indexes
        $this->db->query('DROP INDEX idx_push_tokens_device ON push_notification_tokens');
        $this->db->query('DROP INDEX idx_push_tokens_valid ON push_notification_tokens');

        // Drop table
        $this->forge->dropTable('push_notification_tokens', true);
    }
}
