<?php

namespace Menking\Mud\Core;

use Menking\Mud\Core\Event\ServerStartEvent;

class Server {
    private $address, $port, $started;
    private $serverSocket, $bind, $running = false, $clients = [], $outgoing = [], $incoming = [];
    private $world, $logins;
    protected $handlers;

    public function __construct($listen_address, $listen_port) {
        $this->address = $listen_address;
        $this->port = $listen_port;

        $this->serverSocket = @socket_create(AF_INET, SOCK_STREAM, 0);

        if( $this->serverSocket === false ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }
    }

    public function registerHandler(String $endpoint) {
        $this->handlers[] = $endpoint;
    }

    public function deregisterHandler($endpoint) {
        $key = array_search($endpoint, $this->handlers);

        unset($this->handlers[$key]);
    }

    protected function sendEvent($event) {
        foreach($this->handlers as $handler) {
            $handler($event);
        }
    }

    /**
     * Causes the server to initialize
     * 
     */
    public function start() {
        $this->bind = @socket_bind($this->serverSocket, $this->address, $this->port);

        if( $this->bind === false ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        if( !socket_listen($this->serverSocket) ) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }

        $this->running = true;
        $this->started = time();

        $this->sendEvent(new ServerStartEvent($this));
    }

    /**
     * Stop the server
     * 
     * Closes the server socket and related stuff @TODO
     * - It should save each player and close each client socket with a nice message
     */
    public function stop() {
        @socket_close($this->serverSocket);
    }

    public function run() {
        $this->world = World::getInstance($_ENV['SERVER_WORLD']);
        //$this->logins = [];
        $timer = microtime(true);

        // game loop
        while(true) {
            // if we have data to write to a client, we need to add that client's socket to the $write
            // and the socket_select() will tell us if we can write without blocking
            //
            foreach($this->world->getPlayers() as $player) {
                while($player->hasMessages()) {
                    $this->queueMessage($player->getTag('uuid'), $player->getMessage());
                }
            }

            try {
                $changes = $this->select();

                foreach($changes as $type=>$change) {
                    foreach($change as $uuid) {
                        if( $type == 'new' ) {
                            $logins[$uuid] = new Login($uuid);
                            $this->queueMessage($uuid, $logins[$uuid]->begin());
                        }
                        else if( $type == 'data' ) {
                            if( isset($logins[$uuid]) ) {
                                $answer = $this->getNextMessage($uuid);

                                if( strlen($answer) == 0 ) continue;

                                $response = $logins[$uuid]->processAnswer($answer);
                                if( $response['completed'] === true ) {
                                    //$log->debug("Completed: true; " . json_encode($response));
                                    if( $response['state'] == 'login-prompt' ) {
                                        if( Player::exists($response['data']['login-prompt']) ) {
                                            $this->queueMessage($uuid, $logins[$uuid]->begin('authenticate-user', true));
                                        }
                                        else {
                                            $this->queueMessage($uuid, $logins[$uuid]->begin('start-registration', true));
                                        }
                                    }
                                    else if( $response['state'] == 'authenticate-user') {
                                        // log player in
                                        try {
                                            $p = Player::load($response['data']['login-prompt']);
                                            if( $p->authenticate($response['data']['authenticate-user']) ) {
                                                $p->addTag('uuid', $uuid);
                                                $p->addCommand('look');
                                                $this->world->addPlayer($p);
                                                unset($logins[$uuid]);
                                                //$log->info("[SERVER] Player {$p->name()} logged in");
                                            }
                                            else {
                                                $this->queueMessage($uuid, $logins[$uuid]->begin());
                                            }
                                        }
                                        catch(\Exception $e) {
                                            $this->queueMessage($uuid, $logins[$uuid]->begin());
                                        }
                                    }
                                    else if( $response['state'] == 'enter-email' ) {
                                        //$log->warning("[SERVER] need to write new user implementation!");
                                        // attempt to create new user
                                        //$log->debug("response: " . json_encode($response['data']));
                                        $p = Player::create($response['data']['login-prompt'],
                                            $response['data']['select-race'],
                                            [],
                                            $response['data']['enter-password-1'],
                                            $response['data']['enter-email'],
                                            $this->world->getSpawn()
                                        );
                                        $p->addTag('uuid', $uuid);
                                        $p->addCommand('look');
                                        $this->world->addPlayer($p);

                                        unset($logins[$uuid]);
                                    }
                                }
                                else {
                                    $this->queueMessage($uuid, $response['data']);
                                }
                            }
                            else {
                                $player = $this->world->getPlayerWithTag('uuid', $uuid);						
                                
                                if( !is_null($player) ) {
                                    $command = $this->getNextMessage($uuid);

                                    if( strlen($command) > 0 ) {
                                        $player->addCommand($command);
                                    }	
                                }
                                else {
                                    //$log->info("[SERVER] Error: got a null player, UUID is $uuid\n");
                                }
                            }
                        }
                        else if( $type == 'disconnected' ) {
                            if( isset($logins[$uuid]) ) {
                                //$log->info("[SERVER] Client disconnected");
                                unset($logins[$uuid]);
                            }
                            else {
                                $player = $this->world->getPlayerWithTag('uuid', $uuid);
                                $player->save();
                                $this->world->removePlayer($player);
                            }
                        }
                    }
                }
            }
            catch(\Exception $e) {
                //$log->emergency("[SERVER] Exception on select: " . $e->getMessage());
                //die();
                throw new \Exception($e->getMessage());
            }
            
            //
            // Let clients process commands and such
            //
            foreach($this->world->getPlayers() as $player) {
                $status = $player->execute();
            }

            $en = microtime(true);
            
            if( $en - $timer > 10 ) {
                //$log->info("[SERVER] There are " . number_format($world->countPlayers(), 0) . " players connected");
                //$log->info("[SERVER] Current: " . number_format(round(memory_get_usage() / 1024), 0) . "MB\tPeak: " 
                //    . number_format(round(memory_get_peak_usage() / 1024), 0) . "MB");
                $timer = $en;
            }
        }
    }

    /**
     * Perform a socket select
     */
    protected function select($timeout = 5) {
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

    protected function getNextMessage(int $clientSocket) {
        $message = '';
        $e = strpos($this->incoming[$clientSocket], "\r\n");

        if($e !== false) {
            $message = trim(substr($this->incoming[$clientSocket], 0, $e));
            $this->incoming[$clientSocket] = substr($this->incoming[$clientSocket], $e+1);
        }

        return trim($message);
    }

    protected function queueMessage(int $clientSocket, $txt) {
        $this->outgoing[$clientSocket] .= $txt;
    }

    public function running() { return $this->running; }
    public function ip() { return $this->address; }
    public function port() { return $this->port; }
      
}