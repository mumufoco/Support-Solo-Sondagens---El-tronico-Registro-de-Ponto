<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChatMessagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'sender_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para employees (remetente)',
            ],
            'recipient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para employees (destinatário)',
            ],
            'message' => [
                'type'    => 'TEXT',
                'comment' => 'Conteúdo da mensagem (max 5000 chars)',
            ],
            'attachment_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Caminho do arquivo anexado',
            ],
            'attachment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'comment'    => 'Tipo MIME do anexo',
            ],
            'attachment_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Tamanho do anexo em bytes',
            ],
            'sent_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Data e hora de envio',
            ],
            'delivered_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Data e hora de entrega (WebSocket)',
            ],
            'read_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Data e hora de leitura',
            ],
            'deleted_by_sender' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'comment'    => 'Mensagem deletada pelo remetente',
            ],
            'deleted_by_recipient' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'comment'    => 'Mensagem deletada pelo destinatário',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['sender_id', 'recipient_id', 'sent_at']);
        $this->forge->addKey(['recipient_id', 'read_at']);
        $this->forge->addKey('sent_at');

        $this->forge->addForeignKey('sender_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recipient_id', 'employees', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('chat_messages');
    }

    public function down()
    {
        $this->forge->dropTable('chat_messages');
    }
}
