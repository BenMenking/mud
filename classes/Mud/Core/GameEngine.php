<?php

namespace Menking\Mud\Core;

use Menking\Mud\Core\Event\PlayerLoginEvent;
use Menking\Mud\Core\Event\PlayerLogoffEvent;
use Menking\Mud\Core\Event\ServerStartEvent;

class GameEngine {
    private $server;

    public function __construct($address, $port) {
        $this->server = new Server($address, $port);

        $this->server->registerHandler([$this, 'onServerEvent']);

        try {
            $this->server->initialize();
        }
        catch(\Exception $e) {
            die("Server failed to initialize with: " . $e->getMessage());
        }
    }

    public function run() {
        $this->server->run();
    }

    public function onServerEvent($event) {
        if( $event instanceof ServerStartEvent ) {
            echo "Server started\n";
        }
        else if( $event instanceof PlayerLogoffEvent) {
            echo "Player {$event->player->name()} has disconnected\n";
        }
        else if( $event instanceof PlayerLoginEvent) {
            echo "Player {$event->player->name()} connected\n";
        }
        else {
            echo "Got unknown event " . $event::class . "\n";
        }
    }
}