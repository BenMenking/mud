<?php

date_default_timezone_set('America/New_York');

require_once('vendor/autoload.php');
require_once('classes/autoload.php');

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

$world = World::getInstance($_ENV['SERVER_WORLD']);
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

		foreach($changes as $type=>$change) {
			foreach($change as $uuid) {
				if( $type == 'new' ) {
					$logins[$uuid] = new Login($uuid);
					$server->queueMessage($uuid, $logins[$uuid]->begin());
				}
				else if( $type == 'data' ) {
					if( isset($logins[$uuid]) ) {
						$answer = $server->getNextMessage($uuid);

						if( strlen($answer) == 0 ) continue;

						$response = $logins[$uuid]->processAnswer($answer);
						if( $response['completed'] === true ) {
							echo "Completed: true; " . json_encode($response) . "\n";
							if( $response['state'] == 'login-prompt' ) {
								if( Player::exists($response['data']['login-prompt']) ) {
									$server->queueMessage($uuid, $logins[$uuid]->begin('authenticate-user', true));
								}
								else {
									$server->queueMessage($uuid, $logins[$uuid]->begin('start-registration', true));
								}
							}
							else if( $response['state'] == 'authenticate-user') {
								// log player in
								try {
									$p = Player::load($response['data']['login-prompt']);
									if( $p->authenticate($response['data']['authenticate-user']) ) {
										$p->addTag('uuid', $uuid);
										$p->addCommand('look');
										$world->addPlayer($p);
										unset($logins[$uuid]);
										echo "[SERVER] Player {$p->name()} logged in\n";
									}
									else {
										$server->queueMessage($uuid, $logins[$uuid]->begin());
									}
								}
								catch(Exception $e) {
									$server->queueMessage($uuid, $logins[$uuid]->begin());
								}
							}
							else if( $response['state'] == 'enter-email' ) {
								echo "[SERVER] need to write new user implementation!\n";
								// attempt to create new user
								echo "response: " . json_encode($response['data']) . "\n";
								$p = Player::create($response['data']['login-prompt'],
									$response['data']['select-race'],
									[],
									$response['data']['enter-password-1'],
									$response['data']['enter-email'],
									$world->getSpawn()
								);
								$p->addTag('uuid', $uuid);
								$p->addCommand('look');
								$world->addPlayer($p);

								unset($logins[$uuid]);
							}
						}
						else {
							$server->queueMessage($uuid, $response['data']);
						}
					}
					else {
						$player = $world->getPlayerWithTag('uuid', $uuid);						
						
						if( !is_null($player) ) {
							$command = $server->getNextMessage($uuid);

							if( strlen($command) > 0 ) {
								$player->addCommand($command);
							}	
						}
						else {
							echo "[SERVER] Error: got a null player, UUID is $uuid\n";
						}
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
	}

	$en = microtime(true);
	
	if( $en - $timer > 10 ) {
		echo "[SERVER] There are " . number_format($world->countPlayers(), 0) . " players connected\n";
		echo "[SERVER] Current: " . number_format(round(memory_get_usage() / 1024), 0) . "MB\tPeak: " 
			. number_format(round(memory_get_peak_usage() / 1024), 0) . "MB\n";
		$timer = $en;
	}
}

socket_close($server_sock);

?>