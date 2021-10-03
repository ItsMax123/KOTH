<?php

namespace Max\koth\Tasks;

use pocketmine\scheduler\Task;

class StartKothTask extends Task {

    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function onRun(int $currentTick) {
        $this->plugin->StartKoth();
    }
}