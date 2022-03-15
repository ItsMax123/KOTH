<?php

declare(strict_types=1);

namespace Max\koth;


use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Embed;

use xenialdan\apibossbar\BossBar;
use CortexPE\Commando\PacketHooker;

use Max\koth\Commands\kothCommand;
use Max\koth\Tasks\KothTask;
use Max\koth\Tasks\StartKothTask;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Config;


class KOTH extends PluginBase{
	public static KOTH $instance;
	public Config $data;

	public ?TaskHandler $task;
	public ?Arena $current;
	public BossBar $bar;
	public array $arenas;

	public int $TASK_DELAY;
	public int $CAPTURE_TIME;
	public bool $SEND_TIPS;
	public bool $USE_BOSSBAR;
	public array $REWARD_COMMANDS;
	public string $START_MESSAGE;
	public string $END_MESSAGE;
	public array $START_TIMES;
	public string $WEBHOOK_LINK;
	public array $START_WEBHOOK;
	public array $END_WEBHOOK;

	public function onEnable() : void {
		self::$instance = $this;

		$this->saveResource("config.yml");
		$this->saveResource("data.yml");
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);

		$this->TASK_DELAY = $config->get("update_delay", 2);
		$this->CAPTURE_TIME = $config->get("capture_time", 300);
		$this->USE_BOSSBAR = $config->get("bossbar", True);
		$this->SEND_TIPS = $config->get("hotbar_popups", False);
		$this->REWARD_COMMANDS = $config->get("reward_command", ["give {PLAYER} diamond 64", "give {PLAYER} obsidian 64"]);
		$this->START_MESSAGE = $config->get("start_message", "KOTH {KOTH} has started!");
		$this->END_MESSAGE = $config->get("end_message", "{PLAYER} has won the KOTH event.");
		$this->START_TIMES = $config->get("start_times", []);
		$this->WEBHOOK_LINK = $config->get("webhook_link", "");
		$this->START_WEBHOOK = $config->get("start_webhook", []);
		$this->END_WEBHOOK = $config->get("end_webhook", []);
		$this->current = null;
		$this->task = null;

		foreach ($this->data->getAll() as $arenaName => $arenaData) {
			$this->arenas[$arenaName] = new Arena($arenaName);
		}

		if ($this->USE_BOSSBAR) $this->bar = new BossBar();

		if(!PacketHooker::isRegistered()) {
			PacketHooker::register($this);
		}
		$this->getServer()->getCommandMap()->register("koth", new kothCommand($this, "koth", "KOTH commands prefix"));

