<?php

namespace Menking\Mud\Core\Event;

use Menking\Mud\Core\Server;

class ServerTickEvent extends Event {
    public readonly Server $server;

    public function __construct(Server $server) {
        $this->server = $server;
    }
}