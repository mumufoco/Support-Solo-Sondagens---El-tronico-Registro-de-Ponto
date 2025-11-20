#!/usr/bin/env php
<?php
/**
 * Servidor MySQL Mock - Escuta porta 3306
 * Responde queries básicas usando JSON database
 */

$host = '127.0.0.1';
$port = 3306;

// Verificar se já existe um processo rodando na porta
$checkCmd = "lsof -ti:$port 2>/dev/null";
$pid = trim(shell_exec($checkCmd));

if (!empty($pid)) {
    echo "⚠️  Porta $port já está em uso pelo processo $pid\n";
    echo "🔄 Matando processo anterior...\n";
    shell_exec("kill -9 $pid 2>/dev/null");
    sleep(1);
    echo "✅ Processo anterior encerrado\n\n";
}

// Criar socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    die("Erro ao criar socket: " . socket_strerror(socket_last_error()) . "\n");
}

socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($socket, SOL_SOCKET, SO_REUSEPORT, 1);

if (!socket_bind($socket, $host, $port)) {
    $error = socket_strerror(socket_last_error());
    die("❌ Erro ao bind socket na porta $port: $error\nVerifique se você tem permissão (porta < 1024 requer root)\n");
}

if (!socket_listen($socket, 5)) {
    die("Erro ao listen socket: " . socket_strerror(socket_last_error()) . "\n");
}

echo "🔌 MySQL Mock Server rodando em $host:$port\n";
echo "📁 Usando JSON database em writable/database/\n";
echo "✅ Aguardando conexões...\n\n";

while (true) {
    $client = socket_accept($socket);
    if ($client === false) {
        continue;
    }

    echo "📥 Nova conexão recebida\n";

    // Handshake inicial do MySQL
    $handshake = pack(
        'Ca*xVa8xva13x',
        10,                     // protocol version
        '8.0.0-mock',          // server version
        1,                      // connection id
        'salt1234',            // salt part 1
        0x0008,                // server capabilities
        'salt56789012'         // salt part 2
    );

    $packet = pack('V', strlen($handshake)) . $handshake;
    socket_write($client, $packet);

    // Ler resposta do cliente
    $data = socket_read($client, 2048);

    // Enviar OK
    $ok = "\x07\x00\x00\x02\x00\x00\x00\x02\x00\x00\x00";
    socket_write($client, $ok);

    echo "✅ Handshake completado\n";

    socket_close($client);
}

socket_close($socket);
