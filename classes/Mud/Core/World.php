<?php

namespace Menking\Mud\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Menking\Mud\Core\Player;

class World {
    private $name;
    private $areas = [], $rooms = [], $spawn_id;
    private $players = [], $mobs = [], $items = [];
    private static $instance = null;
    private $log;

    private function __construct($name) {
        $this->log = new Logger('World');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $file = 'worlds/' . strtolower($name) . '/' . strtolower($name) . '.json';
        //$this->name = $name;

        $this->log->info("Loading world $name");

        if( file_exists($file) ) {
            $data = json_decode(file_get_contents($file), true);

            if( is_null($data) ) {
                $this->log->error("Unable to load world file for $name");
                
                throw new \Exception("Unable to load world data");
            }
            else {
                $this->name = $data['name'];

                foreach($data['areas'] as $area) { 
                    $this->area[] = $area;

                    foreach($data['rooms'] as $room) {
                        $this->rooms[$room['id']] = Room::load($room, $this, $area);

                        if( isset($room['spawn']) && $room['spawn'] ) {
                            $this->spawn_id = $room['id'];
                        }
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

    public function getPlayer(String $name) {
        foreach($this->players as $player) {
            if( strtolower($player->name()) == strtolower($name)) {
                return $player;
            }
        }

        return null;
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

    public function playersInRoom($room) {
        $r = [];
        foreach($this->players as $player) {
            if( $player->room() == $room ) {
                $r[] = $player;
            }
        }
        return $r;
    }

    public function name() { return $this->name; }

    public function getSpawn() {
        return $this->rooms[$this->spawn_id];
    }

    public function getRoom($id) {
        //print_r($this->rooms);
        return $this->rooms[$id];
    }
}
