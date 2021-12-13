<?php

namespace Max\koth\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;

use Ifera\ScoreHud\event\ServerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;

class KothTask extends Task {

    public $arena_enter_time = [];
    public $king_time = [
    	"name" => "",
		"time" => 0
	];

    public function __construct($pl, $arenaName) {
        $this->plugin = $pl;
        $this->arenaName = $arenaName;
    }

    public function onRun(int $currentTick) {
    	$arenaData = $this->plugin->data->get($this->arenaName);
    	$world = $arenaData["world"];
        $pos1 = explode(":", $arenaData["position1"]);
        $pos2 = explode(":", $arenaData["position2"]);
        $minX = min($pos1[0], $pos2[0]);
        $maxX = max($pos1[0], $pos2[0]);
        $minY = min($pos1[1], $pos2[1]);
        $maxY = max($pos1[1], $pos2[1]);
        $minZ = min($pos1[2], $pos2[2]);
        $maxZ = max($pos1[2], $pos2[2]);

		$time = time();
		$arena_enter_time = [];

		foreach (Server::getInstance()->getOnlinePlayers() as $player) {
			if ($player->getLevel()->getFolderName() == $world) {
				if (($minX <= $player->getX() && $player->getX() <= $maxX && $minY <= $player->getY() && $player->getY() <= $maxY && $minZ <= $player->getZ() && $player->getZ() <= $maxZ)) {
					if (isset($this->arena_enter_time[$player->getName()])) {
						$arena_enter_time[$player->getName()] = $this->arena_enter_time[$player->getName()]; #If player was already in the arena, keep their time as the original.
					} else {
						$this->arena_enter_time[$player->getName()] = $time - 1; #If the player is entering the arena, set their time.
						$arena_enter_time[$player->getName()] = $time - 1;
					}
				} else {
					unset($this->arena_enter_time[$player->getName()]); #If player is outside the arena, unset their time.
					unset($this->king_time[$player->getName()]);
				}
			}
			if ($this->plugin->config->get("bossbar")) $this->plugin->bar->addPlayer($player);
		}

		$this->arena_enter_time = $arena_enter_time;

		if (empty($this->arena_enter_time)) {
			$this->king_time["name"] = "";
			$this->king_time["time"] = $time;
		} else {
			$kingName = array_keys($this->arena_enter_time, min($this->arena_enter_time))[0];
			if ($this->king_time["name"] !== $kingName) {
				$this->king_time["name"] = $kingName;
				$this->king_time["time"] = $time - 1;
			}
    	}

		$kingName = $this->king_time["name"];
		$kingTime = $this->king_time["time"];

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
