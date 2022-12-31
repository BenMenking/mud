<?php

namespace Menking\Mud\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Area {
    private $world;
    private $data;
    private $rooms = [], $spawnRoom = null;
    private $players = [];
    private $log;

    public static $instance = null;
    
    private function __construct($data, World $world) {
        $this->log = new Logger('Area');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $this->data = $data;
        $this->world = $world;

        $this->log->info("Loading Area {$data['id']}");
    }

    /**
     * This function moves.... I don't know why it's here.  Players should traverse, not Areas....???
     */
    public function traverse(Room $fromRoom, $direction) {
        if( $id = $fromRoom->hasExit($direction) ) {
            return $this->getRoom($id);
        }
        else {
            return null;
        }
    }

    public function getSpawnRoom() {
        return $this->spawnRoom;
    }

    /**
     * Register a Room object with this area
     * 
     * @param Room $room  The room object
     */
    public function addRoom(Room $room, bool $spawn = false) {
        $this->rooms[$room->id()] = $room;

        if( $spawn ) $this->spawnRoom = $room;
    }

    /**
     * Get a room object based on that room's ID
     * 
     * @param String $id    ID string of the room, found in the JSON definition file
     * @return Room | null
     */
    public function getRoom(String $id): Room|null {
        if( isset($this->rooms[$id]) ) {
            return $this->rooms[$id];
        }
        else {
            return null;
        }
    }

    public static function getInstance($data, World $world) {
        if (self::$instance == null)
        {
          self::$instance = new Area($data, $world);
        }
     
        return self::$instance;
    }

    /**
     * Adds a player to this area
     * 
     * @param Player $player
     */
    public function addPlayer(Player $player) {
        if( !in_array($player, $this->players) ) {
            $this->players[] = $player;
        }
    }

    /**
     * Returns all players, filtered by parameters, in this Area
     * 
     * @param bool $includeAdmins By default we include administrators
     * @param bool $includeMortals By default we include mortals
     * 
     * @return Array
     */
    public function getPlayers($includeAdmins = true, $includeMortals = true) {
        return $this->players;
    }

    public function getPlayer(String $player_id) {
        foreach($this->players as $player) {
            if( $player->id() == $player_id) {
                return $player;
            }
        }

        return null;
    }

    public function getPlayerBySocketId($socket_id) {
        foreach($this->players as $player) {
            if( $player->getSocketId() == $socket_id) {
                return $player;
            }
        }

        return null;
    }

    public function removePlayer(Player $player) {
        foreach($this->players as $idx=>$currentPlayer) {
            if( $currentPlayer == $player ) {
                // TODO: remove from room
                unset($this->players[$idx]);

                return true;
            }
        }

        return false;
    }

    public function playersInRoom(Room $room) {
        $r = [];
        foreach($this->players as $player) {
            if( $player->room() == $room ) {
                $r[] = $player;
            }
        }
        return $r;
    }

    public function name() { return $this->data['name']; }
    public function id() { return $this->data['id']; }
    public function world() { return $this->world; }
}
