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

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		$username = $sender->getName();
		switch($command->getName()){
		case "pp":
		$msg = PermissionSystem::API()->permissionUsage("p");
		$sender->sendMessage("Usage: /ppplayer <player> $msg");
		$msg = PermissionSystem::API()->permissionUsage("c");
		$sender->sendMessage("Usage: /ppcommand <command or commandPermission> $msg");
		$msg = PermissionSystem::API()->permissionUsage("c");
		$sender->sendMessage("Usage: /ppsub <cmd> <subcmd> $msg");
		$sender->sendMessage("Usage: /ppconfig");
		break;
		case "ppplayer":
		$player = array_shift($args);
		$permission = array_shift($args);
		if(is_null($player) or is_null($permission)){
			if($username === "CONSOLE"){
                		PermissionSystem::API()->showPPermissionsList($sender);
			}
                	$msg = PermissionSystem::API()->permissionUsage("p");
                	$sender->sendMessage("Usage: /ppplayer <player> $msg");
		break;
		}
                PermissionSystem::API()->setPPermission($player, $permission,$sender);
                PermissionSystem::API()->saveData();
                $player = $sender->getServer()->getPlayerExact($player);
                if($player instanceof Player){
			$this->setPermission($player);
			$this->changeName($player);
		}
                break;
		case "ppcommand":
                $command = array_shift($args);
		if(is_null($command)) {
                	if($username === "CONSOLE"){
                		CommandSystem::API()->showCPermissionsList($sender);
                	}
                	$msg = PermissionSystem::API()->permissionUsage("c");
                	$sender->sendMessage("Usage: /ppcommand <command or commandPermission> $msg");
                break;
                }
                CommandSystem::API()->setCPermission($command,$args,$sender);
                CommandSystem::API()->saveData();
                foreach(Server::getInstance()->getOnlinePlayers() as $player){
                	$this->setPermission($player);
                }
                break;
		case "ppsub":
                $cmd = array_shift($args);
                $subcmd = array_shift($args);
                if(is_null($cmd) or is_null($subcmd)){
                	if ($username === "CONSOLE") {
                		CommandSystem::API()->showSPermissionsList($sender);
                	}
                        $msg = PermissionSystem::API()->permissionUsage("c");
                        $sender->sendMessage("Usage: /ppsub <cmd> <subcmd> $msg");
                break;
                }
                CommandSystem::API()->setSPermission($cmd, $subcmd, $args, $sender);
                CommandSystem::API()->saveData();
                foreach(Server::getInstance()->getOnlinePlayers() as $player){
                	$this->setPermission($player);
                }
                break;
                case "ppconfig":
		$config = array_shift($args);
           	switch($config){
           	case "notice":
		$bool = array_shift($args);
		if(!$bool = $this->castBool($bool)){
			$sender->sendMessage("Usage: /ppconfig notice <on | off>");
		break;
		}
                PermissionSystem::API()->set("notice", $bool);
                PermissionSystem::API()->saveData();
                if($bool){
                	$sender->sendMessage("[Permission+] Truned on to the notify function.");
                }else{
                	$sender->sendMessage("[Permission+] Truned off to the notify function.");
                }
                break;
                case "autoop":
                $bool = array_shift($args);
                if(!$bool = $this->castBool($bool)){
                	$sender->sendMessage("Usage: /ppconfig autoop <on | off>");
                break;
                }
                PermissionSystem::API()->set("autoop", $bool);
                PermissionSystem::API()->saveData();
                if($bool){
                	$sender->sendMessage("[Permission+] Truned on to the auto op function.");
                        $this->giveOPtoEveryone();
                }else{
                	$sender->sendMessage("[Permission+] Truned off to the auto op function.");
                }
                break;
		case "pername":
		$bool = array_shift($args);
		if(!$bool = $this->castBool($bool)){
                	$sender->sendMessage("Usage: /ppconfig pername <on | off>");
                break;
                }
                PermissionSystem::API()->set("PerName", $bool);
                PermissionSystem::API()->saveData();
                if($bool){
                	$sender->sendMessage("[Permission+] Truned on to the PerName function.");
                	$this->changeNametoEveryone();
                }else{
                	$sender->sendMessage("[Permission+] Truned off to the PerName function.");
                }
                break;
                case "cmdwhitelist":
                case "cmdw":
                $bool = array_shift($args);
                if(!$bool = $this->castBool($bool)){
                	$sender->sendMessage("Usage: /ppconfig cmdwhitelist <on | off>");
                break;
                }
                PermissionSystem::API()->set("cmdw", $bool);
                PermissionSystem::API()->saveData();
                if($bool){
                	$sender->sendMessage("[Permission+] Truned on to the cmd-whitelist function.");
                }else{
                	$sender->sendMessage("[Permission+] Truned off to the cmd-whitelist function.");
                	$sender->sendMessage("".TextFormat::DARK_RED."[Permission+] You have to restart PocketMine-MP to apply the setting!".TextFormat::WHITE."");
                }
                break;
                case "add":
                $name = array_shift($args);
                if(empty($name) || !$this->isAlnum($name)){
                	$sender->sendMessage("Usage: /ppconfig add <rank name>");
                break;
                }
                if(PermissionSystem::API()->addPermission($name)){
                	$sender->sendMessage("[Permission+] Successful!");
                }else{
                	$sender->sendMessage("[Permission+] Failed to add!");
                }
                break;
                case "rm":
                case "remove":
                $name = array_shift($args);
                if(empty($name) || !$this->isAlnum($name)){
                	$sender->sendMessage("Usage: /ppconfig remove <rank name>");
                break;
                }
                if(PermissionSystem::API()->removePermission($name)){
                	$sender->sendMessage("[Permission+] Successful!");
                }else{
                	$sender->sendMessage("[Permission+] Failed to remove!");
                }
                break;
                case "":
		default:
		$sender->sendMessage("Usage: /ppconfig notice <on | off>");
		$sender->sendMessage("Usage: /ppconfig autoop <on | off>");
		$sender->sendMessage("Usage: /ppconfig pername <on | off>");
		$sender->sendMessage("Usage: /ppconfig add <rank name>");
		$sender->sendMessage("Usage: /ppconfig remove <rank name>");
		break;
		}
                return true;
                break;
		}
        }






//TODO









}