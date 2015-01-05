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
        private $attachments = [];
        const VERSION = "1.3.2";

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
                        }else{
                                $this->getLogger()->info("ImportError");
                        }
                }
                $this->alias = [];
                $this->aliasPermissions = [];
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
                	$this->changeNametoEveryone2();
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
                        PermissionSystem::API()->ResetPermission($name);
                        foreach(Server::getInstance()->getOnlinePlayers() as $player){
                                $this->changeName($player);
                		$this->setPermission($player);
                        }
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

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
       他の処理
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function giveOP($username){
                $player = $this->getServer()->getPlayerExact($username);
                $player->sendMessage("You are now op!");
                $player->setOp(true);
        }

        public function giveOPtoEveryone(){
                foreach(Server::getInstance()->getOnlinePlayers() as $player){
                        $player->sendMessage("You are now op!");
                        $player->setOp(true);
                }
        }

        public function isAlnum($text){
                if(preg_match("/^[a-zA-Z0-9]+$/",$text)){
                        return true;
                }else{
                        return false;
                }
        }

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

        public function getPermission($permission){
                foreach($this->getServer()->getCommandMap()->getCommands() as $command){
                        if($command->getPermission() === $permission){
                                return $command;
                        }
                }
        }

        public function setPermission($player){
                if(PermissionSystem::API()->get("cmd-whitelist") and $player->isOp()){
                        $attachment = $this->getAttachment($player);
                        foreach(array_keys($attachment->getPermissions()) as $old_perm){
                                $attachment->unsetPermission($old_perm);
                        }
                        foreach(CommandSystem::API()->get('command')[$per = PermissionSystem::API()->getUserPermission($player->getName())] as $new_perm => $en){
                                $this->alias[$per] = [];
                                $command = $this->getServer()->getCommandMap()->getCommand($new_perm);
                                if($command instanceof Command){
                                        if($en){
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = [$alias,true];
                                                }




                                        }else{
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = [$alias,false];
                                                }
                                        }
                                }else{
                                        $command = $this->getPermission($new_perm);
                                        if($en){
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = [$alias,true];
                                                }




                                        }else{
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = [$alias,false];
                                                }



                                        }
                                }
                        }








/*
                        foreach($this->getServer()->getCommandMap()->getCommands() as $command){


                                        switch($new_perm){
                                        case $command->getPermission():
                                        if($en){
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = array($alias,true);
                                                }
                                                $attachment->setPermission($command->getPermission(),true);
                                                $this->alias[$per][] = array($command->getName(),true);
                                        }else{
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = array($alias,false);
                                                }
                                                $attachment->setPermission($command->getPermission(),false);
                                                $this->alias[$per][] = array($command->getName(),false);
                                        }
                                        break;
                                        case $command->getName():
                                        if($en){
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = array($alias,true);
                                                }
                                                $command->setPermission("permissionplus.command.".$command->getName()."");
                                                $attachment->setPermission($command->getPermission(),true);
                                                $this->alias[$per][] = array($command->getName(),true);
                                        }else{
                                                foreach($command->getAliases() as $alias){
                                                        $this->alias[$per][] = array($alias,false);
                                                }
                                                $command->setPermission("permissionplus.command.".$command->getName()."");
                                                $attachment->setPermission($command->getPermission(),false);
                                                $this->alias[$per][] = array($command->getName(),false);
                                        }
                                        break;
                                        }
                                }
                        }*/
                }
        }

        public function getAttachment(Player $player){
                if(!isset($this->attachments[$player->getName()])){
                        $this->attachments[$player->getName()] = $player->addAttachment($this);
                }else{
                        $player->removeAttachment($this->attachments[$player->getName()]);
                        $this->attachments[$player->getName()] = $player->addAttachment($this);
                }
                return $this->attachments[$player->getName()];
        }

        public function MainCommand($text){
                $maincmd = "";
                $mainend = "";
                for($number = 1; ; $number++){
                        if(!isset($text[$number]) or !isset($text[$number]) or $text[$number] === " "){
                                $mainend = $number;
                        break;
                        }
                        $maincmd .= $text[$number];
                }
                return array($maincmd,$mainend);
        }

        public function SubCommand($text,$amount){
                $subcmd = "";
                for($number = $amount; ; $number++){
                        if(!isset($text[$number]) or !isset($text[$number]) or $text[$number] === " "){
                                $number = $amount;
                        break;
                        }
                        $subcmd .= $text[$number];
                }
                return array($subcmd,$number);
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	Event
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function onPlayerJoin(PlayerJoinEvent $event){
                $player = $event->getPlayer();
                $username = $player->getName();
                if(!PermissionSystem::API()->getUserPermission($username)){
                        PermissionSystem::API()->createAccount($username);
                        PermissionSystem::API()->saveData();
                }
                if(PermissionSystem::API()->get("autoop")){
                        if($player instanceof Player){
                                $this->giveOP($username);
                        }
                }
                if(PermissionSystem::API()->get("PerName")){
                        if($player instanceof Player){
                                $this->changeName($player);
                        }
                }
                $this->setPermission($player);
        }

        public function onPlayerQuit(PlayerQuitEvent $event){
                $player = $event->getPlayer();
                if(PermissionSystem::API()->get("PerName")){
                        if($player instanceof Player){
                                $this->changeName2($player);
                        }
                }
        }

        public function onCommandEvent(PlayerCommandPreprocessEvent $event){
                $player = $event->getPlayer();
                $username = $player->getName();
                $text = $event->getMessage();
                if($text[0] === "/"){
                        $Main = $this->MainCommand($text);
                        $Sub = $this->SubCommand($text,$Main[1]+1);
                        if(PermissionSystem::API()->get("cmd-whitelist")){
                                $cmdCheck = CommandSystem::API()->checkPermission($username,$Main[0],$Sub[0],PermissionSystem::API()->get("notice"),$this->getLogger(),$this->alias);
                                if(!$cmdCheck){
                                        $event->setCancelled(true);
                                        $player->sendMessage("You don't have permissions to use this command.");
                                }
                        }
                }
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	名前変更
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function changeNametoEveryone(){
                foreach(Server::getInstance()->getOnlinePlayers() as $player){
                        $username = $player->getName();
                        $Permission = PermissionSystem::API()->getUserPermission($username);
                        if(is_null($Permission)){
                                $Permission = "ERROR";
                        }
                        $player->setNameTag("[".$Permission."] ".$username."");
                        $player->setDisplayName("[".$Permission."] ".$username."");
                }
        }

        public function changeNametoEveryone2(){
                foreach(Server::getInstance()->getOnlinePlayers() as $player){
                        $username = $player->getName();
                        $player->setNameTag($username);
                        $player->setDisplayName($username);
                }
        }

        public function changeName($player){
                $username = $player->getName();
                $Permission = PermissionSystem::API()->getUserPermission($username);
                if(is_null($Permission)){
                        $Permission = "ERROR";
                }
                $player->setNameTag("[".$Permission."] ".$username."");
                $player->setDisplayName("[".$Permission."] ".$username."");
        }

        public function changeName2($player){
                $username = $player->getName();
                $player->setNameTag($username);
                $player->setDisplayName($username);
        }

}