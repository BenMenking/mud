<?php

require('telnet.inc.php');

$server_sock = socket_create(AF_INET, SOCK_STREAM, 0);

if( !$server_sock ) { die("Could not create socket\n"); }

$bind = socket_bind($server_sock, '0.0.0.0', '23');

if( !$bind ) { die("Could not bind to port 23!\n"); }

if( !socket_listen($server_sock) ) die("Could not listen on port 23!\n");

$clients = array($server_sock);
$client_meta = array();

echo "Bound server to port 23\n";

while(true) {
	$read = $clients;
	$write = null;
	$except = null;
	
	$num_changed = socket_select($read, $write, $except, 0);
	
	if( $num_changed === false ) die("socket_select() failed, reason: " .
        socket_strerror(socket_last_error()) . "\n");

	if( $num_changed > 0 ) {
		if( in_array($server_sock, $read) ) {
			// accept new client
			$clients[] = $new_sock = socket_accept($server_sock);

			// need to do some telnet command/options work ehre
			$terms = '';
			
			// we're not going to ECHO
			$terms .= $commands['IAC'] . $commands['WONT'] . $options['ECHO'];
			
			// no encryption
			$terms .= $commands['IAC'] . $commands['WONT'] . $options['ENCRYPT'];
/*
			$data = @socket_read($new_sock, 1024, PHP_NORMAL_READ);
			echo "INITIAL READ: \n" . hex_dump($data) . "\n";
			socket_write($new_sock, $terms);						

			$data = @socket_read($new_sock, 1024, PHP_NORMAL_READ);
			echo "SECONDARY READ: \n" . hex_dump($data) . "\n";
*/
			//socket_write($new_sock, file_get_contents('intro.txt')
			//	. "\r\n\r\n\r\nPlease enter your name: ");
	
            socket_getpeername($new_sock, $ip);
			
			$client_meta[(int)$new_sock] = array('state'=>'login', 'username'=>'',
				'peername'=>$ip);
				
			$key = array_search($server_sock, $read);
			unset($read[$key]);
            echo "New client connected: {$ip}\n";			
		}
		
		foreach($read as $read_sock) {
			 $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);
            
            // check if the client is disconnected
            if ($data === false) {
                // remove client for $clients array
                $key = array_search($read_sock, $clients);
                unset($clients[$key]);
                echo "client disconnected.\n";
                // continue to the next client to read from, if any
                continue;
            }

			$terms = array();
			
			echo hex_dump($data) . "\n\n";
			$message = read($data, $terms);
			echo "MESSAGE: $message\n\n";
			
			socket_getpeername($read_sock, $ip);
			/*
			if( $client_meta[(int)$read_sock]['state'] == 'login' ) {
					echo "Hello, ". ucwords($data) . "\n";
					$client_meta[(int)$read_sock]['username'] = ucwords($data);
					$client_meta[(int)$read_sock]['state'] == 'playing';
					
					socket_write($read_sock, "\r\n$data > ");
			}
			else if( $client_meta[(int)$read_sock]['state'] == 'playing' ) {
				socket_write($read_sock, "\r\n$data > ");
			}
			*/
		}
		
		foreach($write as $write_sock) {
			echo "FD $write_sock wants a write?\n";
		}
	}
}

socket_close($server_sock);

function show_prompt($socket) {
	socket_write($socket, "\nUSER > ");
}

function read($data, &$terms) {
	$message = '';
	
	for($a = 0; $a < strlen($data); $a++ ) {
		if( $data[$a] == 0xff && $data[$a+1] == 0xff ) {
			// special case where 0xFF is padded
			$message .= 0xFF;
			$a++;
		}
		else if( $data[$a] == 0xff ) {
			$command = $data[$a++];
			
			$option = '';
			
			while( $data[$a] != 0xff ) {
				$option .= $data[$a++];
			}
			
			echo "Found COMMAND " . $command_descriptions[$option[0]] . " with extra data '" . $option . "\n";
		}
		else {
			$message .= $data[$a];
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