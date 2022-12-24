<?php

namespace Menking\Mud\States;

use Menking\Mud\Command\Command;
use Menking\Mud\Command\LookCommand;
use Menking\Mud\Command\WhoCommand;
use Menking\Mud\Command\CommCommand;

class IncapacitatedState extends PlayerStates {
    public function __construct() {
        $this->name = "incapacitated";
    }
    
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof LookCommand:
            case $command instanceof WhoCommand:
            case $command instanceof CommCommand:
                $command->perform();
            break;
            default:
                return "You cannot perform that action while incapacitated\r\n";
        }
    }
}
