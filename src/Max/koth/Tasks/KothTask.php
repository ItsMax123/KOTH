<?php

namespace Max\koth\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;

use Ifera\ScoreHud\event\ServerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;

class KothTask extends Task {

    public $players_in_zone_old = [];

    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function onRun(int $currentTick) {
        $pos1 = explode(":", $this->plugin->data->get("position1"));
        $pos2 = explode(":", $this->plugin->data->get("position2"));
        $minX = min($pos1[0], $pos2[0]);
        $maxX = max($pos1[0], $pos2[0]);
        $minY = min($pos1[1], $pos2[1]);
        $maxY = max($pos1[1], $pos2[1]);
        $minZ = min($pos1[2], $pos2[2]);
        $maxZ = max($pos1[2], $pos2[2]);

        $players_in_zone = [];
        $time = time();

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if(($minX <= $player->getX() && $player->getX() <= $maxX && $minY <= $player->getY() && $player->getY() <= $maxY && $minZ <= $player->getZ() && $player->getZ() <= $maxZ)){
                $players_in_zone[$player->getName()] = $time - 1;
            }
			if ($this->plugin->config->get("bossbar")) $this->plugin->bar->addPlayer($player);
        }

        foreach ($players_in_zone as $playerName => $playerTime) {
            if (isset($this->players_in_zone_old[$playerName])) {
                $players_in_zone[$playerName] = $this->players_in_zone_old[$playerName];
            }
        }

        $this->players_in_zone_old = $players_in_zone;

        if (!empty($players_in_zone)) {
            $kingTime = min($players_in_zone);
            $kingName = array_keys($players_in_zone, $kingTime)[0];
        } else {
            $kingName = "";
            $kingTime = $time;
        }

        $total_capture_time = $this->plugin->config->get("capture_time");
        $current_capture_time = $time - $kingTime;
        $minutes = floor(($total_capture_time - $current_capture_time)/60);
        $seconds = sprintf("%02d", (($total_capture_time - $current_capture_time) - ($minutes * 60)));
		if ($this->plugin->config->get("bossbar")) {
			$this->plugin->bar->setTitle("§cKing Of The Hill §7(§bKOTH§7)");
			$this->plugin->bar->setSubTitle("§m" . $minutes . ":" . $seconds . "  |  " . "King: " . $kingName);
			$this->plugin->bar->setPercentage(round(($current_capture_time / $total_capture_time), 2) + 0.01);
		}
		if ($this->plugin->config->get("hotbar")) {
			foreach (Server::getInstance()->getOnlinePlayers() as $player) {
				$player->sendPopup("§mTime: ".$minutes.":".$seconds." | King: ".$kingName);
			}
		}
		if (isset($this->plugin->scorehud)) {
			(new ServerTagsUpdateEvent([
				new ScoreTag("koth.king", $kingName),
				new ScoreTag("koth.time", $minutes . ":" . $seconds)
			]))->call();
		}

        if ($time - $kingTime >= $total_capture_time) {
            $this->plugin->StopKoth($kingName);
        }
    }
}