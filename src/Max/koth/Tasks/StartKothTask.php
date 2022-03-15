<?php

namespace Max\koth\Tasks;

use Max\koth\KOTH;
use pocketmine\scheduler\Task;

class StartKothTask extends Task {

	public KOTH $pl;

	public function __construct(KOTH $pl) {
		$this->pl = $pl;
	}

	public function onRun() : void {
		if (in_array((float)date("G.i"), $this->pl->START_TIMES)){
			$this->pl->startKoth($this->pl->getArena());
		}
	}
}