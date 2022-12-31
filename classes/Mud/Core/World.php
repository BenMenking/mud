<?php

namespace Menking\Mud\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Menking\Mud\Core\Player;

class World {
    private $name;
    private $areas = [], $defaultArea = null;
    private $mobs = [], $items = [];
    private static $instance = null;
    private $log;

    private function __construct($name) {
        $this->log = new Logger('World');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $file = 'worlds/' . strtolower($name) . '/' . strtolower($name) . '.json';

        $this->log->info("Loading world $name");

        if( file_exists($file) ) {
            $data = json_decode(file_get_contents($file), true);

            if( is_null($data) ) {
                $this->log->error("Unable to load world file for $name");
                
                throw new \Exception("Unable to load world data");
            }
            else {
                $this->data['name'] = $data['name'];

                foreach($data['areas'] as $area) {
                    $a = Area::getInstance($area, $this); 
                    $this->areas[] = $a;

                    // set a default area, unless the config tells us otherwise
                    if( is_null($this->defaultArea) ) {
                        $this->defaultArea = $a;
                    }

                    foreach($area['rooms'] as $room) {
                        $a->addRoom(Room::load($room, $a), (isset($room['spawn']) && $room['spawn']));
                    }
                }
            }
        }
        else {
            throw new \Exception("World not found.");
        }

        $this->log->info("Loading items for $name");

        // load items
        //
        $dir = 'worlds/' . strtolower($name) . '/items/';
        $objs = scandir($dir);

        foreach($objs as $obj) {
            if( $obj == '.' || $obj == '..' ) continue;

            if( strpos($obj, '.json') !== false ) {
                $data = file_get_contents($dir . '/' . $obj);
                $json = json_decode($data, true);

                if( is_null($json) ) {
                    $this->log->warning("Unable to load item '$obj'");
                }
                else {
                    $this->log->info("Loaded item '{$json['name']}'");
                    $this->items[$json['name']] = $json;
                }
            }
        }

        $this->log->info("Loading mobiles for $name");

        // load mobs
        //
        $dir = 'worlds/' . strtolower($name) . '/mobs/';
        $objs = scandir($dir);

        foreach($objs as $obj) {
            if( $obj == '.' || $obj == '..' ) continue;

            if( strpos($obj, '.json') !== false ) {
                $data = file_get_contents($dir . '/' . $obj);
                $json = json_decode($data, true);

                if( is_null($json) ) {
                    $this->log->warning("Unable to load mob '$obj'");
                }
                else {
                    $this->log->info("Loaded mob '{$json['name']}'");
                    $this->mobs[$json['name']] = $json;
                }
            }
        }

        $this->log->info("Loading world {$this->name} complete");
    }
    
    /**
     * Get players in the world, or optionally in one Area
     * 
     * @param String $area_id   A particular area, as opposed to everyone in the World
     * 
     * @return Array
     */
    public function getPlayers($area_id = null) {
        $players = [];

        foreach($this->areas as $area) {
            if( !is_null($area_id) && $area->id() == $area_id) {
                $players = array_merge($players, $area->getPlayers());
            }
            else if( is_null($area_id) ) {
                $players = array_merge($players, $area->getPlayers());
            }
        }

        return $players;
    }

    public function removePlayer($player) {
        foreach($this->areas as $area) {
            if( $area->removePlayer($player) ) {
                return true;
            }
        }

        return false;
    }
    /**
     * Get a player in the World
     * 
     * @param String $player_id
     * @return Player | null
     */
    public function getPlayer($player_id) {
        foreach($this->areas as $area) {
            if( ($player = $area->getPlayer($player_id)) !== null ) {
                return $player;
            }
        }

        return null;
    }

    public function getPlayerBySocketId($socket_id) {
        foreach($this->areas as $area) {
            if( ($player = $area->getPlayerBySocketId($socket_id)) !== null ) {
                return $player;
            }
        }

        return null;        
    }

    public static function getInstance($name) {
        if (self::$instance == null)
        {
          self::$instance = new World($name);
        }
     
        return self::$instance;
    }

    public function addPlayer(Player $player) {
        $this->defaultArea->addPlayer($player);
    }

    public function getSpawnRoom($room_id = '') {
        // use the default area and find the spawn point room
        if( !empty($room_id) ) {
            return $this->findRoom($room_id);
        }
        else {
            return $this->defaultArea->getSpawnRoom();
        }
    }

    /**
     * Search all areas and find the room_id specified.
     * 
     * @param String $room_id   the room ID of the room desired
     * @return Room | null;
     */
    public function findRoom($room_id) {
        foreach($this->areas as $area) {
            if( $room = $area->getRoom($room_id) ) {
                return $room;
            }
        }

        return null;
    }

    public function name() { return $this->data['name']; }
}
