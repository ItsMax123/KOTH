<?php

declare(strict_types=1);

namespace Max\koth;

use pocketmine\math\Vector3;
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

class Main extends PluginBase{
    public $taskid, $bar;

    public function onEnable() {

        $this->saveResource("config.yml");
		$this->saveResource("data.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
        $this->configAll = $this->config->getAll();
		if ($this->config->get("bossbar")) $this->bar = new BossBar();

		new EventListener($this);

		if (class_exists(Webhook::class)) $this->webhook = True;
		if ($this->getServer()->getPluginManager()->getPlugin("ScoreHud") instanceof Plugin) {
			$this->getServer()->getPluginManager()->registerEvents(new ScoreHudListener(), $this);
			$this->scorehud = True;
		}

		$this->getScheduler()->scheduleDelayedRepeatingTask(new StartKothTask($this), ($this->config->get("delay_between_koths")*72000), ($this->config->get("delay_between_koths")*72000));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() == "koth") {
            if (!isset($args[0])) {
                $sender->sendMessage("§7[§bKOTH§7] §aAvailable commands:§r\n - /koth create [arena name]\n - /koth delete [arena name]\n - /koth setpos1 [arena name]\n - /koth setpos2 [arena name]\n - /koth setspawn [arena name]\n - /koth list\n - /koth start [Optional: arena name]\n - /koth join\n - /koth stop");
                return true;
            }
            switch($args[0]){
                case "start":
					if (isset($this->taskid)) {
						$sender->sendMessage("§7[§bKOTH§7] §cCould not start KOTH event because another one is already running.");
						return true;
					}
					if (empty($this->data->getAll())) {
						$sender->sendMessage("§7[§bKOTH§7] §cCould not start KOTH event because there are no arenas setup. Create an arena with '/koth create'");
						return true;
					}

					if (isset($args[1])) {
						if (!$this->data->get($args[1])) {
							$sender->sendMessage("§7[§bKOTH§7] §cThe KOTH you are trying to start does not exist.");
							return true;
						}
						$this->StartKoth($args[1]);
					} else {
						$this->StartKoth();
					}
                    return true;
                case "end":
                case "stop":
					if (isset($this->taskid)) $this->stopkoth();
                    return true;
				case "create":
				case "new":
					if (isset($args[1])) {
						$this->newArena($args[1]);
						$sender->sendMessage("§7[§bKOTH§7] §aSuccessfully created ".$args[1]." arena. Now you need to set where the arena should be with '/koth setpos1' and '/koth setpos2'");
					} else {
						$sender->sendMessage("§7[§bKOTH§7] §cYou need to specify the name of the koth arena you want to create.");
					}
					return true;
				case "delete":
				case "remove":
					if (isset($args[1])) {
						if (!$this->data->get($args[1])) {
							$sender->sendMessage("§7[§bKOTH§7] §cThe KOTH you are trying to delete does not exist.");
							return true;
						}
						$this->deleteArena($args[1]);
						$sender->sendMessage("§7[§bKOTH§7] §aSuccessfully deleted ".$args[1]." arena.");
					} else {
						$sender->sendMessage("§7[§bKOTH§7] §cYou need to specify the name of the koth arena you want to delete.");
					}
					return true;
                case "setpos1":
                case "pos1":
                    if (!$sender instanceof Player) {
                        $this->getLogger()->info("§cYou can only use this command in-game.");
                        return true;
                    }
					if (isset($args[1])) {
						if (!$this->data->get($args[1])) {
							$sender->sendMessage("§7[§bKOTH§7] §cThe KOTH you are trying to setpos1 for does not exist.");
							return true;
						}
						$position = $sender->getPosition();
						$arenaData = $this->data->get($args[1]);
						$arenaData["position1"] = $position->x.":".$position->y.":".$position->z;
						$this->data->set($args[1], $arenaData);
						$this->data->save();
						$this->data->reload();
						$sender->sendMessage("§7[§bKOTH§7] §aPosition 1 set. Do '/koth setpos2' to set the 2nd position (In the opposite corner of the KOTH area).");
					} else {
						$sender->sendMessage("§7[§bKOTH§7] §cYou need to specify the name of the koth arena you want to setpos1 for.");
					}
                    return true;
                case "setpos2":
                case "pos2":
                    if (!$sender instanceof Player) {
                        $this->getLogger()->info("§cYou can only use this command in-game.");
                        return true;
                    }
					if (isset($args[1])) {
						if (!$this->data->get($args[1])) {
							$sender->sendMessage("§7[§bKOTH§7] §cThe KOTH you are trying to setpos2 for does not exist.");
							return true;
						}
						$position = $sender->getPosition();
						$arenaData = $this->data->get($args[1]);
						$arenaData["position2"] = $position->x.":".$position->y.":".$position->z;
						$this->data->set($args[1], $arenaData);
						$this->data->save();
						$this->data->reload();
						$sender->sendMessage("§7[§bKOTH§7] §aPosition 2 set.");
					} else {
						$sender->sendMessage("§7[§bKOTH§7] §cYou need to specify the name of the koth arena you want to setpos2 for.");
					}
					$arenaData = $this->data->get($args[1]);
					$pos1 = explode(":", $arenaData["position1"]);
					$pos2 = explode(":", $arenaData["position2"]);
					$X = round((min($pos1[0], $pos2[0]) + max($pos1[0], $pos2[0]))/2);
					$Y = round((min($pos1[1], $pos2[1]) + max($pos1[1], $pos2[1]))/2);
					$Z = round((min($pos1[2], $pos2[2]) + max($pos1[2], $pos2[2]))/2);
					$arenaData["coords"] = $X.", ".$Y.", ".$Z;
					$this->data->set($args[1], $arenaData);
					$this->data->save();
					$this->data->reload();
                    return true;
				case "setteleport":
				case "settp":
				case "setspawn":
				case "setjoin":
					if (!$sender instanceof Player) {
						$this->getLogger()->info("§cYou can only use this command in-game.");
						return true;
					}
					if (isset($args[1])) {
						if (!$this->data->get($args[1])) {
							$sender->sendMessage("§7[§bKOTH§7] §cThe KOTH you are trying to set the join position for does not exist.");
							return true;
						}
						$position = $sender->getPosition();
						$arenaData = $this->data->get($args[1]);
						$arenaData["spawn"] = $position->x.":".$position->y.":".$position->z;
						$this->data->set($args[1], $arenaData);
						$this->data->save();
						$this->data->reload();
						$sender->sendMessage("§7[§bKOTH§7] §aJoin position set.");
					} else {
						$sender->sendMessage("§7[§bKOTH§7] §cYou need to specify the name of the koth arena you want to set the join position for.");
					}
					return true;
				case "join":
				case "spawn":
				case "tp":
					if (!$sender instanceof Player) {
						$this->getLogger()->info("§cYou can only use this command in-game.");
						return true;
					}
					if (isset($this->currentKOTH)) {
						$arenaData = $this->data->get($this->currentKOTH);
						if (!is_null($arenaData["spawn"])) {
							$spawn = explode(":", $arenaData["spawn"]);
							$sender->teleport(new Vector3((float)($spawn[0]), (float)($spawn[1]), (float)($spawn[2])));
							$sender->sendMessage("§7[§bKOTH§7] §aTeleported to the KOTH arena.");
						} else {
							$sender->sendMessage("§7[§bKOTH§7] §cCannot teleport to the arena.");
						}
					}
					return true;
				case "list":
					if (empty($this->data->getAll())) {
						$sender->sendMessage("§7[§bKOTH§7] §cThere are currently no KOTH arenas.");
						return true;
					}
					$sender->sendMessage("§7[§bKOTH§7] §aList of all KOTH arenas:");
					foreach ($this->data->getAll() as $arenaName => $arenaData) {
						$sender->sendMessage("§b".$arenaName."§r: ".$arenaData["coords"]);
					}
					return true;
				default:
					$sender->sendMessage("§7[§bKOTH§7] §aAvailable commands:§r\n - /koth create [arena name]\n - /koth delete [arena name]\n - /koth setpos1 [arena name]\n - /koth setpos2 [arena name]\n - /koth list\n - /koth start [Optional: arena name]\n - /koth stop");
					return true;
            }
        } else {
            return true;
        }
    }

