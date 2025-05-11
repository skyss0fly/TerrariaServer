<?php

// Create a TCP socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, '0.0.0.0', 7777);
socket_listen($socket);
echo "Terraria TCP Server started on port 7777...\n";

while (true) {
    $client = socket_accept($socket);
    echo "Client connected.\n";

    while (true) {
        // Read the first 2 bytes (packet length)
        $lengthBytes = socket_read($client, 2);
        if (!$lengthBytes) {
            echo "No data received or client disconnected.\n";
            break;
        }

        // Get the length of the packet
        $length = unpack('v', $lengthBytes)[1]; // Little-endian

        // Read the full packet
        $packet = socket_read($client, $length);
        $packetId = ord($packet[0]);

        echo "Received Packet ID: $packetId\n";

        // Handle different packet IDs
        switch ($packetId) {
            case 1: // Connect Request (Packet ID 1)
                echo "Received Connect Request (Packet ID 1)\n";
                // Extract version string
                $version = '';
                for ($i = 1; $i < strlen($packet); $i++) {
                    if ($packet[$i] === "\0") break;
                    $version .= $packet[$i];
                }
                echo "Client version: $version\n";

                // Send Password Request (Packet ID 2)
                $packetId = chr(2);
                $payload = $packetId . chr(0); // 0 = no password required
                $response = pack('v', strlen($payload) + 3) . $payload;
                socket_write($client, $response);
                echo "Sent Password Request (Packet ID 2)\n";
                break;

            case 3: // Password Response (Packet ID 3)
                echo "Received Password Response (Packet ID 3)\n";

                // Send World Info (Packet ID 7)
                $worldName = "PHP World";
                $worldId = 123456789;
                $payload = pack("V", $worldId) . chr(0) . chr(0) . chr(0) . chr(0) . chr(strlen($worldName)) . $worldName . str_repeat(chr(0), 100);
                $response = pack('v', strlen($payload) + 3) . chr(7) . $payload;
                socket_write($client, $response);
                echo "Sent World Info (Packet ID 7)\n";
                break;

            case 8: // Player Slot Assignment (Packet ID 8)
                $playerSlot = 0; // Assign slot
                $payload = pack("V", $playerSlot);
                $response = pack('v', strlen($payload) + 3) . chr(8) . $payload;
                socket_write($client, $response);
                echo "Sent Player Slot Assignment (Packet ID 8)\n";
                break;

            case 9: // Player Join (Packet ID 9)
                $playerId = 1;
                $team = 0;
                $position = pack("f", 0.0); // x position
                $health = pack("f", 100.0); // health
                $payload = $playerId . $team . $position . $health;
                $response = pack('v', strlen($payload) + 3) . chr(9) . $payload;
                socket_write($client, $response);
                echo "Sent Player Join (Packet ID 9)\n";
                break;

            case 10: // Player Data Update (Packet ID 10)
                $playerPosition = pack("f", 10.0); // Fake x position
                $playerHealth = pack("f", 100.0);  // Fake health
                $payload = $playerPosition . $playerHealth;
                $response = pack('v', strlen($payload) + 3) . chr(10) . $payload;
                socket_write($client, $response);
                echo "Sent Player Data Update (Packet ID 10)\n";
                break;

            case 13: // Inventory Sync (Packet ID 13)
                $itemId = 123;
                $amount = 1;
                $slot = 0;
                $payload = pack("V", $itemId) . pack("V", $amount) . pack("V", $slot);
                $response = pack('v', strlen($payload) + 3) . chr(13) . $payload;
                socket_write($client, $response);
                echo "Sent Inventory Data (Packet ID 13)\n";
                break;

            case 16: // Chat Message (Packet ID 16)
                $chatMessage = "Hello, world!";
                $payload = pack("v", strlen($chatMessage)) . $chatMessage;
                $response = pack('v', strlen($payload) + 3) . chr(16) . $payload;
                socket_write($client, $response);
                echo "Sent Chat Message (Packet ID 16)\n";
                break;

            default:
                echo "Unexpected Packet ID: $packetId\n";
                break;
        }
    }

    socket_close($client);
    echo "Connection closed.\n\n";
}

socket_close($socket);
?>
