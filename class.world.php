<?php

class World {
    private $world;
    private $rooms = [], $spawn_id;

    public function __construct($name) {
        $file = 'worlds/' . strtolower($name) . '.json';

        if( file_exists($file) ) {
            $this->world = json_decode(file_get_contents($file), true);

            foreach($this->world['rooms'] as $id=>$room) {
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
    
    public function traverse(Room $fromRoom, $direction) {
        if( $id = $fromRoom->hasExit($direction) ) {
            return $this->getRoom($id);
        }
        else {
            return null;
        }
    }

    public function getSpawn() {
        return $this->rooms[$this->spawn_id];
    }

    public function getRoom($id) {
        return $this->rooms[$id];
    }
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