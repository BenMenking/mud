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

// some variables to hold important information about our clients
$clients = array($server_sock);
$client_meta = array();

echo "running on {$_ENV['SERVER_ADDR']}:{$_ENV['SERVER_PORT']}\n\n";

$started = microtime(true);
$timer = microtime(true);

$questions = new Questions();
$world = new World($_ENV['SERVER_WORLD']);

// game loop
while(true) {
	/* resolve network activity */
	/* resolve outgoing messaging to clients */
	/* resolve client state/last message */
	
	$read = $clients;
	$write = null;
	$except = null;
	
	// if we have data to write to a client, we need to add that client's socket to the $write
	// and the socket_select() will tell us if we can write without blocking
	//
	foreach($client_meta as $client) {
		if( !empty($client['queued_messages']) ) {
			$write[] = $client['socket'];
			
			// can't set sockets for reading and writing, causes socket_select() to hang
			if( in_array($client['socket'], $read) ) {
				unset($read[$client['socket']]);
			}
		}
	}
	
	$num_changed = socket_select($read, $write, $except, 5);
	
	if( $num_changed === false ) die("socket_select() failed, reason: " .
        socket_strerror(socket_last_error()) . "\n");

	// a socket signaled that a change was made.. could be a connect, read or disconnect
	//
	if( $num_changed > 0 ) {
		// if the socket we got is not in our $clients array, it's a new connect
		//
		if( !empty($read) && in_array($server_sock, $read) ) {
			$clients[] = $new_sock = socket_accept($server_sock);

            socket_getpeername($new_sock, $ip);
			echo "[SERVER] setup socket for $new_sock\n";
			$client_meta[(int)$new_sock] = array('state'=>'new', 'peername'=>$ip, 'queued_commands'=>array(), 'socket'=>$new_sock,
				'queued_messages'=>array());
				
			$key = array_search($server_sock, $read);
			unset($read[$key]);
            echo "[$ip] connected\n";	

			$client_meta[(int)$new_sock]['queued_messages'][] = file_get_contents('intro.txt');
			$login_question = $questions->byId("login-prompt");
			$client_meta[(int)$new_sock]['queued_messages'][] = $login_question['message'] . " ";
			$client_meta[(int)$new_sock]['question'] = "login-prompt";
		}

		// there is data we can read from a socket, so let's do that.  could also indicate a disconnect
		//
		if( !empty($read) ) {
			foreach($read as $read_sock) {
				 $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);
				
				// check if the client is disconnected
				if ($data === false) {
					// remove client for $clients array
					echo "[{$client_meta[(int)$read_sock]['peername']} disconnected\n";
					$key = array_search($read_sock, $clients);
					unset($clients[$key]);
					unset($client_meta[(int)$read_sock]);					
					continue;
				}

				$terms = array();
				
				$message_t = explode("\r\n", read_stream($data, $terms));
				$message = array();
				
				foreach($message_t as $data) {
					if( strlen(trim($data)) > 0 ) {
						$message[] = trim($data);
					}
				}
				
				if( !empty($terms) ) {
					$client_meta[(int)$read_sock]['ntv'] = $terms;
				}
				
				socket_getpeername($read_sock, $ip);
				
				if( count($message) > 0 )
					echo "[$ip] said '" . implode(',', $message) . "'\n";
				
				// add this command to the client's queue
				foreach($message as $msg)
					$client_meta[(int)$read_sock]['queued_commands'][] = $msg;
			}
		}
		
		// we are able to write messages out to sockets without blocking
		//
		if( !empty($write) && count($write) > 0 ) {
			foreach($write as $write_sock) {
				if( !empty($client_meta[(int)$write_sock]['queued_messages']) ) {
					$msg = array_shift($client_meta[(int)$write_sock]['queued_messages']);
				
					socket_write($write_sock, $msg);
				}
			}
		}
	}
	
	/* resolve outgoing message(s) to client(s) */
	
	
	/* resolve client state/last message */
	$dispose = array();
	
	foreach($client_meta as $id=>&$client) {
		$socket = $client['socket'];
		
		/**
		 * A command was entered by the client, process it
		 */
		if( !empty($client['queued_commands']) ) {
			$cmd = array_shift($client['queued_commands']);
	
			if( $client['state'] == 'new' ) {  // socket just setup and we don't have a User attached to this client
				switch($client['question']) {
					case "login-prompt":
						// see if user exists
						try {
							$user = User::load($cmd);
							$client['potential_user'] = $user;
							$secret_question = $questions->byId("authenticate-user");
							$client['queued_messages'][] = $secret_question['message'];
							$client['question'] = "authenticate-user";
						}
						catch(Exception $e) {
							//$client['queued_messages'][] = "That name has not been recorded in the Royal Register!\r\n\r\n";
							$not_found_question = $questions->byId("start-registration");
							$client['queued_messages'][] = $not_found_question['message'];
							$client['question'] = "start-registration";
							$client['create'] = ['username'=>$cmd];
						}
						break;
					case "start-registration": // create user
						//echo "We'd like to create a user, but it's not implemented yet\n";
						//$client['queued_messages'][] = "Sorry, the quill has run out of ink and the name cannot be registered.\r\n\r\n";
						$secret_question = $questions->byId("select-race");
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "select-race";
					break;
					case "select-race":
						$secret_question = $questions->byId("select-class");
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "select-class";
						$client['create']['race'] = $cmd;
					break;
					case "select-class":
						$secret_question = $questions->byId("enter-password-1");
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "enter-password-1";
						$client['create']['class'] = $cmd;
					break;
					case "enter-password-1":
						$secret_question = $questions->byId("enter-password-2");
						$client['queued_messages'][] = $secret_question['message'];
						$client['question'] = "enter-password-2";
						$client['create']['password'] = $cmd;
					break;
					case "enter-password-2":
						if( $cmd === $client['create']['password'] ) {
							$question = $questions->byId("enter-email");
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "enter-email";
						}
						else {
							$client['queued_messages'][] = "Sorry, those secret phrases do not match.  Please re-enter.\r\n";
							$question = $questions->byId("enter-password-1");
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "enter-password-1";
							unset($client['create']['password']);
						}
					break;
					case "enter-email":
						if( ($cmd = filter_var($cmd, FILTER_VALIDATE_EMAIL)) !== false ) {
							$u = User::create(
								$client['create']['username'], 
								$client['create']['class'], 
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
							$client['user'] = $u;
							$client['state'] = "playing";
						}
						else {
							$client['queued_messages'][] = "That do not appear to be a valid email address.  Please re-enter.\r\n";
							$question = $questions->byId("enter-email");
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "enter-email";
						}
					break;
					case "authenticate-user": // password test
						if( $client['potential_user']->authenticate($cmd) ) {
							$client['state'] = 'playing';
							$client['user'] = $client['potential_user'];
							$client['user']->setRoom($world->getSpawn());
							$client['input'] = new InputHandler($client['user'], $world);
							unset($client['potential_user']);
							unset($client['question']);
							echo "[{$client['peername']}] User {$client['user']->name()} logged in\n";
							$client['queued_messages'][] = "WELCOME {$client['user']->name()}\r\n\r\n";		
						}
						else {
							$client['queued_messages'][] = "Sorry, the secret phrase was incorrect.\r\n\r\n";
							$question = $questions->byId("login-prompt");
							$client['queued_messages'][] = $question['message'];
							$client['question'] = "login-prompt";
						}
					break;
					default: 
						echo "[{$client['peername']}] Question did not have an ID\n";
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
						case $action instanceof QuitCommand:
							socket_close($socket);
							$dispose[] = $id;
							echo "[{$client['peername']}] Requesting to quit\n";
						break;
						case $action instanceof LookCommand:
						case $action instanceof MoveCommand:
							$client['queued_messages'][] = $client['user']->performAction($action);
						break;
						default:
							echo "[{$client['peername']}] Command not found: {$action->argv(0)}\n";
							$client['queued_messages'][] = "Sorry, command '{$action->argv(0)}' not recognized\r\n";	
					}
				}
			}		

			if( isset($client['user']) ) {
				$client['queued_messages'][] = $client['user']->prompt();
			}
		}
	}
	
	// remove any clients that 'quit'
	foreach($dispose as $id) {
		unset($client_meta[$id]);
        $key = array_search($read_sock, $clients);
        unset($clients[$key]);
	}
	
	$en = microtime(true);
	
	if( $en - $timer > 15 ) {
		echo "[SERVER] There are " . number_format(count($clients) - 1, 0) . " client(s) connected\n";
		$timer = $en;
	}
	
	//echo json_encode($client_meta) . "\n\n";
	//sleep(2);
}

socket_close($server_sock);

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