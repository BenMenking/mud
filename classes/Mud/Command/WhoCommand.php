<?php

namespace Menking\Mud\Command;

class WhoCommand extends Command {
    public function perform() {
        parent::perform();

        $world = $this->player->room()->getWorld();

        $players = $world->getPlayers();

        $str = "+-----------------------------------------------+\r\n";
        $width = strlen($str) - 3;

        foreach($players as $player) {
            $str .= str_pad("| " . ucfirst($player->name()), $width) . "|\r\n";
        }
        $str .= "+-----------------------------------------------+\r\n\r\n";

        $this->player->sendMessage($str);
    }
}
