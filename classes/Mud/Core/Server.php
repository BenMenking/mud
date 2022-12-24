<?php

namespace Menking\Mud\Core;

class Server {
    private $address, $port, $started;
    private $serverSocket, $bind, $running = false, $clients = [], $outgoing = [], $incoming = [];

    public function __construct($listen_address, $listen_port) {
        $this->address = $listen_address;
        $this->port = $listen_port;

        $this->serverSocket = @socket_create(AF_INET, SOCK_STREAM, 0);

        if( $this->serverSocket === false ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }
    }

    /**
     * Causes the server to initialize
     * 
     */
    public function connect() {
        $this->bind = @socket_bind($this->serverSocket, $this->address, $this->port);

        if( $this->bind === false ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        if( !socket_listen($this->serverSocket) ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        $this->running = true;
        $this->started = time();
    }

    public function close() {
        @socket_close($this->serverSocket);
    }

    /**
     * Perform a socket select
     */
    public function select($timeout = 5) {
        $results = ['new'=>[], 'data'=>[], 'disconnected'=>[]];

        $read[] = $this->serverSocket;
        $read = array_merge($read, array_values($this->clients)); // always attempt to read from every client
        
        $write = [];

        foreach($this->outgoing as $clientSocket=>$msg) {
            // there is data in this socket's buffer to write out, so add them to the write list
            //
            if( strlen($msg) > 0 ) {
                $write[] = $this->clients[$clientSocket];
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
            if( !empty($read) && in_array($this->serverSocket, $read) ) {
                $c = socket_accept($this->serverSocket);
                $objId = spl_object_id($c);
                $this->clients[$objId] = $c;
                $this->outgoing[$objId] = '';
                $this->incoming[$objId] = '';
                $results['new'][] = $objId;

                // need to remove $server_sock from $read, otherwise this will cause issues
                // later in the code
                $key = array_search($this->serverSocket, $read);
                unset($read[$key]);
            }
        }

		// if there are any remaining sockets in $read, process them
		//
		if( !empty($read) ) {
			foreach($read as $read_sock) {
                $objId = spl_object_id($read_sock);
                                
                //$clientSocket = array_search(spl_object_id($read_sock), $this->clients);
                //$data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

                if( isset($this->clients[$objId]) ) {
                    $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

                    if( $data === false ) {
                        // client disconnected, cleanup
                        socket_close($this->clients[$objId]);
                        unset($this->clients[$objId]);
                        $results['disconnected'][] = $objId;
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
                
                        $this->incoming[$objId] .= $message;
                        $results['data'][] = $clientSocket;
                    }
                }
                else {
                    echo "Got read_sock but don't have that Socket registered!\n";
                }
			}
		}
        
        // if there are any outgoing messages, send them out
        //
		if( !empty($write) && count($write) > 0 ) {
			foreach($write as $write_sock) {
                                //$clientSocket = array_search($write_sock, $this->clients);
                
                $objId = spl_object_id($write_sock);

                if( isset($this->clients[$objId]) ) {
                    // write as many bytes as possible to socket.  the write may not send 
                    // all the bytes in the buffer at once, so we send what we can and save
                    // the rest when this gets called again
                    $bytes_written = @socket_write($write_sock, $this->outgoing[$clientSocket]);
                    
                    // now truncate $bytes_written from the buffer
                    $this->outgoing[$clientSocket] = substr($this->outgoing[$clientSocket], $bytes_written);
                }
                else {
                    echo "Got write_sock but don't have that Socket registered!\n";
                }

			}
        }
        
        return $results;
    }

    public function getNextMessage(int $clientSocket) {
        $message = '';
        $e = strpos($this->incoming[$clientSocket], "\r\n");

        if($e !== false) {
            $message = trim(substr($this->incoming[$clientSocket], 0, $e));
            $this->incoming[$clientSocket] = substr($this->incoming[$clientSocket], $e+1);
        }

        return trim($message);
    }

    public function queueMessage(int $clientSocket, $txt) {
        $this->outgoing[$clientSocket] .= $txt;
    }

    public function running() { return $this->running; }
    public function ip() { return $this->address; }
    public function port() { return $this->port; }
      
}