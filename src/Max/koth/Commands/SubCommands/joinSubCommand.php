<?php

declare(strict_types=1);

namespace Max\koth\Commands\SubCommands;

use CortexPE\Commando\BaseSubCommand;
use Max\koth\KOTH;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class joinSubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.join");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		$koth = KOTH::getInstance();
		$arena = $koth->current;
		if (is_null($arena)) {
			$sender->sendMessage("§7[§bKOTH§7] §cThere is no KOTH event currently running.");
			return;
		}
		$spawn = $arena->getSpawn();
		if (is_null($spawn)) {
			$sender->sendMessage("§7[§bKOTH§7] §cThe KOTH arena has no spawn point.");
			return;
		}
		if ($sender instanceof Player){
			$sender->teleport(new Position($spawn["x"], $spawn["y"], $spawn["z"], Server::getInstance()->getWorldManager()->getWorldByName($arena->getWorld())));
			$sender->sendMessage("§7[§bKOTH§7] §aTeleported to KOTH event.");
		}
	}
}