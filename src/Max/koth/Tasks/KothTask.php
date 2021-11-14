<?php

namespace Max\koth\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;

use Ifera\ScoreHud\event\ServerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;

class KothTask extends Task {

    public $time_in_arena = [];
    public $king_time = [];

    public function __construct($pl, $arenaName) {
        $this->plugin = $pl;
        $this->arenaName = $arenaName;
    }

    public function onRun(int $currentTick) {
    	$arenaData = $this->plugin->data->get($this->arenaName);
        $pos1 = explode(":", $arenaData["position1"]);
        $pos2 = explode(":", $arenaData["position2"]);
        $minX = min($pos1[0], $pos2[0]);
        $maxX = max($pos1[0], $pos2[0]);
        $minY = min($pos1[1], $pos2[1]);
        $maxY = max($pos1[1], $pos2[1]);
        $minZ = min($pos1[2], $pos2[2]);
        $maxZ = max($pos1[2], $pos2[2]);

		$time = time();

		foreach (Server::getInstance()->getOnlinePlayers() as $player) {
		    if(($minX <= $player->getX() && $player->getX() <= $maxX && $minY <= $player->getY() && $player->getY() <= $maxY && $minZ <= $player->getZ() && $player->getZ() <= $maxZ)){
				if (!isset($this->time_in_arena[$player->getName()])) {
					$this->time_in_arena[$player->getName()] = $time - 1;
				}
		    } else {
				unset($this->time_in_arena[$player->getName()]);
				unset($this->king_time[$player->getName()]);
			}
			if ($this->plugin->config->get("bossbar")) $this->plugin->bar->addPlayer($player);
		}

		if (empty($this->time_in_arena)) {
			$kingName = "";
			$kingTime = $time;
		} else {
			$kingName = array_keys($this->time_in_arena, min($this->time_in_arena))[0];
			if (!isset($this->king_time[$kingName])) $this->king_time[$kingName] = $time - 1;
			$kingTime = $this->king_time[$kingName];
    	}

        $total_capture_time = $this->plugin->config->get("capture_time");
        $current_capture_time = $time - $kingTime;
        $minutes = floor(($total_capture_time - $current_capture_time)/60);
        $seconds = sprintf("%02d", (($total_capture_time - $current_capture_time) - ($minutes * 60)));
		if ($this->plugin->config->get("bossbar")) {
			$this->plugin->bar->setTitle("§bKOTH: §c".$this->arenaName);
			$this->plugin->bar->setSubTitle("§r§m" . $minutes . ":" . $seconds . "  |  " . "King: " . $kingName);
			$this->plugin->bar->setPercentage(round(($current_capture_time / $total_capture_time), 2) + 0.01);
		}
		if ($this->plugin->config->get("hotbar")) {
			foreach (Server::getInstance()->getOnlinePlayers() as $player) {
				$player->sendTip("§bKOTH: §c".$this->arenaName."\n§r§mTime: ".$minutes.":".$seconds." | King: ".$kingName);
			}
		}
		if (isset($this->plugin->scorehud)) {
			(new ServerTagsUpdateEvent([
				new ScoreTag("koth.name", $this->arenaName),
				new ScoreTag("koth.king", $kingName),
				new ScoreTag("koth.time", $minutes . ":" . $seconds)
			]))->call();
		}

        if ($current_capture_time >= $total_capture_time) {
            $this->plugin->StopKoth($kingName);
        }
    }
}
