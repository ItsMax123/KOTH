<?php

namespace Max\koth;

use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerJoinEvent, PlayerQuitEvent};

class EventListener implements Listener {
    public function __construct($pl) {
        $this->plugin = $pl;
        $pl->getServer()->getPluginManager()->registerEvents($this, $pl);
    }

    public function onJoin(PlayerJoinEvent $event) {
		if ($this->plugin->config->get("bossbar")) $this->plugin->bar->removePlayer($event->getPlayer());
    }
}