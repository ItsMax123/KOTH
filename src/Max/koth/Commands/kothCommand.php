<?php

declare(strict_types=1);

namespace Max\koth\Commands;

use CortexPE\Commando\BaseCommand;
use Max\koth\Commands\SubCommands\createSubCommand;
use Max\koth\Commands\SubCommands\deleteSubCommand;
use Max\koth\Commands\SubCommands\joinSubCommand;
use Max\koth\Commands\SubCommands\listSubCommand;
use Max\koth\Commands\SubCommands\pos1SubCommand;
use Max\koth\Commands\SubCommands\pos2SubCommand;
use Max\koth\Commands\SubCommands\removeSpawnSubCommand;
use Max\koth\Commands\SubCommands\setSpawnSubCommand;
use Max\koth\Commands\SubCommands\startSubCommand;
use Max\koth\Commands\SubCommands\stopSubCommand;
use pocketmine\command\CommandSender;

class kothCommand extends BaseCommand {

	protected function prepare(): void {
		$this->setPermission("maxkoth.command.koth.use");
		$this->registerSubCommand(new startSubCommand("start", "Start a KOTH", ["s"]));
		$this->registerSubCommand(new stopSubCommand("stop", "Stop the currently running KOTH", ["e"]));
		$this->registerSubCommand(new pos1SubCommand("pos1", "Set's the first corner of the KOTH arena.", ["1"]));
		$this->registerSubCommand(new pos2SubCommand("pos2", "Set's the second corner of the KOTH arena.", ["2"]));
		$this->registerSubCommand(new setSpawnSubCommand("setspawn", "Set's the spawn point for the KOTH arena.", ["createspawn"]));
		$this->registerSubCommand(new removeSpawnSubCommand("removespawn", "Remove's the spawn point for the KOTH arena.", ["delspawn"]));
		$this->registerSubCommand(new joinSubCommand("join", "Join the KOTH event.", ["spawn"]));
		$this->registerSubCommand(new listSubCommand("list", "Get a list of KOTH arenas."));
		$this->registerSubCommand(new createSubCommand("create", "Create a KOTH arena.", ["new"]));
		$this->registerSubCommand(new deleteSubCommand("delete", "Delete a KOTH arena.", ["remove"]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
		$this->sendUsage();
	}
}