<?php
define('SERVER_PASSWORD', 'test');

namespace TerrariaServer\TerrariaServer;

use TerrariaServer\Internal\Packet;

class Server {

    public function start(): void {
        $server = stream_socket_server("tcp://0.0.0.0:7777", $errno, $errstr);
        if (!$server) {
            die("Error: $errstr ($errno)");
        }

        echo "Server started on port 7777\n";

        while ($client = @stream_socket_accept($server, -1)) {
            echo "Client connected.\n";
            $this->handleClient($client);
        }
    }

    private function handleClient($client): void {
        echo "Waiting for Connect Request (Packet ID 1)\n";
        $packet = $this->readPacket($client);
        if (!$packet || $packet['id'] !== 1) return $this->disconnect($client);

        echo "Client version: " . $this->parseVersion($packet['data']) . "\n";

        // Send Password Request
        echo "Sent Password Request (Packet ID 2)\n";
        $this->writePacket($client, 2, chr(0));

        // Receive Password
        $packet = $this->readPacket($client);
        if (!$packet || $packet['id'] !== 3) return $this->disconnect($client);

        $passwordLength = ord($packet['data'][0]);
        $password = substr($packet['data'], 1, $passwordLength);
        echo "Received password: '$password'\n";

        if ($password !== SERVER_PASSWORD) {
            echo "Incorrect password. Disconnecting.\n";
            $this->writePacket($client, 2, chr(1));
            return $this->disconnect($client);
        }

        echo "Password correct.\n";
        $this->writePacket($client, 7, $this->getWorldInfo());
        echo "Sent World Info (Packet ID 7)\n";

        $packet = $this->readPacket($client);
        if (!$packet || $packet['id'] !== 4) return $this->disconnect($client);

        $this->writePacket($client, 8, chr(0));
        echo "Sent Player Slot Assignment (Packet ID 8)\n";

        $this->writePacket($client, 9, chr(0));
        echo "Sent Player Join (Packet ID 9)\n";

        echo "Handshake complete. Client should now be in world loading phase.\n";
        sleep(5);
        echo "No data received or client disconnected.\nConnection closed.\n";
        fclose($client);
    }

    private function parseVersion($data): string {
        if (empty($data)) return "Unknown";
        $parts = explode("\0", $data);
        return $parts[0] ?? "Unknown";
    }

    private function getWorldInfo(): string {
        return pack('fVCa256Va256VCCC',
            13500.0, 1, 0, 'TestWorld', 12345, 'WorldSeed123', 4200, 0, 0, 0
        );
    }

    private function readPacket($socket): ?array {
        $header = fread($socket, 3);
        if ($header === false || strlen($header) < 3) {
            echo "Failed to read packet header.\n";
            return null;
        }

        $unpacked = unpack('vlength/CpacketId', $header);
        $payloadLength = $unpacked['length'] - 3;

        if ($payloadLength < 0) {
            echo "Invalid payload length.\n";
            return null;
        }

        $payload = $payloadLength > 0 ? fread($socket, $payloadLength) : '';
        if ($payload === false || strlen($payload) < $payloadLength) {
            echo "Failed to read full payload.\n";
            return null;
        }

        return ['id' => $unpacked['packetId'], 'data' => $payload];
    }

    private function writePacket($client, $id, $data): void {
        $length = strlen($data) + 1;
        $packet = pack('v', $length) . chr($id) . $data;
        fwrite($client, $packet);
    }

    private function disconnect($client): void {
        echo "Client disconnected or invalid packet.\n";
        fclose($client);
    }
}
