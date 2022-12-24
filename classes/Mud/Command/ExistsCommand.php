<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Terminal;

class ExitsCommand extends Command {
    public function perform() {
        parent::perform();

        $txt = '';
        foreach($this->player->room()->exits() as $direction=>$exit) {
            $txt .= $direction . ' - ' . $exit['description'] . "\r\n";
        }
        $this->player->sendMessage(Terminal::LIGHT_CYAN . "Visible exits: \r\n$txt" . Terminal::RESET);
    }
}
