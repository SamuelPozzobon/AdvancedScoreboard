<?php

namespace varion;

use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use varion\Event\LevelChangeEvent;
use pocketmine\command\{Command,CommandSender};
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\NetworkSessionManager;
use jojoe77777\FormAPI;
use jojoe77777\FormAPI\SimpleForm;


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
		$this->getLogger()->info(TF::DARK_PURPLE."AdvancedScoreboard enabled");
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
  $player->getNetworkSession()->sendDataPacket($packet);
		self::$scoreboard[$player->getName()] = $player->getName();
	}

	/**
	* @param Player $player
	* @return void
	*/
	public function removeScore(Player $player) : void{
		$packet = new RemoveObjectivePacket();
		$packet->objectiveName = "objective";
		$player->getNetworkSession()->sendDataPacket($packet);
		unset(self::$scoreboard[$player->getName()]);
	}

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool
    {
        switch ($cmd->getName()) {
            case "as":
                if ($sender instanceof Player) {
                 if($sender->hasPermission("advancedscoreboard.use")){
                    $form = new SimpleForm(function (Player $sender, ?int $data){
                        if ($data === null){
                            return true;
                        }

                        switch($data){
                            case 0:
                                $this->createScore[$sender->getName()] = $sender->getName();
                                return true;
                                break;

                            case 1:
                                $this->removeScore($sender);

                                return true;

                        }
                        return false;
                    });
                    $form->setTitle(TF::GOLD . "Scoreboard");
                    $form->setContent("Select an option");
                    $form->addButton(TF::BOLD . "§l§aShow Scoreboard");
                    $form->addButton(TF::BOLD . "§l§cHide Scoreboard");
                    $form->sendToPlayer($sender);
               }
              }
                else{
                    $sender->sendMessage(TF ::RED . "Use this Command in-game.");
                    return true;
                }
                    }
                    return true;
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
		$player->getNetworkSession()->sendDataPacket($packet); 
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
        $message = str_replace('{PING}', $player->getNetworkSession()->getPing(), $message);
        $message = str_replace('{NAME}', $player->getName(), $message);
        $message = str_replace('{X}', $player->getPosition()->getX(), $message);
        $message = str_replace('{Y}', $player->getPosition()->getY(), $message);
        $message = str_replace('{Z}', $player->getPosition()->getZ(), $message);

        $message = str_replace('{IP}', $player->getNetworkSession()->getIP(), $message);
        // $message = str_replace('{ITEM_ID}', $player->getInventory()->getItemInHand(), $message);
        $level = $player->getWorld();
        $message = str_replace('{WORLDNAME}', $level->getFolderName(), $message);
        $message = str_replace('{WORLDPLAYERS}', count($level->getPlayers()), $message);
        $message = str_replace('{TICKS}', $this->getServer()->getTickUsage(), $message);
        $message = str_replace('{TPS}', $this->getServer()->getTicksPerSecond(), $message);
        $message = str_replace('{ONLINE}', count($this->getServer()->getOnlinePlayers()), $message);
        $message = str_replace('{MAX_ONLINE}', $player->getServer()->getMaxPlayers(), $message);
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
		$FactionsPro = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
		if(!is_null($FactionsPro)){
			$message = str_replace('{FACTION}', $FactionsPro->getPlayerFaction($player->getName()), $message);
            $message = str_replace('{FPOWER}', $FactionsPro->getFactionPower($factionName), $message);
		}else{
			$message = str_replace('{FACTION}', "PLUGIN NOT INSTALLED", $message);
			$message = str_replace('{FPOWER}', "PLUGIN NOT INSTALLED", $message);
		}

        $Logger = $this->getServer()->getPluginManager()->getPlugin("CombatLogger");
        if (!is_null($Logger)) {
            $message = str_replace('{COMBATLOGGER}', $Logger->getTagDuration($player), $message);
        }else{
			$message = str_replace('{COMBATLOGGER}', "PLUGIN NOT INSTALLED", $message);
	}

        $kdr = $this->getServer()->getPluginManager()->getPlugin("KDR");
        if (!is_null($kdr)) {
            $message = str_replace('{KDR}', $kdr->getProvider()->getKillToDeathRatio($player), $message);
            $message = str_replace('{DEATHS}', $kdr->getProvider()->getPlayerDeathPoints($player), $message);
            $message = str_replace('{KILLS}', $kdr->getProvider()->getPlayerKillPoints($player), $message);
        }else{
			$message = str_replace('{KDR}', "PLUGIN NOT INSTALLED", $message);
			$message = str_replace('{DEATHS}', "PLUGIN NOT INSTALLED", $message);
		        $message = str_replace('{KILLS}', "PLUGIN NOT INSTALLED", $message);
	}

		$CPS = $this->getServer()->getPluginManager()->getPlugin("PreciseCpsCounter");
		if (!is_null($CPS)) {
			$message = str_replace('{CPS}', $CPS->getCps($player), $message);
		}else{
			$message = str_replace('{CPS}', "PLUGIN NOT INSTALLED", $message);
		}

        $RedSkyBlock = $this->getServer()->getPluginManager()->getPlugin("RedSkyBlock");
        if (!is_null($RedSkyBlock)) {
            $message = str_replace('{ISLAND_NAME}', $RedSkyBlock->getIslandName($player), $message);
            $message = str_replace('{ISLAND_MEMBERS}', $RedSkyBlock->getMembers($player), $message);
            $message = str_replace('{ISLAND_BANNED}', $RedSkyBlock->getBanned($player), $message);
            $message = str_replace('{ISLAND_LOCKED_STATUS}', $RedSkyBlock->getLockedStatus($player), $message);
            $message = str_replace('{ISLAND_SIZE}', $RedSkyBlock->getSize($player), $message);
            $message = str_replace('{ISLAND_RANK}', $RedSkyBlock->calcRank(strtolower($player->getName())), $message);
            $message = str_replace('{ISLAND_VALUE}', $RedSkyBlock->getValue($player), $message);
        }
		return $message;
	}
}
