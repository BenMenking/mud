<?php

namespace Menking\Mud\Command;

use Menking\Mud\States\RestState;

class RestCommand extends Command {
    public function perform() {
        parent::perform();

        $this->player->state = new RestState();
        $this->player->sendMessage("You sit.");
        $room = $this->player->room();
        foreach($this->player->room()->getWorld()->playersInRoom($room) as $resident) {
            if( $resident != $this->player ) {
                $resident->sendMessage("{$this->player->name()} sits down.\r\n");
            }
        }
    }
}
