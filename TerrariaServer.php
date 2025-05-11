<?php

// Create a TCP socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, '0.0.0.0', 7777);
socket_listen($socket);
echo "Terraria TCP Server started on port 7777...\n";

while (true) {
    $client = socket_accept($socket);
    echo "Client connected.\n";

    // Read the first 2 bytes (packet length)
    $lengthBytes = socket_read($client, 2);
    $length = unpack('v', $lengthBytes)[1]; // Little-endian

    // Read the packet ID and payload
    $packet = socket_read($client, $length);
    $packetId = ord($packet[0]);

    if ($packetId === 1) {
        echo "Received Connect Request (Packet ID 1)\n";

        // Extract version string
        $version = '';
        for ($i = 1; $i < strlen($packet); $i++) {
            if ($packet[$i] === "\0") break;
            $version .= $packet[$i];
        }
        echo "Client version: $version\n";

        // Send back Packet ID 2 (Send Password Request)
        $packetId = chr(2);
        $payload = $packetId . chr(0); // 0 = no password required
        $length = pack('v', strlen($payload));
        $response = $length . $payload;

        socket_write($client, $response);
        echo "Sent Password Request (Packet ID 2)\n";
    } else {
        echo "Unexpected packet ID: $packetId\n";
    }

    // Handle further communication in a separate loop for clarity
    while (true) {
        $data = socket_read($client, 1024);
        if (!$data) break; // Break if no data

        $packetId = ord($data[2] ?? "\x00");

        if ($packetId === 3) {
            echo "Received Password Response (Packet ID 3)\n";

            // Send World Info packet (Packet ID 7)
            $worldName = "PHP World";
            $worldId = 123456789; // Just a random ID

            $payload = "";
            $payload .= pack("V", $worldId); // world ID (uint32)
            $payload .= chr(0); // time
            $payload .= chr(0); // day
            $payload .= chr(0); // moon phase
            $payload .= chr(0); // blood moon
            $payload .= chr(strlen($worldName)); // world name length
            $payload .= $worldName; // world name
            $payload .= str_repeat(chr(0), 100); // fake filler data for the rest

            $packet = pack("v", strlen($payload) + 3) . chr(7) . $payload;
            socket_write($client, $packet);
            echo "Sent fake World Info (Packet ID 7)\n";
        }
    }

    socket_close($client);
    echo "Connection closed.\n\n";
}
?>
