<?php

namespace Menking\Mud\Command;

use Menking\Mud\States\StandingState;

class StandCommand extends Command {
    public function perform() {
        parent::perform();

        $this->player->state = new StandingState();
        $this->player->sendMessage("You stand.");
        $room = $this->player->room();
        foreach($this->player->room()->getWorld()->playersInRoom($room) as $resident) {
            if( $resident != $this->player ) {
                $resident->sendMessage("{$this->player->name()} stands up.\r\n");
            }
        }
    }
}
