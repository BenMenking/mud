<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Terminal;

class LookCommand extends Command {
    public function perform() {
        parent::perform();

        $things = [];

        foreach($this->player->room()->getWorld()->playersInRoom($this->player->room()) as $resident) {
            if( $resident != $this->player ) {
                $things[] = "{$resident->name()} is {$resident->state->name} here.";
            }
        }

        $this->player->sendMessage(Terminal::BOLD . Terminal::LIGHT_WHITE . $this->player->room()->name() . Terminal::RESET . "\r\n" 
            . $this->player->room()->description() . "\r\n" . Terminal::LIGHT_CYAN . "[Exits: " 
            . implode(' ', array_keys($this->player->room()->exits())) . "]\r\n\r\n" . Terminal::LIGHT_BLACK . implode("\r\n", $things) . "\r\n\r\n" . Terminal::RESET);
    }
}