		$this->getScheduler()->scheduleRepeatingTask(new StartKothTask($this), 600);
	}

	public static function getInstance() : KOTH {
		return self::$instance;
	}

	public function isRunning() : bool {
		if (isset($this->task)) return True;
		else return False;
	}

	public function getArena(string $arenaName = null) : Arena|bool {
		if (is_null($arenaName)) {
			if (!empty($this->data->getAll())) $arenaName = array_rand($this->data->getAll());
			else return False;
		}
		if (!$this->data->get($arenaName)) {
			return False;
		}
		return $this->arenas[$arenaName];
	}

	public function startKoth(Arena $arena) : string {
		if ($this->isRunning()) return "§7[§bKOTH§7] §cKOTH already running";
		$this->task = $this->getScheduler()->scheduleRepeatingTask(new KothTask($this, $arena), $this->TASK_DELAY);
		$this->current = $arena;
		$arenaName = $arena->getName();
		$this->getServer()->broadcastMessage(str_replace("{ARENA_NAME}", $arenaName, $this->START_MESSAGE));
		if($this->WEBHOOK_LINK) {
			$webHook = new Webhook($this->WEBHOOK_LINK);
			$msg = new Message();
			$embed = new Embed();

			if(isset($this->START_WEBHOOK["username"])) $msg->setUsername($this->START_WEBHOOK["username"]);
			if(isset($this->START_WEBHOOK["avatar-url"])) $msg->setAvatarURL($this->START_WEBHOOK["avatar-url"]);
			if(isset($this->START_WEBHOOK["mention"])) $msg->setContent($this->START_WEBHOOK["mention"]); //<@& Role_ID >
			$embed->setColor(0x00FF00);
			if(isset($this->START_WEBHOOK["thumnail-url"])) $embed->setThumbnail($this->START_WEBHOOK["thumnail-url"]);
			if(isset($this->START_WEBHOOK["image-url"])) $embed->setImage($this->START_WEBHOOK["image-url"]);
			if(isset($this->START_WEBHOOK["footer"])) $embed->setFooter($this->START_WEBHOOK["footer"], $this->START_WEBHOOK["footer-icon-url"]);

			if(isset($this->START_WEBHOOK["title"])) $embed->setTitle(str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $arena->getCoords()), $this->START_WEBHOOK["title"]));
			if(isset($this->START_WEBHOOK["description"])) $embed->setDescription(str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $arena->getCoords()), $this->START_WEBHOOK["description"]));
			if(isset($this->START_WEBHOOK["fields"])) {
				foreach($this->START_WEBHOOK["fields"] as $name => $value) {
					$embed->addField(str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $arena->getCoords()), $name), str_replace(array("{ARENA_NAME}", "{COORDS}"), array($arenaName, $arena->getCoords()), $value));
				}
			}

			$msg->addEmbed($embed);
			$webHook->send($msg);
		}
		return "§7[§bKOTH§7] §aStarted KOTH";
	}

	public function stopKoth(string $winnerName = null) : string {
		if (!$this->isRunning()) return "§7[§bKOTH§7] §cThere is no KOTH events currently running";
		if (isset($winnerName)) {
			$consoleCommandSender = new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage());
			foreach ($this->REWARD_COMMANDS as $command){
				$this->getServer()->dispatchCommand($consoleCommandSender, str_replace("{PLAYER}", $winnerName, $command));
			}
		} else {
			$winnerName = "no one";
		}
		$this->task->cancel();
		$this->task = null;
		$this->current = null;
		$this->bar->removeAllPlayers();
		$this->getServer()->broadcastMessage(str_replace("{PLAYER}", $winnerName, $this->END_MESSAGE));
		if($this->WEBHOOK_LINK) {
			$webHook = new Webhook($this->WEBHOOK_LINK);
			$msg = new Message();
			$embed = new Embed();

			if(isset($this->END_WEBHOOK["username"])) $msg->setUsername($this->END_WEBHOOK["username"]);
			if(isset($this->END_WEBHOOK["avatar-url"])) $msg->setAvatarURL($this->END_WEBHOOK["avatar-url"]);
			if(isset($this->END_WEBHOOK["mention"])) $msg->setContent($this->END_WEBHOOK["mention"]); //<@& Role_ID >
			$embed->setColor(0x00FF00);
			if(isset($this->END_WEBHOOK["thumnail-url"])) $embed->setThumbnail($this->END_WEBHOOK["thumnail-url"]);
			if(isset($this->END_WEBHOOK["image-url"])) $embed->setImage($this->END_WEBHOOK["image-url"]);
			if(isset($this->END_WEBHOOK["footer"])) $embed->setFooter($this->END_WEBHOOK["footer"], $this->END_WEBHOOK["footer-icon-url"]);

			if(isset($this->END_WEBHOOK["title"])) $embed->setTitle(str_replace("{PLAYER}", $winnerName, $this->END_WEBHOOK["title"]));
			if(isset($this->END_WEBHOOK["description"])) $embed->setDescription(str_replace("{PLAYER}", $winnerName, $this->END_WEBHOOK["description"]));
			if(isset($this->END_WEBHOOK["fields"])) {
				foreach($this->END_WEBHOOK["fields"] as $name => $value) {
					$embed->addField(str_replace("{PLAYER}", $winnerName, $name), str_replace("{PLAYER}", $winnerName, $value));
				}
			}

			$msg->addEmbed($embed);
			$webHook->send($msg);
		}
		return "§7[§bKOTH§7] §aStopped KOTH";
	}
}
