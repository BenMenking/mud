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
        $status = null;

        if( $this->hasCommands() ) {
            $command = $this->popCommand();

            if( $question = $this->questions->byId($this->state) ) {
                echo "[SERVER] found a question\n";
                $success_question = isset($question['correct-next-step'])?$this->questions->byId($question['correct-next-step']):false;
                $failed_question = isset($question['incorrect-next-step'])?$this->questions->byId($question['incorrect-next-step']):false;

                switch($this->state) {
                    case 'login-prompt':
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
                    break;
                    case 'authenticate-user':
                        if( $this->player->authenticate($command) ) {
                            $this->pushMessage("You may now enter!\r\n\r\n");
                            $this->state = "playing";
                            
                            $status = 'authenticated'; 
                        }
                        else {
                            $this->player = null;
                            $this->pushMessage("Sorry, that is incorrect.\r\n");
                            $this->state = $question['incorrect-next-step'];
                        }
                    break;
                }
                /*
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

                */
            }
            else {
                $commands = $this->explode_ex(' ', $command);

                echo "[SERVER] Got command {$commands[0]}\n";
                
                $cmdObj = CommandFactory::factory($commands, $this->player);

                $this->pushMessage($this->player->perform($cmdObj));
            }
        }
        else {
            echo "[SERVER] has no commands\n";
        }

        return $status;
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