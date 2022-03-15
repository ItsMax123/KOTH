<?php

namespace Max\koth\Tasks;

use Max\koth\Arena;
use Max\koth\KOTH;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class KothTask extends Task {

	private KOTH $pl;
	private ?Player $king;
	private string $kingName;
	private Arena $arena;
	private int $captureTime;

	public function __construct(KOTH $pl, Arena $arena) {
        $this->pl = $pl;
        $this->arena = $arena;
    }

    public function onRun() : void{
		if(isset($this->king) and $this->king->isOnline() and $this->arena->isInside($this->king)){
			if(time() - $this->captureTime >= $this->pl->CAPTURE_TIME){
				$this->pl->stopKoth($this->kingName);
			}
		}else{
			$this->king = null;
			$this->kingName = "...";
			$this->captureTime = time();
			$onlinePlayers = Server::getInstance()->getOnlinePlayers();
			shuffle($onlinePlayers);
			foreach($onlinePlayers as $player){
				if($this->arena->isInside($player)){
					$this->king = $player;
					$this->kingName = $player->getName();
					break;
				}
			}
		}

		$timeLeft = $this->pl->CAPTURE_TIME - (time() - $this->captureTime);
		$minutes = floor($timeLeft/60);
		$seconds = sprintf("%02d", ($timeLeft - ($minutes * 60)));

		if ($this->pl->USE_BOSSBAR) {
			foreach (Server::getInstance()->getOnlinePlayers() as $player){
				$this->pl->bar->removePlayer($player);
				$this->pl->bar->setTitle("§bKOTH: §c" . $this->arena->getName() . "§r - §bTime: §c" . $minutes . ":" . $seconds);
				$this->pl->bar->setSubTitle("§bKing: §c" . $this->kingName);
				$this->pl->bar->setPercentage($timeLeft / $this->pl->CAPTURE_TIME);
				$this->pl->bar->addPlayer($player);
			}
		}

		if ($this->pl->SEND_TIPS){
			foreach(Server::getInstance()->getOnlinePlayers() as $player) {
				$player->sendTip("§bKOTH: §c".$this->arena->getName() . "§r - §bTime: §c" . $minutes . ":" . $seconds . "\n§bKing: §c" . $this->kingName);
			}
		}
    }
}
