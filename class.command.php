<?php

class HelpCommand extends Command {

}

class CommCommand extends Command {

}

class UnknownCommand extends Command {

}

class ExitsCommand extends Command {
    public function perform() {
        parent::perform();

        return "Exits: " . $this->player->room->exits() . "\r\n";
    }
}

class WhoCommand extends Command {
    public function perform() {
        parent::perform();

        return "Not implemented\r\n";
    }
}

class LookCommand extends Command {
    public function perform() {
        parent::perform();

        return $this->player->room->name() . "\r\n" . $this->player->room->description() . "\r\n[Exits: " . $this->player->room->exits() . "]\r\n\r\n";
    }
}

class MoveCommand extends Command {
    public function perform() {
        parent::perform();

        $exits = explode(',', $this->player->room->exits(','));

        if( in_array($this->cmds[0], $exits) ) {
            $room = $this->player->room->getWorld()->traverse($this->player->room, $this->cmds[0]);
            
            if( is_null($room) ) {
                return "You cannot go that direction\r\n";
            }
            else {
                $this->player->setRoom($room);
                return $this->player->room->name() . "\r\n" . $this->player->room->description()
                    . "\r\n[Exits: " . $this->player->room->exits() . "]\r\n\r\n";
            }
        }
        else {
            return "You cannot go that way\r\n";
        }
    }
}

class QuitCommand extends Command {
    // it tis what it is
}

class KillCommand extends Command {
    //starts the combat loop
}



class Command {
    protected $player, $world, $cmds;

    public function __construct(Player $player, World $world, Array $cmds) {
        $this->player = $player;
        $this->world = $world;
        $this->cmds = $cmds;
    }

    public function perform() { 
        return null;
    }

    public function argc() {
        return count($this->cmds);
    }

    public function argv($index) {
        return isset($this->cmds[$index])?$this->cmds[$index]:null;
    }
}

class InputHandler {
    // this file maps input commands to command Classes/Objects
    private $player, $world;

    public function __construct(Player $player, World $world) {
        $this->player = $player;
        $this->world = $world;
    }

    public function execute($cmd) {
        $cmds = $this->explode_ex(' ', $cmd);

        // blank commands shouldn't get here, but we'll double-check anyway
        //
        if( count($cmds) == 0 ) return $this->constructCommand("UnknownCommand", $cmds);

        echo "[InputHandler->execute] Got " . json_encode($cmds) . "\n";

        switch($cmds[0]) {
            case 'quit':
            case 'q':
                return $this->constructCommand("QuitCommand", $cmds);
            break;
            case 'look':
            case 'l':
                return $this->constructCommand("LookCommand", $cmds);
            break;
            case 'north':
            case 'n':
            case 'south':
            case 's':
            case 'east':
            case 'e':
            case 'west':
            case 'w':
            case 'up':
            case 'u':
            case 'down':
            case 'd':
                return $this->constructCommand("MoveCommand", $cmds);
            break;
            case 'who':
                return $this->constructCommand("WhoCommand", $cmds);
            break;
            case 'help':
                return $this->constructCommand("HelpCommand", $cmds);
            break;
            case 'say':
            case 'shout':
            case 'gossip':
            case 'tell':
                return $this->constructCommand("CommCommand", $cmds);
            break;
            case 'inventory':
            case 'i':
                return $this->constructCommand("InventoryCommand", $cmds);
            break;
            case 'equipment':
            case 'eq':
                return $this->constructCommand("EquipmentCommand", $cmds);
            break;
            case 'get':
            case 'drop':
            case 'wear':
            case 'remove':
            case 'wield':
            case 'hold':
                return $this->constructCommand("ActionCommand", $cmds);
            break;
            case 'score':
                return $this->constructCommand("ScoreCommand", $cmds);
            break;
            case 'sleep':
            case 'rest':
            case 'wake':
            case 'stand':
                return $this->constructCommand("StateCommand", $cmds);
            break;
            case 'consider':
            case 'con':
                return $this->constructCommand("AssessCommand", $cmds);
            break;
            case 'kill':
                return $this->constructCommand("AttackCommand", $cmds);
            break;
            case 'assist':
                return $this->constructCommand("AssistCommand", $cmds);
            break;
            case 'flee':
                return $this->constructCommand("FleeCommand", $cmds);
            break;
            case 'cast':
                return $this->constructCommand("CastCommand", $cmds);
            break;
            case 'rent':
                return $this->constructCommand("RentCommand", $cmds);
            break;
            case 'eat':
            case 'drink':
            case 'quaff':
                return $this->constructCommand("DrinkCommand", $cmds);
            break;
            case 'time':
                return $this->constructCommand("ServerCommand", $cmds);
            break;
            case 'aff':
                return $this->constructCommand("AffectsCommand", $cmds);
            break;
            default:
                return $this->constructCommand("UnknownCommand", $cmds);
        }
    }

    private function constructCommand($command_class, Array $cmds) {
        return new $command_class($this->player, $this->world, $cmds);
    }

    private function explode_ex($delimiter, $str) {
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