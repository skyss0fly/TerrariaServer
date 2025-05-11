<?php
namespace TerrariaServer\TerrariaServer;

use TerrariaServer\Internal\Packet;

class Server {
    public function run() {
        // Create a TCP socket
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, '0.0.0.0', 7777);
        socket_listen($socket);
        echo "Terraria TCP Server started on port 7777...\n";

        while (true) {
            $client = socket_accept($socket);
            echo "Client connected.\n";

            while (true) {
                $packet = Packet::readPacket($client);
                if (!$packet) {
                    echo "No data received or client disconnected.\n";
                    break;
                }

                $packetId = $packet['id'];
                $data = $packet['data'];

                echo "Received Packet ID: $packetId\n";

                switch ($packetId) {
                    case 1: // Connect Request
                        echo "Received Connect Request (Packet ID 1)\n";
                        $version = explode("\0", $data)[0];
                        echo "Client version: $version\n";

                        // Send Password Request
                        Packet::writePacket($client, 2, chr(0));
                        echo "Sent Password Request (Packet ID 2)\n";
                        break;

                    case 3: // Password Response
                        echo "Received Password Response (Packet ID 3)\n";

                        // Send World Info
                        $worldName = "PHP World";
                        $worldId = 123456789;
                        $payload = pack("V", $worldId) . chr(0) . chr(0) . chr(0) . chr(0) .
                                chr(strlen($worldName)) . $worldName . str_repeat(chr(0), 100);
                        Packet::writePacket($client, 7, $payload);
                        echo "Sent World Info (Packet ID 7)\n";
                        break;

                    case 8: // Player Slot Assignment
                        $payload = pack("V", 0);
                        Packet::writePacket($client, 8, $payload);
                        echo "Sent Player Slot Assignment (Packet ID 8)\n";
                        break;

                    case 9: // Player Join
                        $payload = chr(1) . chr(0) . pack("f", 0.0) . pack("f", 100.0);
                        Packet::writePacket($client, 9, $payload);
                        echo "Sent Player Join (Packet ID 9)\n";
                        break;

                    case 10: // Player Data Update
                        $payload = pack("f", 10.0) . pack("f", 100.0);
                        Packet::writePacket($client, 10, $payload);
                        echo "Sent Player Data Update (Packet ID 10)\n";
                        break;

                    case 13: // Inventory Sync
                        $payload = pack("V", 123) . pack("V", 1) . pack("V", 0);
                        Packet::writePacket($client, 13, $payload);
                        echo "Sent Inventory Data (Packet ID 13)\n";
                        break;

                    case 16: // Chat Message
                        $chatMessage = "Hello, world!";
                        $payload = pack("v", strlen($chatMessage)) . $chatMessage;
                        Packet::writePacket($client, 16, $payload);
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
    }
}
