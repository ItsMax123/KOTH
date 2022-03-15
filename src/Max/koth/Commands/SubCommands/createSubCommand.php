<?php

declare(strict_types=1);

namespace Max\koth\Commands\SubCommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Max\koth\Arena;
use Max\koth\KOTH;
use pocketmine\command\CommandSender;

class createSubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.create");
		$this->registerArgument(0, new RawStringArgument("Arena name", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		KOTH::getInstance()->arenas[$args["Arena name"]] = new Arena($args["Arena name"]);
		$sender->sendMessage("§7[§bKOTH§7] §aCreated arena.");
	}
}