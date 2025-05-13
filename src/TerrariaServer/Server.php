<?php
define('SERVER_PASSWORD', 'test'); // Set your desired password here
namespace TerrariaServer\TerrariaServer;

use TerrariaServer\Internal\Packet;

class Server {

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

// Read the password string (first byte = length, then content)
$passwordLength = ord($packet['data'][0]);
$password = substr($packet['data'], 1, $passwordLength);
echo "Received password: '$password'\n";

if ($password !== SERVER_PASSWORD) {
    echo "Incorrect password. Disconnecting.\n";
    // Optionally send kick (Packet ID 2 with non-zero reason byte)
    writePacket($client, 2, chr(1)); // 1 = generic failure
    return disconnect($client);
}

echo "Password correct.\n";
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
    if (empty($data)) return "Unknown";
	$parts = explode("\0",$data);
return isset($parts[0]) ? $parts[0]: "Unknown";
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

function readPacket($socket) {
    $header = fread($socket, 3); // 2 bytes length, 1 byte packet ID
    if ($header === false || strlen($header) < 3) {
        echo "Failed to read packet header.\n";
        return null;
    }

    $unpacked = unpack('vlength/CpacketId', $header);
    $length = $unpacked['length'];
    $packetId = $unpacked['packetId'];

    // Subtract the header bytes (3) since length includes them
    $payloadLength = $length - 3;
    if ($payloadLength < 0) {
        echo "Invalid payload length: $payloadLength\n";
        return null;
    }

    $payload = '';
    if ($payloadLength > 0) {
        $payload = fread($socket, $payloadLength);
        if ($payload === false || strlen($payload) < $payloadLength) {
            echo "Failed to read full payload.\n";
            return null;
        }
    }

    return [
        'id' => $packetId,
        'data' => $payload
    ];
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
}
