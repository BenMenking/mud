<?php

date_default_timezone_set('America/New_York');

require_once('vendor/autoload.php');

// this contains all of our TELNET protcol related stuff
require_once('telnet.inc.php');
require_once('class.mudserver.php');

// load our .env file into $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// create the socket we will be using
$server_sock = socket_create(AF_INET, SOCK_STREAM, 0);

echo "[SERVER] initializing... ";

// problems, bail out
if( !$server_sock ) { die("Could not create socket\n"); }

// now we need to bind the port we want to listen to for new connections
// and incoming data from clients
$bind = @socket_bind($server_sock, $_ENV['SERVER_ADDR'], $_ENV['SERVER_PORT']);

// problems, bail out
if( !$bind ) { die("Could not bind to {$_ENV['SERVER_ADDR']}:{$_ENV['SERVER_PORT']}!\n"); }

// more problems.  another process must have bound to this port before us
if( !socket_listen($server_sock) ) die("Could not listen on {$_ENV['SERVER_ADDR']}:{$_ENV['SERVER_PORT']}!\n");

echo "running on {$_ENV['SERVER_ADDR']}:{$_ENV['SERVER_PORT']}\n\n";

$started = microtime(true);
$timer = microtime(true);

$questions = new Questions();
$world = new World($_ENV['SERVER_WORLD']);
$clients = new Clients();

