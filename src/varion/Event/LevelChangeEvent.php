<?php

namespace varion\Event;

use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\Server;
use varion\AdvancedScoreboard;

class LevelChangeEvent implements Listener{

	/** @var AdvancedScoreboard $plugin */
	private $plugin;

	/**
	* @param AdvancedScoreboard $plugin
	*/
	public function __construct($plugin){
		$this->plugin = $plugin;
	}

    /**
     * @param Player $player
     * @param array $titles
     * @param array $lines
     */
	public function onChange(EntityTeleportEvent $event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $this->plugin->removeScore($player);
        }
     return true;
    }


}