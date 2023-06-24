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
	public function onChange(EntityTeleportEvent $event) {
		$player = $event->getEntity();
        if ($player instanceof Player) {
			$this->plugin->removeScore($player);
            if (empty($worlds)) {
                $this->Score(Server::getInstance()->getOnlinePlayers(), $this->getConfig()->get('default', []));
            }else{
                foreach ($worlds as $world => $title) {
                    $level_world = Server::getInstance()->getWorldManager()->getWorldByName($world);
                    if ($level_world instanceof Level) {
                        $this->Score($level_world->getPlayers(), $title);
                    }
                }
            }
            return true;
		}
	}
    /**
     * @param Player $player
     * @param array $titles
     * @param array $lines
     */
    public function sendScore(Player $player, array $titles, array $lines) : void{
        $title = $this->getTitle($titles);
        $this->plugin->createScore($player, $this->plugin->translate($player, $title));
        $this->plugin->setScoreLines($player, $lines, true);
    }

}