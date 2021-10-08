<?php

namespace Max\koth;

use Ifera\ScoreHud\event\TagsResolveEvent;
use pocketmine\event\Listener;

class ScoreHudListener implements Listener
{
	public function onTagResolve(TagsResolveEvent $event){
		$tag = $event->getTag();

		switch($tag->getName()){
			case "koth.king":
				$tag->setValue("");
				break;

			case "koth.time":
				$tag->setValue("");
				break;
		}
	}
}
