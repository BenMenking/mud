<?php

class World {
    private $data, $name;
    private $rooms = [], $spawn_id;
    private $players = [];
    private static $instance = null;

    private function __construct($name) {
        $file = 'worlds/' . strtolower($name) . '.json';
        $this->name = $name;

        if( file_exists($file) ) {
            $this->data = json_decode(file_get_contents($file), true);

            foreach($this->data['rooms'] as $id=>$room) {
                $this->rooms[$id] = Room::load($room, $id, $this);

                if( isset($room['spawn']) && $room['spawn'] ) {
                    $this->spawn_id = $id;
                }
            }
        }
        else {
            throw new Exception("World not found.");
        }
    }
    
    public static function getInstance($name) {
        if (self::$instance == null)
        {
          self::$instance = new World($name);
        }
     
        return self::$instance;
    }

    public function addPlayer(Player $player) {
        if( !in_array($player, $this->players) ) {
            $this->players[] = $player;
        }
    }

    public function countPlayers($includeAdmins = true, $includeMortals = true) {
        return count($this->players);
    }

    public function getPlayers($includeAdmins = true, $includeMortals = true) {
        return $this->players;
    }

    public function getPlayerWithTag($key, $value) {
        foreach($this->players as $player) {
            if( $player->getTag($key) == $value) {
                return $player;
            }
        }

        return null;
    }

    public function removePlayer(Player $player) {
        foreach($this->players as $idx=>$currentPlayer) {
            if( $currentPlayer == $player ) {
                unset($this->players[$idx]);
                break;
            }
        }
    }

    public function traverse(Room $fromRoom, $direction) {
        if( $id = $fromRoom->hasExit($direction) ) {
            return $this->getRoom($id);
        }
        else {
            return null;
        }
    }

    public function name() { return $this->name; }
    public function getSpawn() {
        return $this->rooms[$this->spawn_id];
    }

    public function getRoom($id) {
        return $this->rooms[$id];
    }
}

class Area {
    private $world;
    private $rooms = [];
}

class Room {
    private $data, $world;

    public static function load($data, $id, $world) {
        $room = new self();

        $room->data = $data;
        $room->data['id'] = $id;
        $room->world = $world;

        return $room;
    }

    public function id() { return $this->data['id']; }
    public function description() { return $this->data['description']; }
    public function temperature() { return $this->data['temperature']; }
    public function isSpawn() { return isset($this->data['spawn']); }
    public function exits($delimiter = ' ') { return implode($delimiter, array_keys($this->data['exits'])); }
    public function oxy_level() { return $this->data['oxygen_level']; }
    public function ambiance() { return $this->data['ambiance']; }
    public function lightLevel() { return $this->data['light_level']; }
    public function name() { return $this->data['room-name']; }
    public function hasExit($direction) { return isset($this->data['exits'][$direction])?$this->data['exits'][$direction]['target']:null; }
    public function getWorld() { return $this->world; }

    public function __toString() {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }
}