    public function newArena(string $arenaName) {
		$safeSpawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
    	$this->data->set(
    		$arenaName,
			array (
				'position1' => '0:256:0',
				'position2' => '0:256:0',
				'spawn' => null,
				'coords' => 'N/A'
			)
		);
    	$this->data->save();
    	$this->data->reload();
	}

	public function deleteArena(string $arenaName) {
		if (isset($this->taskid)) $this->StopKoth();
		$this->data->remove($arenaName);
		$this->data->save();
		$this->data->reload();
	}

    public function StartKoth(string $arenaName = null) {
    	if (empty($this->data->getAll())) {
    		$this->getLogger()->info("§cCould not start KOTH event because there are no arenas setup.");
    		return;
    	}
        if (isset($this->taskid)) {
            $this->getLogger()->info("§cCould not start KOTH event because another one is already running.");
            return;
        }
		if (!isset($arenaName)) $arenaName = array_rand($this->data->getAll());
		$this->currentKOTH = $arenaName;
        $this->taskid = $this->getScheduler()->scheduleRepeatingTask(new KothTask($this, $arenaName), 20)->getTaskId();
        $this->getServer()->broadcastMessage(str_replace("{ARENA_NAME}", $arenaName, $this->config->get("koth_start_message")));
        if(isset($this->configAll["start-webhook-url"]) AND isset($this->webhook)) {
        	$webHook = new Webhook($this->config->get("start-webhook-url"));
			$msg = new Message();
			$embed = new Embed();

			if(isset($this->configAll["start-webhook-username"])) $msg->setUsername($this->config->get("start-webhook-username"));
			if(isset($this->configAll["start-webhook-avatar-url"])) $msg->setAvatarURL($this->config->get("start-webhook-avatar-url"));
			if(isset($this->configAll["start-webhook-mention"])) $msg->setContent($this->config->get("start-webhook-mention")); //<@& Role_ID >
			$embed->setColor(0x00FF00);
			if(isset($this->configAll["start-webhook-thumnail-url"])) $embed->setThumbnail($this->config->get("start-webhook-thumnail-url"));
			if(isset($this->configAll["start-webhook-image-url"])) $embed->setImage($this->config->get("start-webhook-image-url"));
			if(isset($this->configAll["start-webhook-footer"])) $embed->setFooter($this->config->get("start-webhook-footer"), $this->config->get("start-webhook-footer-icon-url"));

			if(isset($this->configAll["start-webhook-title"])) $embed->setTitle(str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $this->data->get($arenaName)["coords"]), $this->config->get("start-webhook-title")));
			if(isset($this->configAll["start-webhook-description"])) $embed->setDescription(str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $this->data->get($arenaName)["coords"]), $this->config->get("start-webhook-description")));
			if(isset($this->configAll["start-webhook-fields"])) {
				foreach($this->config->get("start-webhook-fields") as $name => $value) {
					$embed->addField(str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $this->data->get($arenaName)["coords"]), $name), str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $this->data->get($arenaName)["coords"]), $value));
				}
			}

			$msg->addEmbed($embed);
			$webHook->send($msg);
		}
    }

    public function StopKoth(string $winner = null) {
    	unset($this->currentKOTH);
        if ($winner == null) {
            $winner = "no one";
        } else {
            foreach ($this->config->get("reward_commands") as $command) {
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{PLAYER}", $winner, $command));
            }
        }
        $this->getScheduler()->cancelTask($this->taskid);
        unset($this->taskid);
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
			if(isset($this->configAll["end-webhook-thumnail-url"])) $embed->setThumbnail($this->config->get("end-webhook-thumnail-url"));
			if(isset($this->configAll["end-webhook-image-url"])) $embed->setImage($this->config->get("end-webhook-image-url"));
			if(isset($this->configAll["end-webhook-footer"])) $embed->setFooter($this->config->get("end-webhook-footer"), $this->config->get("end-webhook-footer-icon-url"));

			if(isset($this->configAll["end-webhook-title"])) $embed->setTitle(str_replace("{PLAYER}", $winner, $this->config->get("end-webhook-title")));
			if(isset($this->configAll["end-webhook-description"])) $embed->setDescription(str_replace("{PLAYER}", $winner, $this->config->get("end-webhook-description")));
			if(isset($this->configAll["end-webhook-fields"])) {
				foreach($this->config->get("end-webhook-fields") as $name => $value) {
					$embed->addField(str_replace("{PLAYER}", $winner, $name), str_replace("{PLAYER}", $winner, $value));
				}
			}

			$msg->addEmbed($embed);
			$webHook->send($msg);
		}
    }
}
