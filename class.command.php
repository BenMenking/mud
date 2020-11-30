<?php

class CommCommand extends Command {
    public function perform() {
        parent::perform();

        switch($this->cmds[0]) {
            case 'shout':
            case 'gossip':
                $cmds = $this->cmds;
                array_shift($cmds);

                foreach($this->world->getPlayers() as $player) {
                    if( $player != $this->player ) {
                        $player->sendMessage("{$this->player->name()} shouts '" . implode(' ', $cmds) . "'\r\n");
                    }
                }
                return "You shout '" . implode(' ', $cmds) . "'\r\n";
            break;
            case 'say':
                $cmds = $this->cmds;
                array_shift($cmds);

                foreach($this->world->getPlayers() as $player) {
                    if( $player->room() == $this->player->room() ) {
                        $player->sendMessage("{$this->player->name()} says '" . implode(' ', $cmds) . "'\r\n");
                    }
                }
                return null;
            break;
            case 'tell':
                $cmds = $this->cmds;
                array_shift($cmds);
                $target_name = array_shift($cmds);

                try {
                    $target_player = Player::load($target_name);

                    if( $target_player->online() ) {
                        $target_player->sendMessage("{$this->player->name()} tells you '" . $implode(' ' , $cmds) . "'\r\n");
                    }
                }
                catch(Exception $e) {
                    return "Sorry, that player does not exist\r\n";
                }
            break;
        }
    }
}

class UnknownCommand extends Command {
    public function perform() {
        parent::perform();

        return Terminal::YELLOW . "Command unknown\r\n" . Terminal::RESET;
    }
}

class ExitsCommand extends Command {
    public function perform() {
        parent::perform();

        return Terminal::LIGHT_CYAN . "Exits: " . $this->player->room()->exits() . "\r\n" . Terminal::RESET;
    }
}

class WhoCommand extends Command {
    public function perform() {
        parent::perform();

        $players = $this->world->getPlayers();

        $str = "+-----------------------------------------------+\r\n";
        $width = strlen($str) - 3;

        foreach($players as $player) {
            $str .= str_pad("| " . ucfirst($player->name()), $width) . "|\r\n";
        }
        $str .= "+-----------------------------------------------+\r\n\r\n";

        return $str;
    }
}

class LookCommand extends Command {
    public function perform() {
        parent::perform();

        return Terminal::BOLD . Terminal::LIGHT_WHITE . $this->player->room()->name() . Terminal::RESET . "\r\n" 
            . $this->player->room()->description() . "\r\n" . Terminal::LIGHT_CYAN . "[Exits: " 
            . $this->player->room()->exits() . "]\r\n\r\n" . Terminal::RESET;
    }
}

class MoveCommand extends Command {
    public function perform() {
        parent::perform();

        $exits = explode(',', $this->player->room()->exits(','));

        if( in_array($this->cmds[0], $exits) ) {
            $room = $this->player->room()->getWorld()->traverse($this->player->room(), $this->cmds[0]);
            
            if( is_null($room) ) {
                return "You cannot go that direction\r\n";
            }
            else {
                $this->player->setRoom($room);
                return Terminal::BOLD . Terminal::LIGHT_WHITE . $this->player->room()->name() . Terminal::RESET . "\r\n" 
                    . $this->player->room()->description() . "\r\n" . Terminal::LIGHT_CYAN . "[Exits: " 
                    . $this->player->room()->exits() . "]\r\n\r\n" . Terminal::RESET;
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

class Command {
    protected $player, $cmds;

    public function __constuct(Player $player, Array $cmds) {
        $this->player = $player;
        $this->cmds = $cmds;
    }

    public function argc() {
        return count($this->cmds);
    }

    public function argv($index) {
        return isset($this->cmds[$index])?$this->cmds[$index]:null;
    }

    public function perform() { 
        return null;
    }

    public function __toString() {
        return get_class($this);
    }
}

class CommandFactory {
    public static function factory(Array $cmds, Player $player) {
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
            default:
                return new UnknownCommand($player, $cmds);
        }
    }

}
