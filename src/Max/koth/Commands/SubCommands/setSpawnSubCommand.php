<?php

declare(strict_types=1);

namespace Max\koth\Commands\SubCommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Max\koth\KOTH;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class setSpawnSubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.setspawn");
		$this->registerArgument(0, new RawStringArgument("Arena name", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		$koth = KOTH::getInstance();
		$arena = $koth->getArena($args["Arena name"]);
		if (!$arena) {
			$sender->sendMessage("§7[§bKOTH§7] §cThat arena does not exist.");
			return;
		}
		if ($sender instanceof Player){
			$pos = $sender->getPosition();
			$arena->setSpawn([
				"x" => $pos->getX(),
				"y" => $pos->getY(),
				"z" => $pos->getZ()
			]);
			$sender->sendMessage("§7[§bKOTH§7] §aSet spawn for arena.");
		}
	}
}