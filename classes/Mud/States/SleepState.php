<?php

namespace Menking\Mud\States;

use Menking\Mud\Command\Command;
use Menking\Mud\Command\WhoCommand;
use Menking\Mud\Command\StandCommand;
use Menking\Mud\Command\CommCommand;
use Menking\Mud\Command\InventoryCommand;
use Menking\Mud\Command\RestCommand;

class SleepState extends PlayerStates {
    public function __construct() {
        $this->name = "sleeping";
    }
    
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof WhoCommand:
            case $command instanceof CommCommand:
            case $command instanceof StandCommand:
            case $command instanceof RestCommand:
            case $command instanceof InventoryCommand:
                return $command->perform();
            break;
            default:
                return "You cannot perform that action while sleeping\r\n";
        }
    }
}
