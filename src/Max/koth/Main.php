<?php

declare(strict_types=1);

namespace Max\koth;

use pocketmine\Player;
use pocketmine\utils\{Config};
use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender, ConsoleCommandSender};
use pocketmine\plugin\Plugin;

use Max\koth\Tasks\{StartKothTask, KothTask};
use Max\koth\libs\BossBar;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Embed;
use Ifera\ScoreHud\event\TagsResolveEvent;

class Main extends PluginBase{
    public $taskid, $bar;

    public function onEnable() {

        $this->saveResource("config.yml");
		$this->saveResource("data.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
		$this->dataAll = $this->config->getAll();
        $this->configAll = $this->config->getAll();
		if ($this->config->get("bossbar")) $this->bar = new BossBar();
	    
		new EventListener($this);

		if (class_exists(Webhook::class)) $this->webhook = True;
		if ($this->getServer()->getPluginManager()->getPlugin("ScoreHud") instanceof Plugin) {
			$this->getServer()->getPluginManager()->registerEvents(new ScoreHudListener($this), $this);
			$this->scorehud = True;
		}

		$this->getScheduler()->scheduleDelayedRepeatingTask(new StartKothTask($this), ($this->config->get("delay_between_koths")*72000), ($this->config->get("delay_between_koths")*72000));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() == "koth") {
            if (!isset($args[0])) {
                $sender->sendMessage("§7[§bKOTH§7] Available commands:\n - /koth setpos1\n - /koth setpos2\n - /koth start\n - /koth end");
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
					if (isset($this->taskid)) $this->stopkoth();
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
                    $this->data->set("position1", $position->x.":".$position->y.":".$position->z);
                    $this->data->save();
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
                    $this->data->set("position2", $position->x.":".$position->y.":".$position->z);
                    $this->data->save();
                    $sender->sendMessage("§7[§bKOTH§7] §aPosition 2 set. ");
                    return true;
                default:
                    $sender->sendMessage("§7[§bKOTH§7] Available commands:\n - /koth setpos1\n - /koth setpos2\n - /koth start\n - /koth end");
                    return true;
            }
        } else {
            return true;
        }
    }

    public function StartKoth() {
        if (isset($this->taskid)) {
            $this->getLogger()->info("§cCould not start KOTH event because another one is already running.");
        } else {
            $this->taskid = $this->getScheduler()->scheduleRepeatingTask(new KothTask($this), 20)->getTaskId();
            $this->getServer()->broadcastMessage($this->config->get("koth_start_message"));
        }
        if(isset($this->configAll["start-webhook-url"]) AND isset($this->webhook)) {
        	$webHook = new Webhook($this->config->get("start-webhook-url"));
			$msg = new Message();
			$embed = new Embed();

			if(isset($this->configAll["start-webhook-username"])) $msg->setUsername($this->config->get("start-webhook-username"));
			if(isset($this->configAll["start-webhook-avatar-url"])) $msg->setAvatarURL($this->config->get("start-webhook-avatar-url"));
			if(isset($this->configAll["start-webhook-mention"])) $msg->setContent($this->config->get("start-webhook-mention")); //<@& Role_ID >

			$embed->setColor(0x00FF00);
			if(isset($this->configAll["start-webhook-title"])) $embed->setTitle($this->config->get("start-webhook-title"));
			if(isset($this->configAll["start-webhook-description"])) $embed->setDescription($this->config->get("start-webhook-description"));

			if(isset($this->configAll["start-webhook-fields"])) {
				foreach($this->config->get("start-webhook-fields") as $name => $value) {
					$embed->addField($name, $value);
				}
			}
			if(isset($this->configAll["start-webhook-thumnail-url"])) $embed->setThumbnail($this->config->get("start-webhook-thumnail-url"));
			if(isset($this->configAll["start-webhook-image-url"])) $embed->setImage($this->config->get("start-webhook-image-url"));
			if(isset($this->configAll["start-webhook-footer-icon-url"])) $embed->setFooter($this->config->get("start-webhook-footer"), $this->config->get("start-webhook-footer-icon-url"));

			$msg->addEmbed($embed);
			$webHook->send($msg);
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
		if ($this->config->get("bossbar")) $this->bar->removeAllPlayers();
		if(isset($this->configAll["end-webhook-url"]) AND isset($this->webhook)) {
			$webHook = new Webhook($this->config->get("end-webhook-url"));
			$msg = new Message();
			$embed = new Embed();

			if(isset($this->configAll["end-webhook-username"])) $msg->setUsername($this->config->get("end-webhook-username"));
			if(isset($this->configAll["end-webhook-avatar-url"])) $msg->setAvatarURL($this->config->get("end-webhook-avatar-url"));
			if(isset($this->configAll["end-webhook-mention"])) $msg->setContent($this->config->get("end-webhook-mention")); //<@& Role_ID >

			$embed->setColor(0xFF0000);
			if(isset($this->configAll["end-webhook-title"])) $embed->setTitle($this->config->get("end-webhook-title"));
			if(isset($this->configAll["end-webhook-description"])) $embed->setDescription($this->config->get("end-webhook-description"));

			if(isset($this->configAll["end-webhook-fields"])) {
				foreach($this->config->get("end-webhook-fields") as $name => $value) {
					$embed->addField($name, str_replace("{PLAYER}", $winner, $value));
				}
			}
			if(isset($this->configAll["end-webhook-footer-icon-url"])) $embed->setFooter($this->config->get("end-webhook-footer"), $this->config->get("end-webhook-footer-icon-url"));
			if(isset($this->configAll["end-webhook-thumnail-url"])) $embed->setThumbnail($this->config->get("end-webhook-thumnail-url"));
			if(isset($this->configAll["end-webhook-image-url"])) $embed->setImage($this->config->get("end-webhook-image-url"));

			$msg->addEmbed($embed);
			$webHook->send($msg);
		}
    }
}
