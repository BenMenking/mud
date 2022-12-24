<?php

namespace Menking\Mud\Core;

class Room {
    private $data, $world;

    public static function load($data, $world) {
        $room = new self();

        $room->data = $data;
        $room->world = $world;

        return $room;
    }

    public function id() { return $this->data['id']; }
    public function description() { return isset($this->data['description'])?$this->data['description']:'An unimaginative room.'; }
    public function temperature() { return isset($this->data['temperature'])?$this->data['temperature']:70; }
    public function isSpawn() { return isset($this->data['spawn']); }
    public function exits() { return $this->data['exits']; }
    public function oxygenLevel() { return isset($this->data['env']['oxygen_level'])?$this->data['oxygen_level']:100; }
    public function indoor() { return isset($this->data['env']['indoor'])?$this->data['env']['indoor']:false; }
    public function lightLevel() { return isset($this->data['light-level'])?$this->data['light-level']:50; }
    public function name() { return $this->data['name']; }
    public function hasExit($direction) { return isset($this->data['exits'][$direction])?$this->data['exits'][$direction]['target']:null; }
    public function getWorld() { return $this->world; }
    public function deathTrap() { return isset($this->data['env']['death_trap'])?$this->data['env']['death_trap']:false; }
    public function restrictMobs() { return isset($this->data['env']['no_mobs'])?$this->data['env']['no_mobs']:false; }
    public function peaceful() { return isset($this->data['env']['peaceful'])?$this->data['env']['peaceful']:false; }
    public function soundproof() { return isset($this->data['env']['soundproof'])?$this->data['env']['soundproof']:false; }
    public function restrictMagic() { return isset($this->data['env']['no_magic'])?$this->data['env']['no_magic']:false; }
    public function isTunnel() { return isset($this->data['env']['tunnel'])?$this->data['env']['tunnel']:false; }
    public function private() { return isset($this->data['env']['private'])?$this->data['env']['private']:false; }
    public function adminOnly() { return isset($this->data['env']['admin_only'])?$this->data['env']['admin_only']:false; }
    public function terrain() { return $this->data['terrain']; }

    public function __toString() {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    const ROOM_SECTOR_INSIDE = 'inside';
    const ROOM_SECTOR_CITY = 'city';
    const ROOM_SECTOR_FIELD = 'field';
    const ROOM_SECTOR_FOREST = 'forest';
    const ROOM_SECTOR_HILLS = 'hills';
    const ROOM_SECTOR_MOUNTAIN = 'mountain';
    const ROOM_SECTOR_WATER_SWIM = 'water_swim';
    const ROOM_SECTOR_WATER_NOSWIM = 'water_noswim';
    const ROOM_SECTOR_UNDERWATER = 'underwater';
    const ROOM_SECTOR_FLYING = 'flying';

    const ROOM_ENV_DARK = "dark";
    const ROOM_ENV_DEATH = "death";
    const ROOM_ENV_NOMOB = "nomob";
    const ROOM_ENV_INDOORS = "indoors";
    const ROOM_ENV_PEACEFUL = "peaceful";
    const ROOM_ENV_SOUNDPROOF = "soundproof";
    const ROOM_ENV_NOTRACK = "notrack";
    const ROOM_ENV_NOMAGIC = "nomagic";
    const ROOM_ENV_TUNNEL = "tunnel";
    const ROOM_ENV_PRIVATE = "private";
    const ROOM_ENV_GODROOM = "godroom";

    const ROOM_DOOR_UNRESTRICTED = "unrestricted";
    const ROOM_DOOR_NORMAL = "normal";
    const ROOM_DOOR_PICKPROOF = "pickproof";
}