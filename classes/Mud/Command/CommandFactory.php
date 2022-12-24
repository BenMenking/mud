<?php

namespace Menking\Mud\Command;

use Menking\Mud\Core\Player;

class CommandFactory {
    public static function factory(String $command, Player $player) {
        $cmds = CommandFactory::explode_ex(' ', $command);

        switch($cmds[0]) {
            case 'north': case 'n': case 'south': case 's': case 'east': case 'e':
            case 'west': case 'w': case 'up': case 'u': case 'down': case 'd':
                return new MoveCommand($player, $cmds);
            case 'quit': case 'q':
                return new QuitCommand($player, $cmds);
            case 'who': case 'w':
                return new WhoCommand($player, $cmds);
            case 'say': case 'gossip': case 'shout': case 'tell':
                return new CommCommand($player, $cmds);
            case 'look': case 'l':
                return new LookCommand($player, $cmds);
            case 'stand': case 'st':
                return new StandCommand($player, $cmds);
            case 'rest': case 'sit':  case 'wake':
                return new RestCommand($player, $cmds);
            case 'sleep': 
                return new SleepCommand($player, $cmds);
            case 'inventory': case 'i':
                return new InventoryCommand($player, $cmds);
            case 'put': case 'get': case 'drop':
                return new ItemActionCommand($player, $cmds);
            case 'exits': case 'ex':
                return new ExitsCommand($player, $cmds);
            default:
                return new UnknownCommand($player, $cmds);
        }
    }

    private static function explode_ex($delimiter, $str) {
        $parts = [];
        $part = '';

        for($i = 0; $i < strlen($str); $i++) {
            if( $str[$i] == $delimiter && strlen($part) > 0 ) {
                $parts[] = $part;
                $part = '';
            }
            else {
                $part .= $str[$i];
            }		
        }

        if( strlen($part) > 0 ) {
            $parts[] = $part;
        }

        return $parts;
    }    

}
