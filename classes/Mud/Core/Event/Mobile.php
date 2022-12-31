<?php

namespace Menking\Mud\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Mobile {
    private $data;
    public static $instance;

    private function __construct($name) {
        $this->log = new Logger('Mobile');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $filename = 'worlds/' . strtolower($name) . '/mobs/' . $name;

        $data = file_get_contents($filename);
        $json = json_decode($data, true);

        if( is_null($json) ) {
            $this->log->warning("Unable to load mob '$name'");
        }
        else {
            $this->log->info("Loaded mob '{$json['name']}'");
            $this->data = $json;
        }

        $this->log->info("Loading Mobile $name");
    }

    public function getName() { return $this->data['name']; }
    public function getLocation() { return null; }
    
    public static function getInstance($name) {
        if (self::$instance == null)
        {
          self::$instance = new Mobile($name);
        }
     
        return self::$instance;
    }
}