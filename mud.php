<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Menking\Mud\Core\Player;
use Menking\Mud\Core\Login;
use Menking\Mud\Core\Server;
use Menking\Mud\Core\World;
use Menking\Mud\Core\Event\Event;

date_default_timezone_set('America/New_York');

require_once('vendor/autoload.php');

$log = new Logger('Core');
$log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// load our .env file into $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server = new Server($_ENV['SERVER_ADDR'], $_ENV['SERVER_PORT']);
$server->registerHandler("event_handler");

try {
	$server->start();

	$server->run();

	echo "Server has returned from run()\n";
}
catch(Exception $e) {
	$log->emergency("Exception creating server: " . $e->getMessage());
	die();
}

function event_handler(Event $event) {
	global $log;
	
	switch($event->type) {
		case Event::SERVER_START_EVENT:
			$log->info("Server started on {$event->server->ip()}:{$event->server->port()}");
			break;
		default:
			echo "Got unknown event: " . $event->type . "\n";

	}
}
