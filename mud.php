<?php

date_default_timezone_set('America/New_York');

require_once('vendor/autoload.php');

// this contains all of our TELNET protcol related stuff
require_once('telnet.inc.php');
require_once('class.mudserver.php');

// load our .env file into $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server = new Server($_ENV['SERVER_ADDR'], $_ENV['SERVER_PORT']);

try {
	$server->connect();
	echo "[SERVER] Running on {$server->ip()}:{$server->port()}\n\n";
}
catch(Exception $e) {
	die("Exception creating server: " . $e->getMessage() . "\n\n");
}

$world = new World($_ENV['SERVER_WORLD']);
$logins = [];
$timer = microtime(true);

// game loop
while(true) {
	// if we have data to write to a client, we need to add that client's socket to the $write
	// and the socket_select() will tell us if we can write without blocking
	//
	foreach($world->getPlayers() as $player) {
		while($player->hasMessages()) {
			$server->queueMessage($player->getTag('uuid'), $player->getMessage());
		}
	}

	try {
		$changes = $server->select();

		echo "[SERVER] changes: " . json_encode($changes) . "\n";

		foreach($changes as $type=>$change) {
			foreach($change as $uuid) {
				if( $type == 'new' ) {
					$logins[$uuid] = new Login($uuid);
					$server->queueMessage($uuid, $logins[$uuid]->begin());
				}
				else if( $type == 'data' ) {
					if( isset($logins[$uuid]) ) {
						echo "found a LOGIN object\n";
						$response = $logins[$uuid]->processAnswer($server->getNextMessage($uuid));
						if( $response === true ) {
							// create player
							echo "need to CREATE player\n";
						}
						else {
							echo "sending back response: $response\n";
							$server->queueMessage($uuid, $response);
						}
					}
					else {
						echo "found a REGULAR player response\n";
						$player = $world->getPlayerWithTag('uuid', $uuid);
						$player->sendMessage($server->getNextMessage($uuid));
					}
				}
				else if( $type == 'disconnected' ) {
					if( isset($logins[$uuid]) ) {
						echo "[SERVER] Client disconnected\n";
						unset($logins[$uuid]);
					}
					else {
						$player = $world->getPlayerWithTag('uuid', $uuid);
						$player->save();
						$world->removePlayer($player);
					}
				}
			}
		}
	}
	catch(Exception $e) {
		die("[SERVER] Exception on select: " . $e->getMessage() . "\n\n");
	}
	
	
	//
	// Let clients process commands and such
	//
	foreach($world->getPlayers() as $player) {
		$status = $player->execute();

		if( $status == 'authenticated' ) {
			$world->addPlayer($client->player());
		}
	}

	$en = microtime(true);
	
	if( $en - $timer > 10 ) {
		echo "[SERVER] There are " . number_format($world->countPlayers(), 0) . " players connected\n";
		$timer = $en;
	}
}

socket_close($server_sock);

?>