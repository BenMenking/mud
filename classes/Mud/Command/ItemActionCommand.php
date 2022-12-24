<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Terminal;

class ItemActionCommand extends Command {
    public function perform() {
        parent::perform();

        $cmd = $this->cmds[0];
        switch($this->cmds[0]) {
            case 'put':
            case 'get':
            case 'take':
            case 'drop':
        }
        $this->player->sendMessage(Terminal::YELLOW . "Sorry, command '{$this->cmds[0]}' isn't implemented yet.\r\n" . Terminal::RESET);
    }
}
