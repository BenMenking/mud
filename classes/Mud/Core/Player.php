<?php

namespace Menking\Mud\Core;

use Ramsey\Uuid\Uuid;
use Menking\Mud\States\StandingState;
use Menking\Mud\Core\Item;
use Menking\Mud\Command\CommandFactory;

class Player {
    // volatile variables that do not get permanently recorded
    public $state, $authenticated = false, $messages = [];
    private $room, $tags, $commands = [];

    // non-volatile variables that get saved to Player record
    protected $meta, $playerfile;

    public function addCommand($txt) {
        $this->commands[] = $txt;
    }

    private function popCommand() {
        return array_shift($this->commands);
    }

    public function sendMessage($text) {
        $this->messages[] = $text;
    }

    public function executeCommand($cmd) {
        $this->addCommand($cmd);
        $this->execute(false);
    }

    public function getMessage() {
        return array_shift($this->messages);
    }

    public function hasMessages() {
        return (count($this->messages) > 0);
    }

    public function addTag($key, $value) {
        $this->tags[$key] = $value;
    }

    public function getTag($key) {
        return isset($this->tags[$key])?$this->tags[$key]:null;
    }

    public function removeTag($key) {
        if( isset($this->tags[$key]) ) unset($this->tags[$key]);
    }

    public function execute($show_prompt = true) {
        $command = $this->popCommand();

        if( $command !== null ) {
            $cmd = CommandFactory::factory($command, $this);

            if( $cmd === null ) {
                return null;
            }
            else {
                $this->state->perform($cmd);
                if( $show_prompt ) $this->sendMessage($this->prompt());
                return null;
            }
        }
        else {
            return null;
        }
    }

    public function listInventory() {
        return $this->meta['inventory'];
    }

    public function addInventory(Item $item) {
        return $this->meta['inventory'][] = $item;
    }

    public function removeInventory(Item $item) {
        $index = array_search($item, $this->meta['inventory']);

        if( $index ) {
            $item = $this->meta['inventory'][$index];
            unset($this->meta['inventory'][$index]);

            return $item;
        }
        else {
            return false;
        }
    }

    public static function exists($name) {
        $file = "players/" . Player::pathify($name) . ".json";

        return file_exists($file);
    }

    public static function load($name) {
        $file = "players/" . Player::pathify($name) . ".json";

        if( file_exists($file) ) {
            $instance = new self();

            $instance->meta = json_decode(file_get_contents($file), true);
            // check for meta that might have not been saved with player (maybe older file?)
            if( !isset($instance->meta['inventory']) ) {
                $instance->meta['inventory'] = [];
            }
            if( !isset($instance->meta['inventory_capacity']) ) {
                $instance->meta['inventory_capacity'] = 50;
            }
            $instance->playerfile = $file;
            $instance->state = new StandingState();

            $world = World::getInstance($_ENV['SERVER_WORLD']);
            $instance->room = $world->getRoom($instance->meta['room']);
            return $instance;
        }
        else {
            throw new \Exception('Players does not exist');
        }
    }

    public function setRoom(Room $room) {
        $this->room = $room;
        $this->meta['room'] = $room->id();
        $this->meta['world'] = $room->getWorld()->name();
    }

    public function save() {
        return file_put_contents($this->playerfile, json_encode($this->meta, JSON_PRETTY_PRINT));
    }

    public function authenticate($password) {
        if( password_verify($password, $this->meta['password']) ) {
            $this->authenticated = true;
            return true;
        }
        else {
            return false;
        }
    }

    public function authenticated() {
        return $this->authenticated;
    }

    public static function create($name, $race, $attributes, $password, $email_address, $starting_room) {
        $instance = new self();

        $file = "players/" . Player::pathify($name) . ".json";
        $instance->meta['name'] = $name;
        $instance->meta['title'] = '';
        $instance->meta['race'] = 'human';
        $instance->meta['attributes'] = $attributes;
        $instance->meta['skill_points'] = 0;
        $instance->meta['health'] = 100; // 0-100 (but modifiers could take above 100)
        $instance->meta['total_health'] = 100;
        $instance->meta['mana'] = 0;
        $instance->meta['total_mana'] = 0;
        $instance->meta['movement'] = 25;
        $instance->meta['total_movement'] = 25;
        $instance->meta['hunger'] = 0; // 0-5 with 0 no hunger, 3 hungry, 5 starving
        $instance->meta['thirst'] = 0; // 0-5 with 0 no thirst, 3 parched, 5 dehydrated
        $instance->meta['temperature'] = 96.7; // in farenheit
        $instance->meta['alignment'] = 0;
        $instance->meta['wearables'] = [];
        $instance->meta['wallet'] = 100; // starting coins
        $instance->meta['carrying'] = [];
        $instance->meta['weight'] = 175; // in pounds
        $instance->meta['o2'] = 99; // blood's oxygen level
        $instance->meta['password'] = password_hash($password, PASSWORD_DEFAULT);
        $instance->meta['email'] = $email_address;
        $instance->meta['created'] = time();
        $instance->meta['last_login'] = time();
        $instance->meta['room'] = $starting_room->id();
        $instance->meta['world'] = '';
        $instance->meta['inventory'] = [];
        $instance->meta['inventory_capacity'] = 50;

        $instance->meta['uuid'] = Uuid::uuid5(Uuid::NAMESPACE_X500, json_encode($instance->meta))->toString();

        $instance->state = new StandingState();
        $instance->playerfile = $file;

        $instance->setRoom($starting_room);

        $instance->save();

        return $instance;
    }

    public function name() { return $this->meta['name']; }
    public function class() { return $this->meta['class']; }
    public function race() { return $this->meta['race']; }

    public function base() { return $this->meta['attributes']; }
    public function room() { return $this->room; }

    public function prompt() {
        return "<H:{$this->meta['health']}/{$this->meta['total_health']} "
            . "M:{$this->meta['mana']}/{$this->meta['total_mana']} "
            . "MV:{$this->meta['movement']}/{$this->meta['total_movement']} "
            . "G:{$this->meta['wallet']}> ";
    }

    public function __toString() {
        return $this->meta['uuid'];
    }

    private static function pathify($file) {
        /** https://stackoverflow.com/a/2021729/4508285 */
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;[]().
        // If you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        // Thanks @Łukasz Rysiak!
        $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', strtolower($file));
        // Remove any runs of periods (thanks falstro!)
        return mb_ereg_replace("([\.]{2,})", '', $file);        
    }


}
