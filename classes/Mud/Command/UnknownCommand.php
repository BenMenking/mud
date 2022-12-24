<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Terminal;

class UnknownCommand extends Command {
    public function perform() {
        parent::perform();

        $this->player->sendMessage(Terminal::YELLOW . "Command unknown\r\n" . Terminal::RESET);
    }
}
