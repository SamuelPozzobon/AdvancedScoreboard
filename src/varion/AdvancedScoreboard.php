<?php

namespace varion;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use varion\Event\LevelChangeEvent;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;


class AdvancedScoreboard extends PluginBase{
	
	/** @var string */
	public const LIST = "list";
	public const SIDEBAR = "sidebar";
	public const BELOW_NAME = "belowname";

	/** @var string[] */
	private static $scoreboard = [];

	/** @var AdvancedScoreboard */
	private static $plugin;

	public function onEnable() : void{
		static::$plugin = $this;
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents(new LevelChangeEvent($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new AdvancedTask($this), $this->getConfig()->get("interval-time", 20));
		$this->getLogger()->info(TF::DARK_PURPLE."ADVANCEDSCOREBOARD ABILITATO");
	}

	/**
	* @return AdvancedScoreboard
	*/
	public static function getInstance() : AdvancedScoreboard{
		return static::$plugin;
	}

	/**
	* @param Player $player
	* @param string $displayName
	* @param int    $sortOrder
	* @param string $displaySlot
	* @return void
	*/
	public function createScore(Player $player, string $displayName, int $sortOrder = 0, string $displaySlot = "sidebar") : void{
		if(isset(self::$scoreboard[$player->getName()])){
			$this->removeScore($player);
		}
		$packet = new SetDisplayObjectivePacket();
		$packet->displaySlot = $displaySlot;
		$packet->objectiveName = "objective";
		$packet->displayName = $displayName;
		$packet->criteriaName = "dummy";
		$packet->sortOrder = $sortOrder;
		$player->sendDataPacket($packet);
		self::$scoreboard[$player->getName()] = $player->getName();
	}

	/**
	* @param Player $player
	* @return void
	*/
	public function removeScore(Player $player) : void{
		$packet = new RemoveObjectivePacket();
		$packet->objectiveName = "objective";
		$player->sendDataPacket($packet);
		unset(self::$scoreboard[$player->getName()]);
	}

    /**
     * @param Player $player
     * @param array  $messages
     * @return void
     */
    public function setScoreLines(Player $player, array $messages, bool $translate = false) : void{
        $line = 1;
        foreach ($messages as $message) {
            $this->setScoreLine($player, $line, $message, $translate);
            $line++;
        }
    }

	/**
	* @param Player $player
	* @param int    $line
	* @param string $customName
	* @return bool
	*/
	public function setScoreLine(Player $player, int $line, string $message, bool $translate = false) : void{
		if(!isset(self::$scoreboard[$player->getName()])) {
			return;
		}

		if($line <= 0 or $line > 15) {
			return;
		}

		if ($translate) {
			$message = $this->translate($player, $message);
		}

		$pkline = new ScorePacketEntry();
		$pkline->objectiveName = "objective";
		$pkline->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
		$pkline->customName = $message;
		$pkline->score = $line;
		$pkline->scoreboardId = $line;
		
		$packet = new SetScorePacket();
		$packet->type = SetScorePacket::TYPE_CHANGE;
		$packet->entries[] = $pkline;
		$player->sendDataPacket($packet);
	}

	/**
	* @return string
	*/
	public function getColor() : string{
		$colors = [TF::DARK_BLUE, TF::DARK_GREEN, TF::DARK_AQUA, TF::DARK_RED, TF::DARK_PURPLE, TF::GOLD, TF::GRAY, TF::DARK_GRAY, TF::BLUE, TF::GREEN, TF::AQUA, TF::RED, TF::LIGHT_PURPLE, TF::YELLOW, TF::WHITE];
		return $colors[rand(0,14)];
	}

    /**
     * @param Player $player
     * @param string $message
     * @return string
     */
    public function translate(Player $player, string $message) : string{
        $message = str_replace('{PING}', $player->getPing(), $message);
        $message = str_replace('{NAME}', $player->getName(), $message);
        $message = str_replace('{X}', $player->getFloorX(), $message);
        $message = str_replace('{Y}', $player->getFloorY(), $message);
        $message = str_replace('{Z}', $player->getFloorZ(), $message);
        $level = $player->getLevel();
        $message = str_replace('{WORLDNAME}', $level->getFolderName(), $message);
        $message = str_replace('{WORLDPLAYERS}', count($level->getPlayers()), $message);
        $message = str_replace('{TICKS}', $this->getServer()->getTickUsage(), $message);
        $message = str_replace('{TPS}', $this->getServer()->getTicksPerSecond(), $message);
        $message = str_replace('{ONLINE}', count($this->getServer()->getOnlinePlayers()), $message);
        $message = str_replace("{DATE}", date("H:i a"), $message);
        $message = str_replace("{RANDOMCOLOR}", $this->getColor(), $message);
        $message = $this->reviewAllPlugins($player, $message);
        return TF::colorize((string) $message);
    }

	/**
	* @param Player $player
	* @param string $message
	* @return string
	*/
	private function reviewAllPlugins(Player $player, string $message) : string{
		$PurePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		if (!is_null($PurePerms)) {
			$message = str_replace('{RANK}', $PurePerms->getUserDataMgr()->getGroup($player)->getName(), $message);
			$message = str_replace('{PREFIX}', $PurePerms->getUserDataMgr()->getNode($player, "prefix"), $message);
			$message = str_replace('{SUFFIX}', $PurePerms->getUserDataMgr()->getNode($player, "suffix"), $message);
		}

		$EconomyAPI = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		if (!is_null($EconomyAPI)) {
			$message = str_replace('{MONEY}', $EconomyAPI->myMoney($player), $message);
		}
        $Jobs = $this->getServer()->getPluginManager()->getPlugin("EconomyJob");
        if (!is_null($EconomyJob)) {
            $message = str_replace('{JOBS}', $Jobs->get($player), $message);
        }
		$FactionsPro = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
		if(!is_null($FactionsPro)){
			$message = str_replace('{FACTION}', $FactionsPro->getPlayerFaction($player->getName()), $message);
		}

		$CPS = $this->getServer()->getPluginManager()->getPlugin("PreciseCpsCounter");
		if (!is_null($CPS)) {
			$message = str_replace('{CPS}', $CPS->getCps($player), $message);
		}
		return $message;
	}
}
