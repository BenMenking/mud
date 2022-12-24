<?php

namespace Menking\Mud\States;

use Menking\Mud\Command\Command;
use Menking\Mud\Command\LookCommand;
use Menking\Mud\Command\SleepCommand;
use Menking\Mud\Command\WhoCommand;
use Menking\Mud\Command\StandCommand;
use Menking\Mud\Command\CommCommand;
use Menking\Mud\Command\InventoryCommand;
use Menking\Mud\Command\ItemActionCommand;
use Menking\Mud\Command\ExitsCommand;

class RestState extends PlayerStates {
    public function __construct() {
        $this->name = "resting";
    }
    
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof LookCommand:
            case $command instanceof WhoCommand:
            case $command instanceof CommCommand:
            case $command instanceof StandCommand:
            case $command instanceof SleepCommand:
            case $command instanceof InventoryCommand:
            case $command instanceof ItemActionCommand:
            case $command instanceof ExitsCommand:
                return $command->perform();
            break;
            default:
                return "You cannot perform that action while resting\r\n";
        }
    }
}