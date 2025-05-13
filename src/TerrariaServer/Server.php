<?php

$server = stream_socket_server("tcp://0.0.0.0:7777", $errno, $errstr);
if (!$server) {
    die("Error: $errstr ($errno)");
}

echo "Server started on port 7777\n";

while ($client = @stream_socket_accept($server, -1)) {
    echo "Client connected.\n";
    handleClient($client);
}

function handleClient($client) {
    echo "Waiting for Connect Request (Packet ID 1)\n";
    $packet = readPacket($client);
    if (!$packet || $packet['id'] !== 1) return disconnect($client);

    echo "Client version: " . parseVersion($packet['data']) . "\n";

    // Send Password Request (Packet ID 2) - No password
    echo "Sent Password Request (Packet ID 2)\n";
    writePacket($client, 2, chr(0)); // chr(0) = no password

    // Receive Password Send (Packet ID 3)
    $packet = readPacket($client);
    if (!$packet || $packet['id'] !== 3) return disconnect($client);

    // Send World Info (Packet ID 7)
    $worldInfo = getWorldInfo();
    echo "Sent World Info (Packet ID 7)\n";
    writePacket($client, 7, $worldInfo);

    // Receive Player Info (Packet ID 4)
    $packet = readPacket($client);
    if (!$packet || $packet['id'] !== 4) return disconnect($client);

    // Send Player Slot Assignment (Packet ID 8)
    echo "Sent Player Slot Assignment (Packet ID 8)\n";
    writePacket($client, 8, chr(0)); // Assign slot 0

    // Send Player Join (Packet ID 9)
    echo "Sent Player Join (Packet ID 9)\n";
    writePacket($client, 9, chr(0)); // Join message

    echo "Handshake complete. Client should now be in world loading phase.\n";
    sleep(5); // keep connection open briefly
    echo "No data received or client disconnected.\nConnection closed.\n";
    fclose($client);
}

function parseVersion($data) {
    return explode("\0", $data)[0]; // version string like "Terraria279"
}

function getWorldInfo() {
    // This is a minimal fake world info payload. Adjust as needed.
    return pack('fVCa256Va256VCCC',
        13500.0,          // Time
        1,                // Daytime (1 = day)
        0,                // Moon phase
        'TestWorld',      // World name (null-terminated)
        12345,            // World ID
        'WorldSeed123',   // World seed (null-terminated)
        4200,             // World gen version
        0, 0, 0           // Difficulty, isHardMode, invasionType
    );
}

function readPacket($client) {
    $header = fread($client, 3);
    if (strlen($header) < 3) return false;

    $length = unpack('v', substr($header, 0, 2))[0];
    $id = ord($header[2]);
    $data = fread($client, $length - 1);

    return ['id' => $id, 'data' => $data];
}

function writePacket($client, $id, $data) {
    $length = strlen($data) + 1;
    $packet = pack('v', $length) . chr($id) . $data;
    fwrite($client, $packet);
}

function disconnect($client) {
    echo "Client disconnected or invalid packet.\n";
    fclose($client);
}
