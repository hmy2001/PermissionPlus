<?php

namespace PermissionPlus;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class PermissionPlus extends PluginBase implements Listener, CommandExecutor{
        private $attachments = [];

	public function onEnable(){
		$this->getLogger()->info("PermissionPlus loaded!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
                @mkdir($this->getDataFolder());
                $this->CreateConfig();
                $this->FormatConfig();
                $this->alias = [];
	}

	public function onDisable(){
                //$this->config->save();
	}

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	コマンド
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){

	}

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
       他の処理
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	bool
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function castBool($bool){
                $bool = strtoupper($bool);
                switch($bool){
                case "TRUE":
                case "ON":
                case "1":
                return true;
                break;
                case "FALSE":
                case "OFF":
                case "0":
                return false;
                break;
                default:
                return false;
                }
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	CommandPermission関連
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	Event
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	名前変更
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/














}