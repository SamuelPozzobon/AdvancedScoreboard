<?php

namespace varion;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\World;
use pocketmine\scheduler\Task;

class AdvancedTask extends Task {

    /** @var AdvancedScoreboard */
    private $plugin;

    /**
     * @param AdvancedScoreboard $plugin
     */
    public function __construct(AdvancedScoreboard $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @return Config
     */
    public function getConfig() : Config{
        return new Config($this->plugin->getDataFolder() . 'config.yml');
    }

    /**
     * @param int $tick
     */
    public function onRun() : void{
        $worlds = $this->getConfig()->get("worlds", []);

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
    }

    /**
     * @param array $titles
     * @return string
     */
    public function getTitle(array $titles) : ?string{
        shuffle($titles);
        shuffle($titles);
        return array_shift($titles);
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

    /**
     * @param array $players
     * @param array $config_title
     */
    public function Score(array $players, array $config_title) : void{
        foreach ($players as $player) {
            $this->sendScore($player, $config_title['title'], $config_title['lines']);
        }
    }

}