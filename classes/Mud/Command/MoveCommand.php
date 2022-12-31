<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Terminal;

class MoveCommand extends Command {
    public function perform() {
        parent::perform();

        $exits = array_keys($this->player->room()->exits());

        $command_map = ['n'=>'north', 's'=>'south', 'w'=>'west', 'e'=>'east', 'u'=>'up', 'd'=>'down'];

        if( in_array($this->cmds[0], array_keys($command_map)) ) {
            $this->cmds[0] = $command_map[$this->cmds[0]];
        }

        if( in_array(trim($this->cmds[0]), $exits) ) {          
            $fromRoom = $this->player->room();
            $destRoom = $this->player->room()->area()->traverse($this->player->room(), $this->cmds[0]);
            
            if( is_null($destRoom) ) {
                $this->player->sendMessage(Terminal::YELLOW . "You cannot go that direction\r\n" . Terminal::RESET);
            }
            else {
                foreach($this->player->room()->area()->playersInRoom($fromRoom) as $resident) {
                    if( $resident != $this->player ) {
                        $resident->sendMessage("{$this->player->name()} leaves.\r\n");
                    }
                }
                foreach($this->player->room()->area()->playersInRoom($destRoom) as $resident) {
                    $resident->sendMessage("{$this->player->name()} arrives.\r\n");
                }
                $this->player->setRoom($destRoom);
                $this->player->executeCommand('look'); // not convinced this is the best way to do this
            }
        }
        else {
            $this->player->sendMessage(Terminal::YELLOW . "You cannot go that way\r\n" . Terminal::RESET);
        }
    }
}