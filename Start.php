<?php
require_once __DIR__ . '/src/Internal/Packet.php';
require_once __DIR__ . '/src/TerrariaServer.php';

use TerrariaServer\TerrariaServer\Server;

$server = new Server();
$server->run();
