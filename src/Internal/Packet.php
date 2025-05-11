<?php
namespace TerrariaServer\Internal;

class Packet {
    public static function readPacket($client) {
        $lengthBytes = socket_read($client, 2);
        if (!$lengthBytes) return null;

        $length = unpack('v', $lengthBytes)[1];
        $packet = socket_read($client, $length);
        if (!$packet) return null;

        return [
            'id' => ord($packet[0]),
            'data' => substr($packet, 1)
        ];
    }

    public static function writePacket($client, int $packetId, string $payload = '') {
        $packetBody = chr($packetId) . $payload;
        $packetLength = pack('v', strlen($packetBody) + 3);
        $packet = $packetLength . $packetBody;
        socket_write($client, $packet);
    }
}
