<?php

class Clients {
    private $clients = [];

    public function add(Client $client) {
        if( !in_array($client, $this->clients) ) {
            $this->clients[] = $client;
        }
    }

    public function remove(Client $client) {
        if( $key = array_search($client, $this->clients) ) {
            unset($this->clients[$key]);
        }
    }

    public function getById($id) {
        foreach($this->clients as $client) {
            if( $client->id() == $id ) {
                return $client;
            }
        }

        return null;
    }

    public function getBySocket($socket) {
        foreach($this->clients as $client) {
            if( $client->socket() == $socket ) {
                return $client;
            }
        }

        return null;       
    }

    public function all() {
        return $this->clients;
    }
}

class Client {
    private $messages = [], $socket, $id, $world = null, $player = null;
    private $commands = [], $state, $buffer, $questions;

    public $ip, $port;

    public function __construct($socket) {
        $this->socket = $socket;
        $this->id = (int)$socket;
        $this->state = 'new';
        $this->questions = new Questions();

        socket_getpeername($this->socket, $this->ip, $this->port);
    }

    public function world($world = null) {
        if( is_null($world) ) {
            return $this->world;
        }
        else {
            $this->world = $world;
        }
    }

    public function player() {
        return $this->player;
    }

    public function pushMessage($msg) {
        $this->messages[] = $msg;
    }

    public function hasMessages() {
        return count($this->messages) > 0;
    }

    public function popMessage() {
        return array_shift($this->messages);
    }

    public function addCommand($cmd) {
        $this->commands[] = $cmd;
    }
    
    public function addCommandBuffer($msg) { 
        $message = '';

        for($a = 0; $a < strlen($msg); ) {
    
            if( ord($msg[$a]) == 0xff && ord($msg[$a+1]) == 0xff ) {
                // special case where 0xFF is padded
                $message .= ord(0xFF);
                $a += 2;
            }
            else if( ord($msg[$a]) == 0xff ) {
                $a += 3;
            }
            else {
                $message .= ($msg[$a]);
                $a++;
            }
        }

        $this->buffer .= $message;

        $e = strpos($this->buffer, "\r\n");

        while($e !== false) {
            $command = trim(substr($this->buffer, 0, $e));
            $this->commands[] = $command;
            $this->buffer = substr($this->buffer, $e+1);

            $e = strpos($this->buffer, "\r\n");
        }
    }

    public function hasCommands() {
        return count($this->commands) > 0;
    }

    public function popCommand() {
        return array_shift($this->commands);
    }

    public function execute() {
        //echo "[SERVER] client->execute()\n";

        if( $this->hasCommands() ) {
            $command = $this->popCommand();

            if( $question = $this->questions->byId($this->state) ) {
                echo "[SERVER] found a question\n";
                $success_question = isset($question['correct-next-step'])?$this->questions->byId($question['correct-next-step']):false;
                $failed_question = isset($question['incorrect-next-step'])?$this->questions->byId($question['incorrect-next-step']):false;

                switch($this->state) {
                    case 'login-prompt':
                        //echo "[SERVER] 'login-prompt' command: $command\n";
                        try {
							$this->player = Player::load($command);
                            $this->pushMessage($success_question['message']);
                            $this->state = $question['correct-next-step'];                            
						}
						catch(Exception $e) {
                            $this->player = null;
                            $this->pushMessage($failed_question['message']);
                            $this->state = $question['incorrect-next-step'];
                        }
                        //echo "[SERVER] 'login-prompt' state: {$this->state}\n";
                    break;
                    case 'authenticate-user':
                        //echo "[SERVER] 'authenticate-user' command: $command\n";
                        if( $this->player->authenticate($command) ) {
                            $this->pushMessage("You may now enter!\r\n\r\n");
                            $this->state = "playing";
                        }
                        else {
                            $this->player = null;
                            $this->pushMessage("Sorry, that is incorrect.\r\n");
                            $this->state = $question['incorrect-next-step'];
                        }
                        //echo "[SERVER] 'authenticate-user' state: {$this->state}\n";
                    break;
                }
            }
            else {
                $commands = $this->explode_ex(' ', $command);

                echo "[SERVER] Got command {$commands[0]}\n";
                
                $cmdObj = CommandFactory::factory($commands, $this->player);

                return $this->player->perform($cmdObj);
            }
        }
        else {
            echo "[SERVER] has no commands\n";
        }
    }

    public function id() { return $this->id; }
    public function socket() { return $this->socket; }

    // TODO: this should be in world, not in Client?
    //
    public function disconnect() {
        $this->player->save();
        //$this->world->removePlayer($client['player']);

        unset($client_meta[$socket]);
        $key = array_search($socket, $clients);
        unset($clients[$key]);

        socket_close($socket);

        echo "[{$client['peername']}:{$client['peerport']}] Requesting to quit\n";
    }

    public function beginAuthentication() {
        $this->pushMessage(file_get_contents('intro.txt'));

        $question = $this->questions->byId("login-prompt");
        $this->pushMessage($question['message']);
        $this->state = "login-prompt";
    }

    public function __toString() {
        return sha1($this->id);
    }

    private function explode_ex($delimiter, $str) {
        $parts = [];
        $part = '';

        for($i = 0; $i < strlen($str); $i++) {
            if( $str[$i] == $delimiter && strlen($part) > 0 ) {
                $parts[] = $part;
                $part = '';
            }
            else {
                $part .= $str[$i];
            }		
        }

        if( strlen($part) > 0 ) {
            $parts[] = $part;
        }

        return $parts;
    }    
}