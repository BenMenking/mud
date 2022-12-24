<?php

namespace Menking\Mud\Core\Event;

class Event {
    public readonly int $type;

    public function __construct($type) {
        $this->type = $type;
    }
    
    final public const SERVER_START_EVENT = 1;
    final public const SERVER_STOP_EVENT = 2;
    final public const SERVER_FAULT_EVENT = 3;
}