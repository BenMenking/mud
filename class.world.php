<?php

class World {
    private $world;
    private $rooms = [], $spawn_id;

    public function __construct($name) {
        $this->world = json_decode(file_get_contents('worlds/' . strtolower($_ENV['DEFAULT_WORLD']) . '.json'), true);

        foreach($this->world['rooms'] as $room) {
            $rooms[$room['id']] = Room::load($data);

            if( isset($room['spawn']) && $room['spawn'] ) {
                $this->spawn_id = $room['id'];
            }
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
    private $room;

    public static function load($data) {
        $room = new _self();

        $room->room = $data;

        return $room;
    }

    public function getTemperature() { $this->room['temperature']; }
    public function isSpawn() { return isset($this->room['spawn']); }
    public function exits() { return $this->room['exits']; }
    public function oxy_level() { return $this->room['oxygen_level']; }
    public function ambiance() { return $this->room['ambiance']; }


}