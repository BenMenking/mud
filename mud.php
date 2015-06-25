<?php

require('telnet.inc.php');

$server_sock = socket_create(AF_INET, SOCK_STREAM, 0);

if( !$server_sock ) { die("Could not create socket\n"); }

$bind = socket_bind($server_sock, '0.0.0.0', '23');

if( !$bind ) { die("Could not bind to port 23!\n"); }

if( !socket_listen($server_sock) ) die("Could not listen on port 23!\n");

$clients = array($server_sock);
$client_meta = array('telnet'=>array());

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

            socket_getpeername($new_sock, $ip);
			
			$client_meta[(int)$new_sock] = array('state'=>'login', 'username'=>'',
				'peername'=>$ip);
				
			$key = array_search($server_sock, $read);
			unset($read[$key]);
            echo "New client connected: {$ip}\n";	

			// write out a TELNET NOP
			socket_write($new_sock, file_get_contents('intro.txt'));
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
			
			//echo hex_dump($data) . "\n";
			$message = trim(read_stream($data, $terms));
			//var_dump($terms);
			//echo "MESSAGE: $message\n\n";
			
			socket_getpeername($read_sock, $ip);
			
			if( strlen($message) > 0 )
				echo "client said '$message'\n";
		}
		
		if( $write !== null ) {
			foreach($write as $write_sock) {
				echo "FD $write_sock wants a write?\n";
			}
		}
	}
}

socket_close($server_sock);

function show_prompt($socket) {
	socket_write($socket, "\nUSER > ");
}

function read_stream($data, &$terms) {
	global $command_descriptions, $option_descriptions;
	
	$message = '';
	//echo "in read_stream()\n";
	
	//echo "data length: " . strlen($data) . "\n";
	
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
			//echo "DIKE: " . ord($data[$a]) . "\n";
			
			//while( $a < strlen($data) && ord($data[$a]) != 0xff ) {
			//	$a++;
			//}
			//$a--;
			
			//echo "Found COMMAND " . $command . " with extra data '" . $option . "'\n";
			$terms[] = array($command=>$option);
		}
		else {
			//echo "MSG ADD: " . ord($data[$a]) . "\n";
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