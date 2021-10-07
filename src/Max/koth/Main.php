<?php

declare(strict_types=1);

namespace Max\koth;

use pocketmine\Player;
use pocketmine\utils\{Config};
use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender, ConsoleCommandSender};

use Max\koth\Tasks\{StartKothTask, KothTask};
use Max\koth\libs\BossBar;


class Main extends PluginBase {
    public $taskid, $bar;

    public function onEnable() {

        new EventListener($this);
        $this->bar = new BossBar();

        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

        $this->getScheduler()->scheduleDelayedRepeatingTask(new StartKothTask($this), ($this->config->get("delay_between_koths")*72000), ($this->config->get("delay_between_koths")*72000));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() == "koth") {
            if (!isset($args[0])) {
                $sender->sendMessage("§7[§bKOTH§7] Available commands:\n/koth start\n/koth end\n");
                return true;
            }
            switch($args[0]){
                case "start":
                    if (isset($this->taskid)) {
                        $sender->sendMessage("§7[§bKOTH§7] §cCould not start KOTH event because another one is already running.");
                    } else {
                        $this->StartKoth();
                    }
                    return true;
                case "end":
                case "stop":
                    $this->stopkoth();
                    return true;
                case "setpos1":
                case "set pos 1":
                case "set position1":
                case "set position 1":
                case "pos1":
                    if (!$sender instanceof Player) {
                        $this->getLogger()->info("§cYou can only use this command in-game.");
                        return true;
                    }
                    $position = $sender->getPosition();
                    $this->config->set("position1", $position->x.":".$position->y.":".$position->z);
                    $this->config->save();
                    $sender->sendMessage("§7[§bKOTH§7] §aPosition 1 set. Do '/koth setpos2' to set the 2nd position (In the opposite corner of the KOTH area).");
                    return true;
                case "setpos2":
                case "set pos 2":
                case "set position2":
                case "set position 2":
                case "pos2":
                    if (!$sender instanceof Player) {
                        $this->getLogger()->info("§cYou can only use this command in-game.");
                        return true;
                    }
                    $position = $sender->getPosition();
                    $this->config->set("position2", $position->x.":".$position->y.":".$position->z);
                    $this->config->save();
                    $sender->sendMessage("§7[§bKOTH§7] §aPosition 2 set. ");
                    return true;
                default:
                    $sender->sendMessage("§7[§bKOTH§7] Available commands:\n/koth start\n/koth end\n");
                    return true;
            }
        } else {
            return true;
        }
    }

    //API SECTION

    public function StartKoth() {
        if (isset($this->taskid)) {
            $this->getLogger()->info("§cCould not start KOTH event because another one is already running.");
        } else {
            $this->taskid = $this->getScheduler()->scheduleRepeatingTask(new KothTask($this), 10)->getTaskId();
            $this->getServer()->broadcastMessage($this->config->get("koth_start_message"));
        }
    }

    public function StopKoth(string $winner = null) {
        if ($winner == null) {
            $winner = "no one";
        } else {
            foreach ($this->config->get("reward_commands") as $command) {
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{PLAYER}", $winner, $command));
            }
        }
        $this->getScheduler()->cancelTask($this->taskid);
        unset($this->taskid);
        $this->getScheduler()->cancelAllTasks();
        $this->getServer()->broadcastMessage(str_replace("{PLAYER}", $winner, $this->config->get("koth_end_message")));
        $this->bar->removeAllPlayers();
    }
}