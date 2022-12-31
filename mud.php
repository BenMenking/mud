<?php

use Menking\Mud\Core\GameEngine;

date_default_timezone_set('America/New_York');

require_once('vendor/autoload.php');

// load our .env file into $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$eng = new GameEngine($_ENV['SERVER_ADDR'], $_ENV['SERVER_PORT']);
$eng->run();

echo "Game engine has quit\n";
