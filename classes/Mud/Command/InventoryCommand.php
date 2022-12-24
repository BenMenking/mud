<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Terminal;

class InventoryCommand extends Command {
    public function perform() {
        parent::perform();

        $txt = '';
        foreach($this->player->listInventory() as $item) {
            $txt .= $item->name() . "\r\n";
        }

        if( count($this->player->listInventory()) == 0 ) {
            $txt = Terminal::YELLOW . "There are no items in your inventory.\r\n" . Terminal::RESET;
        }

        $this->player->sendMessage($txt);
    }
}
