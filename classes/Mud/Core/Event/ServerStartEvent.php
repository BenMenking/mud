<?php

namespace Menking\Mud\Core\Event;

use Menking\Mud\Core\Server;

class ServerStartEvent extends Event {
    public readonly Server $server;

    public function __construct(Server $server) {
        parent::__construct(Event::SERVER_START_EVENT);

        $this->server = $server;
    }
}