// game loop
while(true) {
	/* resolve network activity */
	/* resolve outgoing messaging to clients */
	/* resolve client state/last message */
	
	$read = [$server_sock];
	$write = null;
	$except = null;

	// if we have data to write to a client, we need to add that client's socket to the $write
	// and the socket_select() will tell us if we can write without blocking
	//
	foreach($clients->all() as $client) {
		if( $client->hasMessages() ) {
			$write[] = $client->socket();
		}

		// always add each client to the read
		$read[] = $client->socket();
	}

	// if we have data to write to a client, we need to add that client's socket to the $write
	// and the socket_select() will tell us if we can write without blocking
	//
	/*
	foreach($client_meta as $socket=>$client) {
		if( isset($client['player']) && $client['player']->hasMessages() ) {
			$write[] = $socket;
			
			// can't set sockets for reading and writing, causes socket_select() to hang
			if( in_array($socket, $read) ) {
				unset($read[$socket]);
			}
		}

		if( isset($client['queued_messages']) && count($client['queued_messages']) > 0 ) {
			$write[] = $socket;
			
			// can't set sockets for reading and writing, causes socket_select() to hang
			if( in_array($socket, $read) ) {
				unset($read[$socket]);
			}			
		}
	}
	*/
	
	$num_changed = socket_select($read, $write, $except, 5);
	
	if( $num_changed === false ) die("socket_select() failed, reason: " .
        socket_strerror(socket_last_error()) . "\n");

	// a socket signaled that a change was made.. could be a connect, read or disconnect
	//
	if( $num_changed > 0 ) {
		// handle new connections
		//
		if( !empty($read) && in_array($server_sock, $read) ) {
			$new_client = new Client(socket_accept($server_sock));
			$new_client->world($world);
			$new_client->beginAuthentication();

			$clients->add($new_client);

			// need to remove $server_sock from $read, otherwise this will cause issues
			// later in the code
			$key = array_search($server_sock, $read);
			unset($read[$key]);
		}

		// if there are any remaining sockets in $read, process them
		//
		if( !empty($read) ) {
			foreach($read as $read_sock) {
				$client = $clients->getBySocket($read_sock);

				$data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

				if( $data === false ) {
					// client disconnected, cleanup
					if( $client->player()->authenticated() ) {
						$client->save();
						$world->removePlayer($client->player());
					}
					$client->disconnect();
					unset($client);
				}
				else {
					$client->addCommandBuffer($data);
				}
			}
		}
		
		if( !empty($write) && count($write) > 0 ) {
			foreach($write as $write_sock) {
				$client = $clients->getBySocket($write_sock);

				@socket_write($write_sock, $client->popMessage());
			}
		}
	}

	//
	// Let clients process commands and such
	//
	foreach($clients->all() as $client) {
		$client->execute();
	}

	
	/* resolve client state/last message */
	$dispose = array();
	/*
	echo "[SERVER] AT TOP OF CLIENT_META LOOP\n";

	foreach($client_meta as $socket=>&$client) {
		echo "[SERVER] Client resource ID: " . $socket . "\n";

		//A command was entered by the client, process it

		if( !empty($client['queued_commands']) ) {
			$cmd = array_shift($client['queued_commands']);
	
			if( $client['state'] == 'new' ) {  // socket just setup and we don't have a Player attached to this client
				switch($client['question']) {
					case "login-prompt":
						// see if player exists
						try {
							$player = Player::load($cmd);
							$client['potential_player'] = $player;
							$secret_question = $questions->byId("authenticate-user");
							//$client['player']->sendMessage($secret_question['message']);
							$client['queued_messages'][] = $secret_question['message'];
							$client['question'] = "authenticate-user";
						}
						catch(Exception $e) {
							$not_found_question = $questions->byId("start-registration");
							//$client['player']->sendMessage($not_found_question['message']);
							$client['queued_messages'][] = $not_found_question['message'];
							$client['question'] = "start-registration";
							$client['create'] = ['username'=>$cmd];
						}
						break;
					case "start-registration": // create user
						$secret_question = $questions->byId("select-race");
						//$client['player']->sendMessage($secret_question['message']);
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "select-race";
					break;
					case "select-race":
						$secret_question = $questions->byId("enter-password-1");
						//$client['player']->sendMessage($secret_question['message']);
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "enter-password-1";
						$client['create']['race'] = $cmd;
					break;
					case "enter-password-1":
						$secret_question = $questions->byId("enter-password-2");
						//$client['player']->sendMessage($secret_question['message']);
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "enter-password-2";
						$client['create']['password'] = $cmd;
					break;
					case "enter-password-2":
						if( $cmd === $client['create']['password'] ) {
							$question = $questions->byId("enter-email");
							//$client['player']->sendMessage($question['message']);
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "enter-email";
						}
						else {
							//$client['player']->sendMessage("Sorry, passwords did not match.  Please re-enter\r\n");
							$client['queued_messages'][] = "Sorry, passwords did not match.  Please re-enter\r\n";
							$question = $questions->byId("enter-password-1");
							//$client['player']->sendMessage($question['message']);
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "enter-password-1";
							unset($client['create']['password']);
						}
					break;
					case "enter-email":
						if( ($cmd = filter_var($cmd, FILTER_VALIDATE_EMAIL)) !== false ) {
							$u = Player::create(
								$client['create']['username'],  
								$client['create']['race'], 
								[
									'strength'=>7,
									'constitution'=>7,
									'dexerity'=>7,
									'wisdom'=>7, 
									'charisma'=>7,
									'intelligence'=>7
								], 
								$client['create']['password'], $cmd, $world->getSpawn()
							);
							$u->save();

							$client['state'] = 'playing';
							$client['player'] = $u;
							$client['player']->setRoom($world->getSpawn());
							$client['input'] = new InputHandler($client['player'], $world);
							$world->addPlayer($client['player']);
							unset($client['question']);
						}
						else {
							//$client['player']->sendMessage("That do not appear to be a valid email address.  Please re-enter.\r\n");
							$client['queued_messages'][] = "That do not appear to be a valid email address.  Please re-enter.\r\n";
							$question = $questions->byId("enter-email");
							//$client['player']->sendMessage($question['message']);
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "enter-email";
						}
					break;
					case "authenticate-user": // password test
						if( $client['potential_player']->authenticate($cmd) ) {
							$client['state'] = 'playing';
							$client['player'] = $client['potential_player'];
							$client['player']->setRoom($world->getSpawn());
							$client['input'] = new InputHandler($client['player'], $world);
							$world->addPlayer($client['player']);

							unset($client['potential_player']);
							unset($client['question']);
							echo "[{$client['peername']}:{$client['peerport']}] Player {$client['player']->name()} logged in\n";
							$client['player']->sendMessage(Terminal::BOLD . Terminal::LIGHT_WHITE 
								. "WELCOME {$client['player']->name()}\r\n\r\n" . Terminal::RESET);		
						}
						else {
							$client['player']->sendMessage("Sorry, the secret phrase was incorrect.\r\n\r\n");
							$question = $questions->byId("login-prompt");
							$client['player']->sendMessage($question['message']);
							$client['question'] = "login-prompt";
						}
					break;
					default: 
						echo "[{$client['peername']}:{$client['peerport']}] Question did not have an ID\n";
				}
			}	
			else if( $client['state'] == 'playing') {
				if( isset($client['question']) ) {
					echo "[SERVER] We asked a questino but have not implemented this yet\n";
				}
				else {
					$action = $client['input']->execute($cmd);

					echo "[SERVER] Got action with instance of " . get_class($action) . "\n";

					switch(true) {
						case $action instanceof QuitCommand: // special command
							$client['player']->save();
							$world->removePlayer($client['player']);

							unset($client_meta[$socket]);
							$key = array_search($socket, $clients);
							unset($clients[$key]);

							socket_close($socket);

							echo "[{$client['peername']}:{$client['peerport']}] Requesting to quit\n";
						break;
						case $action instanceof LookCommand:
						case $action instanceof MoveCommand:
						case $action instanceof WhoCommand:
						case $action instanceof CommCommand:
							$client['player']->sendMessage($client['player']->performAction($action));
						break;
						default:
							echo "[{$client['peername']}:{$client['peerport']}] Command not found: {$action->argv(0)}\n";
							$client['player']->sendMessage("Sorry, command '{$action->argv(0)}' not recognized\r\n");
					}
				}
			}		
			else {
				echo "[SERVER] I don't know how we got here\n";
			}

			if( isset($client['player']) ) {
				$client['player']->sendMessage($client['player']->prompt());
			}
		}
	}
	
	//echo "[SERVER] " . print_r($client_meta, true) . "\n";

	// remove any clients that 'quit'
	foreach($dispose as $id) {
		echo "[SERVER] disposing of resource id $id\n";

		$key = array_search($read_sock, $clients);

		$world->removePlayer($client_meta[$id]['player']);
		$client_meta[$id]['player']->save();

		unset($client_meta[$id]);
        unset($clients[$key]);
	}
	
	*/
	$en = microtime(true);
	
	if( $en - $timer > 10 ) {
		echo "[SERVER] There are " . number_format($world->countPlayers(), 0) . " players connected\n";
		$timer = $en;
	}
}

socket_close($server_sock);

/*
function read_stream($data, &$terms) {
	global $command_descriptions, $option_descriptions;
	
	$message = '';
	
	for($a = 0; $a < strlen($data); ) {

		if( ord($data[$a]) == 0xff && ord($data[$a+1]) == 0xff ) {
			// special case where 0xFF is padded
			$message .= ord(0xFF);
			$a += 2;
		}
		else if( ord($data[$a]) == 0xff ) {
			$a++;	
			$command = $command_descriptions[ord($data[$a])];
			$a++;
			$option = $option_descriptions[ord($data[$a])];
			$a++;
			
			$terms[] = array($command=>$option);
		}
		else {
			$message .= ($data[$a]);
			$a++;
		}
	}
	
	return $message;
}
*/
function hex_dump($data, $newline="\n")
{
  static $from = '';
  static $to = '';

  static $width = 16; # number of bytes per line

  static $pad = '.'; # padding for non-visible characters

  if ($from==='')
  {
    for ($i=0; $i<=0xFF; $i++)
    {
      $from .= chr($i);
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
    }
  }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

  $offset = 0;
  foreach ($hex as $i => $line)
  {
    echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
    $offset += $width;
  }
}

?>