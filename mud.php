<?php

date_default_timezone_set('America/New_York');

// this contains all of our TELNET protcol related stuff
require('telnet.inc.php');

// create the socket we will be using
$server_sock = socket_create(AF_INET, SOCK_STREAM, 0);

// problems, bail out
if( !$server_sock ) { die("Could not create socket\n"); }

// now we need to bind the port we want to listen to for new connections
// and incoming data from clients
$bind = socket_bind($server_sock, '0.0.0.0', '23');

// problems, bail out
if( !$bind ) { die("Could not bind to port 23!\n"); }

// more problems.  another process must have bound to this port before us
if( !socket_listen($server_sock) ) die("Could not listen on port 23!\n");

// some variables to hold important information about our clients
$clients = array($server_sock);
$client_meta = array();

echo "Bound server to port 23\n";

$started = microtime(true);
$timer = microtime(true);

// game loop
while(true) {
	/* resolve network activity */
	/* resolve outgoing messaging to clients */
	/* resolve client state/last message */
	
	$read = $clients;
	$write = null;
	$except = null;
	
	// if we have data to write to a client, we want to see when a write would not block
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

	if( $num_changed > 0 ) {
		if( !empty($read) && in_array($server_sock, $read) ) {
			echo "new client\n";
			// accept new client
			$clients[] = $new_sock = socket_accept($server_sock);

            socket_getpeername($new_sock, $ip);
			echo "setup: $new_sock\n";
			$client_meta[$new_sock] = array('state'=>'login', 'username'=>'',
				'peername'=>$ip, 'queued_commands'=>array(), 'socket'=>$new_sock,
				'queued_messages'=>array());
				
			$key = array_search($server_sock, $read);
			unset($read[$key]);
            echo "New client connected: {$ip}\n";	

			$client_meta[$new_sock]['queued_messages'][] = file_get_contents('intro.txt');
			$client_meta[$new_sock]['queued_messages'][] = "What is thy name? ";
		}
		
		if( !empty($read) ) {
			foreach($read as $read_sock) {
				 $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);
				
				// check if the client is disconnected
				if ($data === false) {
					// remove client for $clients array
					$key = array_search($read_sock, $clients);
					unset($clients[$key]);
					
					unset($client_meta[$read_sock]);
					
					echo "client disconnected.\n";
					// continue to the next client to read from, if any
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
					$client_meta[$read_sock]['ntv'] = $terms;
				}
				
				socket_getpeername($read_sock, $ip);
				
				if( count($message) > 0 )
					echo "client said " . implode(',', $message) . "\n";
				
				// add this command to the client's queue
				foreach($message as $msg)
					$client_meta[$read_sock]['queued_commands'][] = $msg;
			}
		}
		
		if( count($write) > 0 ) {
			foreach($write as $write_sock) {
				
				if( !empty($client_meta[$write_sock]['queued_messages']) ) {
					$msg = array_shift($client_meta[$write_sock]['queued_messages']);
				
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
		
		if( !empty($client['queued_commands']) ) {
			$cmd = array_shift($client['queued_commands']);
	
			if( $client['state'] == 'login' ) {
				if( strtoupper($cmd) == 'TEST' ) {
					$client['state'] = 'playing';
					$client['username'] = $cmd;
					$client['prompt'] = '< 0hp 0mn 0mv > ';
					echo "User $cmd logged in\n";
					$client['queued_messages'][] = "WELCOME $cmd\r\n\r\n";
				}
				else {
					$client['queued_messages'][] = "That name does not exist in my database\r\n";
					$client['queued_messages'][] = "What is thy name? ";
				}
			}
			else {
				switch($cmd) {
					case 'quit':
						socket_close($socket);
						$dispose[] = $id;
						echo "client {$client['peername']} requested to quit\n";
						break;
					default:
						$client['queued_messages'][] = "Sorry, I don't know how to '$cmd'\r\n";
						break;
				}
			}
			
			$client['queued_messages'][] = $client['prompt'];
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
		echo "[] There are " . number_format(count($clients) - 1, 0) . " client(s) connected\n";
		$timer = $en;
	}
	
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