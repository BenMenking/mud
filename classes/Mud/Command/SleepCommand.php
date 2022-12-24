<?php

namespace Menking\Mud\Command;

class SleepCommand extends Command {
    public function perform() {
        parent::perform();

        $this->player->state = new SleepState();
        $this->player->sendMessage("You go to sleep.");
        $room = $this->player->room();
        foreach($this->player->room()->getWorld()->playersInRoom($room) as $resident) {
            if( $resident != $this->player ) {
                $resident->sendMessage("{$this->player->name()} goes to sleep.\r\n");
            }
        }
    }
}
