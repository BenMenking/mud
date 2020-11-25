<?php

class UnknownCommand extends Command {

}

class LookCommand extends Command {
    public function perform() {
        parent::perform();

        return $this->user->room->name() . "\r\n" . $this->user->room->description() . "\r\n[Exits: " . $this->user->room->exits() . "]\r\n\r\n";
    }
}

class MoveCommand extends Command {
    public function perform() {
        parent::perform();

        return "You can't do that right now\r\n";
    }
}

class QuitCommand extends Command {
    // it tis what it is
}

class WhoCommand extends Command {
    //get list of players online
}

class KillCommand extends Command {
    //starts the combat loop
}



class Command {
    protected $user, $world, $cmds;

    public function __construct(User $user, World $world, Array $cmds) {
        $this->user = $user;
        $this->world = $world;
        $this->cmds = $cmds;
    }

    public function perform() { 
        return null;
    }
}

class InputHandler {
    // this file maps input commands to command Classes/Objects
    private $user, $world;

    public function __construct(User $user, World $world) {
        $this->user = $user;
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
            default:
                return $this->constructCommand("UnknownCommand", $cmds);
        }
    }

    private function constructCommand($command_class, Array $cmds) {
        return new $command_class($this->user, $this->world, $cmds);
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