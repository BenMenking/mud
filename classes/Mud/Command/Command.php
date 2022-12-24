<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Player;

class Command {
    protected $player, $cmds;

    public function __construct(Player $player, Array $cmds) {
        $this->player = $player;
        $this->cmds = $cmds;
    }

    public function argc() {
        return count($this->cmds);
    }

    public function argv($index) {
        return isset($this->cmds[$index])?$this->cmds[$index]:null;
    }

    public function perform() { 
        return null;
    }

    public function __toString() {
        return get_class($this);
    }
}
