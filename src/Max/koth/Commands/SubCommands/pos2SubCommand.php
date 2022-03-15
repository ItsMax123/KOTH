<?php

declare(strict_types=1);

namespace Max\koth\Commands\SubCommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Max\koth\KOTH;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class pos2SubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.pos2");
		$this->registerArgument(0, new RawStringArgument("Arena name", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		$koth = KOTH::getInstance();
		$arena = $koth->getArena($args["Arena name"]);
		if (!$arena) {
			$sender->sendMessage("§7[§bKOTH§7] §cThat arena does not exist.");
			return;
		}
		if (is_null($arena->getMin())) {
			$sender->sendMessage("§7[§bKOTH§7] §cSet pos1 before pos2.");
			return;
		}
		if ($sender instanceof Player){
			$pos1 = $arena->getMin();
			$pos2 = $sender->getPosition();
			$arena->setMin([
				"x" => min($pos1["x"], $pos2->getX()),
				"y" => min($pos1["y"], $pos2->getY()),
				"z" => min($pos1["z"], $pos2->getZ()),
			]);
			$arena->setMax([
				"x" => max($pos1["x"], $pos2->getX()),
				"y" => max($pos1["y"], $pos2->getY()),
				"z" => max($pos1["z"], $pos2->getZ()),
			]);
			$arena->setCoords((int)((min($pos1["x"], $pos2->getX()) + max($pos1["x"], $pos2->getX())) / 2) . ", " . (int)((min($pos1["y"], $pos2->getY()) + max($pos1["y"], $pos2->getY())) / 2) . ", " . (int)((min($pos1["z"], $pos2->getZ()) + max($pos1["z"], $pos2->getZ())) / 2));
			$arena->setWorld($sender->getWorld()->getFolderName());
			$sender->sendMessage("§7[§bKOTH§7] §aSet pos2 for arena.");
		}
	}
}