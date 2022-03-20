<?php

declare(strict_types=1);

namespace Max\koth\Commands\SubCommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Max\koth\KOTH;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class pos1SubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.pos1");
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
			$arena->setMin([
				"x" => $pos->getX(),
				"y" => $pos->getY(),
				"z" => $pos->getZ()
			]);
			$arena->setWorld($sender->getWorld()->getFolderName());
			$sender->sendMessage("§7[§bKOTH§7] §aSet pos1 for arena.");
		}
	}
}