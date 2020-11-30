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
    private $commands = [], $state;

    public $ip, $port;

    public function __construct($socket) {
        $this->socket = $socket;
        $this->id = (int)$socket;
        $this->state = 'new';

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

    public function player($player = null) {
        if( is_null($player) ) {
            return $this->player;
        }
        else {
            $this->player = $player;
        }
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
        $this->buffer .= $msg;

        $e = strpos($this->buffer, "\r\n");

        while($e) {
            $this->commands[] = trim(substr($this->buffer, 0, $e));
            $this->buffer = substr($this->buffer, $e);
        }

    }

    public function hasCommands() {
        return count($this->commands) > 0;
    }

    public function popCommand() {
        return array_shift($this->commands);
    }

    public function execute() {

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

        $questions = new Questions();
        $question = $questions->byId("login-prompt");
        $this->pushMessage($login_question['message']);
        $this->state = "login-prompt";
    }

    public function __toString() {
        return sha1($this->id);
    }
}