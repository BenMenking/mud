<?php

class World {
    private $world;
    private $rooms = [], $spawn_id;

    public function __construct($name) {
        $file = 'worlds/' . strtolower($name) . '.json';

        if( file_exists($file) ) {
            $this->world = json_decode(file_get_contents($file), true);

            foreach($this->world['rooms'] as $room) {
                $rooms[$room['id']] = Room::load($room);

                if( isset($room['spawn']) && $room['spawn'] ) {
                    $this->spawn_id = $room['id'];
                }
            }
        }
        else {
            throw new Exception("World not found.");
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
        $room = new self();

        $room->room = $data;

        return $room;
    }

    public function getTemperature() { $this->room['temperature']; }
    public function isSpawn() { return isset($this->room['spawn']); }
    public function exits() { return $this->room['exits']; }
    public function oxy_level() { return $this->room['oxygen_level']; }
    public function ambiance() { return $this->room['ambiance']; }


}