<?php

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
    public function description() { return isset($this->data['description'])?$this->data['description']:'An unimaginative room.'; }
    public function temperature() { return isset($this->data['temperature'])?$this->data['temperature']:70; }
    public function isSpawn() { return isset($this->data['spawn']); }
    public function exits($delimiter = ' ') { return implode($delimiter, array_keys($this->data['exits'])); }
    public function oxygenLevel() { return isset($this->data['oxygen_level'])?$this->data['oxygen_level']:100; }
    public function ambiance() { return isset($this->data['ambiance'])?$this->data['ambiance']:'indoor'; }
    public function lightLevel() { return isset($this->data['light_level'])?$this->data['light_level']:50; }
    public function name() { return $this->data['room-name']; }
    public function hasExit($direction) { return isset($this->data['exits'][$direction])?$this->data['exits'][$direction]['target']:null; }
    public function getWorld() { return $this->world; }
    public function deathTrap() { return isset($this->data['death_trap'])?$this->data['death_trap']:false; }
    public function restrictMobs() { return isset($this->data['no_mobs'])?$this->data['no_mobs']:false; }
    public function peaceful() { return isset($this->data['peaceful'])?$this->data['peaceful']:false; }
    public function soundproof() { return isset($this->data['soundproof'])?$this->data['soundproof']:false; }
    public function restrictMagic() { return isset($this->data['no_magic'])?$this->data['no_magic']:false; }
    public function isTunnel() { return isset($this->data['tunnel'])?$this->data['tunnel']:false; }
    public function private() { return isset($this->data['private'])?$this->data['private']:false; }
    public function adminOnly() { return isset($this->data['admin_only'])?$this->data['admin_only']:false; }

    public function __toString() {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }
}