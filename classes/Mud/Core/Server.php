<?php

namespace Menking\Mud\Core;

use Ramsey\Uuid\Uuid;

class Server {
    private $address, $port, $started;
    private $socket, $bind, $running = false, $clients = [], $outgoing = [], $incoming = [];

    public function __construct($listen_address, $listen_port) {
        $this->address = $listen_address;
        $this->port = $listen_port;

        $this->socket = @socket_create(AF_INET, SOCK_STREAM, 0);

        if( $this->socket === false ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }
    }

    public function connect() {
        $this->bind = @socket_bind($this->socket, $this->address, $this->port);

        if( $this->bind === false ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        if( !socket_listen($this->socket) ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        $this->running = true;
        $this->started = time();
    }

    public function close() {
        @socket_close($this->socket);
    }

    /**
     * Perform a socket select
     */
    public function select($timeout = 5) {
        $results = ['new'=>[], 'data'=>[], 'disconnected'=>[]];

        $read[] = $this->socket;
        $read = array_merge($read, array_values($this->clients)); // always attempt to read from every client
        
        $write = [];

        foreach($this->outgoing as $uuid=>$msg) {
            // there is data in this socket's buffer to write out, so add them to the write list
            //
            if( strlen($msg) > 0 ) {
                $write[] = $this->clients[$uuid];
            }
        }
        $except = [];

        $changed = @socket_select($read, $write, $except, $timeout);

        if( $changed === false ) {
            $this->running = false;
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        if( $changed > 0 ) {
            // check for a read on server's socket.  new connection
            //
            if( !empty($read) && in_array($this->socket, $read) ) {
                $c = socket_accept($this->socket);
                $uuid = Uuid::uuid5(Uuid::NAMESPACE_X500, (string)($c . time()))->toString();
                $this->clients[$uuid] = $c;
                $this->outgoing[$uuid] = '';
                $this->incoming[$uuid] = '';
                $results['new'][] = $uuid;

                // need to remove $server_sock from $read, otherwise this will cause issues
                // later in the code
                $key = array_search($this->socket, $read);
                unset($read[$key]);
            }
        }

		// if there are any remaining sockets in $read, process them
		//
		if( !empty($read) ) {
			foreach($read as $read_sock) {
                $uuid = array_search($read_sock, $this->clients);
                $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

				if( $data === false ) {
                    // client disconnected, cleanup
                    socket_close($this->clients[$uuid]);
                    unset($this->clients[$uuid]);
                    $results['disconnected'][] = $uuid;
				}
				else {
                    $message = '';

                    for($a = 0; $a < strlen($data); ) {
    
                        if( ord($data[$a]) == 0xff && ord($data[$a+1]) == 0xff ) {
                            // special case where 0xFF is padded
                            $message .= ord(0xFF);
                            $a += 2;
                        }
                        else if( ord($data[$a]) == 0xff ) {
                            $a += 3;
                        }
                        else {
                            $message .= ($data[$a]);
                            $a++;
                        }
                    }
            
                    $this->incoming[$uuid] .= $message;
                    $results['data'][] = $uuid;
				}
			}
		}
        
        // if there are any outgoing messages, send them out
        //
		if( !empty($write) && count($write) > 0 ) {
			foreach($write as $write_sock) {
                $uuid = array_search($write_sock, $this->clients);
                
                // write as many bytes as possible to socket.  the write may not send 
                // all the bytes in the buffer at once, so we send what we can and save
                // the rest when this gets called again
                $bytes_written = @socket_write($write_sock, $this->outgoing[$uuid]);
                
                // now truncate $bytes_written from the buffer
                $this->outgoing[$uuid] = substr($this->outgoing[$uuid], $bytes_written);
			}
        }
        
        return $results;
    }

    public function getNextMessage($uuid) {
        $message = '';
        $e = strpos($this->incoming[$uuid], "\r\n");

        if($e !== false) {
            $message = trim(substr($this->incoming[$uuid], 0, $e));
            $this->incoming[$uuid] = substr($this->incoming[$uuid], $e+1);
        }

        return trim($message);
    }

    public function queueMessage($uuid, $txt) {
        $this->outgoing[$uuid] .= $txt;
    }

    public function running() { return $this->running; }
    public function ip() { return $this->address; }
    public function port() { return $this->port; }
      
}