<?php

namespace Menking\Mud\Command;

class QuitCommand extends Command {
    public function perform() {
        parent::perform();

        $this->player->save();
    }
}
