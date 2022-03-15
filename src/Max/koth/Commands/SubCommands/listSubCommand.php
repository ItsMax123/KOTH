<?php

declare(strict_types=1);

namespace Max\koth\Commands\SubCommands;

use CortexPE\Commando\BaseSubCommand;
use Max\koth\KOTH;
use pocketmine\command\CommandSender;

class listSubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.list");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		$koth = KOTH::getInstance();
		if (!$koth->getArena()) {
			$sender->sendMessage("§7[§bKOTH§7] §cThere are no KOTH arenas.");
			return;
		}
		$sender->sendMessage("§7[§bKOTH§7] §aList of all KOTH arenas:");
		foreach ($koth->data->getAll() as $arenaName => $arenaData) {
			$sender->sendMessage(" - §b".$arenaName."§r: ".$arenaData["coords"]);
		}
	}
}