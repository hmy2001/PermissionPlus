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

class Main extends PluginBase implements Listener, CommandExecutor{
        const VERSION = $this->getDescription()->getVersion();

	public function onEnable(){
		$this->getLogger()->info("PermissionPlus loaded!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
                @mkdir($this->getDataFolder());
		PermissionSystem::init();
		CommandSystem::init();
                PermissionSystem::API()->CreateConfig();
                CommandSystem::API()->CreateConfig();
                if(file_exists($this->getDataFolder(). "config.yml")){
                	$per = PermissionSystem::API()->FormatConfig();
                        $cmd = CommandSystem::API()->FormatConfig();
                        if($per and $cmd){
                        	$this->getLogger()->info("ImportCompletion");
                                @unlink($this->getDataFolder()."config.yml");
                        }else{
                                $this->getLogger()->info("ImportError");
                        }
                }
	}

	public function onDisable(){
		PermissionSystem::API()->saveData();
		CommandSystem::API()->saveData();
	}


/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	コマンド
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

//TODO










}