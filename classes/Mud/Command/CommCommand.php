<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\World;
use Menking\Mud\Core\Terminal;

class CommCommand extends Command {
    public function perform() {
        parent::perform();

        $world = $this->player->room()->getWorld();

        switch($this->cmds[0]) {
            case 'shout':
            case 'gossip':
                $cmds = $this->cmds;
                array_shift($cmds);

                foreach($world->getPlayers() as $player) {
                    if( $player != $this->player ) {
                        $player->sendMessage("{$this->player->name()} shouts '" . implode(' ', $cmds) . "'\r\n");
                    }
                }
                $this->player->sendMessage("You shout '" . implode(' ', $cmds) . "'\r\n");
            break;
            case 'say':
                $cmds = $this->cmds;
                array_shift($cmds);

                foreach($world->getPlayers() as $player) {
                    if( $player->room() == $this->player->room() ) {
                        $player->sendMessage("{$this->player->name()} says '" . implode(' ', $cmds) . "'\r\n");
                    }
                }
                $this->player->sendMessage("You say '" . implode(' ', $cmds) . "\r\n");
            break;
            case 'tell':
            case 't':
                $cmds = $this->cmds;
                array_shift($cmds);
                $target_name = array_shift($cmds);

                try {
                    $world = World::getInstance($this->player->room()->getWorld()->name());
                    $target_player = $world->getPlayer($target_name);

                    if( $target_player ) {
                        $target_player->sendMessage(Terminal::LIGHT_BLACK . "{$this->player->name()} tells you " . Terminal::RESET . "'" . implode(' ' , $cmds) . "'\r\n");
                        $this->player->sendMessage(Terminal::GREEN . "You tell {$target_player->name()} '" . implode(' ', $cmds) . "\r\n" . Terminal::RESET);
                    }
                    else {
                        $this->player->sendMessage(Terminal::RED . "{$target_name} is not able to hear you.\r\n" . Terminal::RESET);
                    }
                }
                catch(\Exception $e) {
                    $this->player->sendMessage(Terminal::RED . "Sorry, that player does not exist\r\n" . Terminal::RESET);
                }
            break;
        }
    }
}
