<?php

namespace Menking\Mud\Core\Event;

use Menking\Mud\Core\Player;

class PlayerLoginEvent extends Event {
    public readonly Player $player;

    public function __construct(Player $player) {
        $this->player = $player;
    }
}