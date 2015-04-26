<?php

namespace PermissionPlus;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class PermissionPlus extends PluginBase implements Listener, CommandExecutor{

	private $attachment = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!file_exists($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->CreateConfig();
		$this->FormatConfig();
		if($this->getServer()->getCodename() === "活発(Kappatsu)フグ(Fugu)"){
			$lang = $this->getServer()->getProperty("settings.language", "en");
		}elseif($this->getServer()->getCodename() === "絶好(Zekkou)ケーキ(Cake)"){
			$lang = $this->config->get("lang");
		}
		if(\Phar::running(true) !== ""){
			$this->lang = new Lang(\Phar::running(true) . "/src/PermissionPlus/lang/");
		}else{
			$this->lang = new Lang($this->getDataFolder()."src/PermissionPlus/lang/");
		}
		if(!$this->lang->LoadLang($lang)){
			$this->getServer()->shutdown();
		}else{
			$this->getLogger()->info($this->lang->getText("select.lang"));
		}
		$this->alias = [];
	}

	public function onDisable(){
		$this->config->save();
	}

// コマンド ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		$username = $sender->getName();
		switch($command->getName()){
			case "pp":
				$msg = $this->permissionUsage("p");
				$sender->sendMessage($this->lang->getText("usage")." /ppplayer ".$this->lang->getText("usage.p")." $msg");
				$msg = $this->permissionUsage("c");
				$sender->sendMessage($this->lang->getText("usage")." /ppcommand ".$this->lang->getText("usage.c")." $msg");
				$msg = $this->permissionUsage("c");
				$sender->sendMessage($this->lang->getText("usage")." /ppsub ".$this->lang->getText("usage.sc")." $msg");
				$sender->sendMessage($this->lang->getText("usage")." /ppconfig");
				break;
			case "ppplayer":
				$player = array_shift($args);
				$permission = array_shift($args);
				if(is_null($player) or is_null($permission)){
					if(!$sender instanceof Player){
						$this->showPPermissionsList($sender);
					}
					$msg = $this->permissionUsage("p");
					$sender->sendMessage($this->lang->getText("usage")." /ppplayer ".$this->lang->getText("usage.p")." $msg");
					break;
				}
				$this->setPPermission($player, $permission,$sender);
				$this->config->save();
				$player = $sender->getServer()->getPlayerExact($player);
				if($player instanceof Player){
					$this->setPermission($player);
					if($this->config->get("PerName")){
						$this->changeName($player);
					}
				}
				break;
			case "ppcommand":
				$command = array_shift($args);
				if(is_null($command)){
					if(!$sender instanceof Player){
						$this->showCPermissionsList($sender);
					}
					$msg = $this->permissionUsage("c");
					$sender->sendMessage($this->lang->getText("usage")." /ppcommand ".$this->lang->getText("usage.c")." $msg");
					break;
				}
				$this->setCPermission($command,$args,$sender);
				$this->config->save();
				foreach(Server::getInstance()->getOnlinePlayers() as $player){
					$this->setPermission($player);
				}
				break;
			case "ppsub":
				$cmd = array_shift($args);
				$subcmd = array_shift($args);
				if(is_null($cmd) or is_null($subcmd)){
					if(!$sender instanceof Player){
						$this->showSPermissionsList($sender);
					}
					$msg = $this->permissionUsage("c");
					$sender->sendMessage($this->lang->getText("usage")." /ppsub ".$this->lang->getText("usage.sc")." $msg");
					break;
				}
				$this->setSPermission($cmd, $subcmd, $args, $sender);
				$this->config->save();
				break;
			case "ppconfig":
				$config = array_shift($args);
				switch($config){
					case "notice":
						$bool = array_shift($args);
						$bool = $this->castBool($bool);
						if($bool === "default"){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig notice ".$this->lang->getText("usage.onoff")."");
							break;
						}
						$this->config->set("notice", $bool);
						$this->config->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.on", ["notify"]));
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.off", ["notify"]));
						}
						break;
					case "autoop":
						$bool = array_shift($args);
						$bool = $this->castBool($bool);
						if($bool === "default"){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig autoop ".$this->lang->getText("usage.onoff")."");
							break;
						}
						$this->config->set("autoop", $bool);
						$this->config->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.on", ["auto op"]));
							$this->giveOPtoEveryone();
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.off", ["auto op"]));
						}
						break;
					case "pername":
						$bool = array_shift($args);
						$bool = $this->castBool($bool);
						if($bool === "default"){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig pername ".$this->lang->getText("usage.onoff")."");
							break;
						}
						$this->config->set("PerName", $bool);
						$this->config->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.on", ["PerName"]));
							$this->changeNametoEveryone();
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.off", ["PerName"]));
							$this->changeNametoEveryone2();
						}
						break;
					case "lang":
						$bool = array_shift($args);
						if($bool === "" or !isset($bool)){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig lang ".$this->lang->getText("usage.lang")."");
							break;
						}
						if($this->lang->LoadLang($bool)){
							$this->getLogger()->info($this->lang->getText("select.lang"));
							$this->config->set("lang", $bool);
							$this->config->save();
						}
						break;
					case "cmdwhitelist":
					case "cmdw":
						$bool = array_shift($args);
						$bool = $this->castBool($bool);
						if($bool === "default"){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig cmdwhitelist ".$this->lang->getText("usage.onoff")."");
							break;
						}
						$this->config->set("cmd-whitelist", $bool);
						$this->config->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.on", ["cmd-whitelist"]));
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("pc.off", ["cmd-whitelist"]));
							$sender->sendMessage(TextFormat::DARK_RED."[Permission+] ".$this->lang->getText("restart")."".TextFormat::WHITE."");
						}
						break;
					case "add":
						$name = array_shift($args);
						if(empty($name) || !$this->isAlnum($name)){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig add ".$this->lang->getText("usage.rankname")."");
							break;
						}
						if($this->addPermission($name)){
							$sender->sendMessage("[Permission+] ".$this->lang->getText("success")."");
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("failed", [$this->lang->getText("add")]));
						}
						break;
					case "rm":
					case "remove":
						$name = array_shift($args);
						if(empty($name) || !$this->isAlnum($name)){
							$sender->sendMessage($this->lang->getText("usage")." /ppconfig remove ".$this->lang->getText("usage.rankname")."");
							break;
						}
						if($this->removePermission($name)){
							$sender->sendMessage("[Permission+] ".$this->lang->getText("success")."");
							$this->ResetPermission($name);
							foreach(Server::getInstance()->getOnlinePlayers() as $player){
								$this->changeName($player);
								$this->setPermission($player);
							}
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->transactionText("failed", [$this->lang->getText("remove")]));
						}
						break;
					case "":
					default:
						$sender->sendMessage($this->lang->getText("usage")." /ppconfig notice ".$this->lang->getText("usage.onoff")."");
						$sender->sendMessage($this->lang->getText("usage")." /ppconfig autoop ".$this->lang->getText("usage.onoff")."");
						$sender->sendMessage($this->lang->getText("usage")." /ppconfig pername ".$this->lang->getText("usage.onoff")."");
						$sender->sendMessage($this->lang->getText("usage")." /ppconfig lang ".$this->lang->getText("usage.lang")."");
						$sender->sendMessage($this->lang->getText("usage")." /ppconfig add ".$this->lang->getText("usage.rankname")."");
						$sender->sendMessage($this->lang->getText("usage")." /ppconfig remove ".$this->lang->getText("usage.rankname")."");
						break;
				}
				break;
		}
		return true;
	}

// 他の処理 ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function setPPermission($player, $permission,$sender){
		if(!$this->castPermission($permission)){
			$msg = $this->permissionUsage("p");
			$sender->sendMessage($this->lang->getText("usage")." /ppplayer ".$this->lang->getText("usage.p")." $msg");
			return;
		}
		$permission = $this->castPermission($permission);
		$players = $this->config->get("player");
		$players[$player] = $permission;
		$this->config->set("player",$players);
		$this->config->save();
		$sender->sendMessage("[Permission+] ".$this->lang->transactionText("per.give.s", [$permission, $player])."");
		$player = $sender->getServer()->getPlayerExact($player);
		if($player instanceof Player){
			$player->sendMessage("[PermissionPlus] ".$this->lang->transactionText("per.give.p", [$permission])."");
		}
	}

	public function setCPermission($command,$permissions,$sender){
		$msg ="";
		$return = array_fill_keys($this->getPermissions(), false);
		if(!empty($permissions)){
			foreach($permissions as $permission){
				$value = $permission;
				if(!$this->castPermission($permission)){
					$sender->sendMessage("[Permission+] Invalid value: \"$value\"");
					continue;
				}
				$permission = $this->castPermission($permission);
				$msg .= $permission." ";
				$return[$permission] = true;
			}
		}else{
			foreach($this->getPermissions() as $permission){
				$return[$permission] = false;
			}
		}
		foreach($this->getPermissions() as $permission){
			$newcmd = $this->config->get('command')[$permission];
			$newcmd[$command] = $return[$permission];
			$this->config->set("command", array_merge($this->config->get("command"), array($permission => $newcmd)));
			$this->config->save();
		}
		if(empty($msg)){
			$sender->sendMessage("[Permission+] \"/".$command."\" was disabled.");
		}else{
			$sender->sendMessage("[Permission+] Assigned ".$msg."to \"/".$command."\".");
		}
	}

	public function setSPermission($cmd, $sub, $permissions, $player){
		$msg ="";
		$return = array_fill_keys($this->getPermissions(), false);
		if(!empty($permissions)){
			foreach($permissions as $permission){
				$value = $permission;
				if(!$this->castPermission($permission)) {
					$player->sendMessage("[Permission+] Invalid value: \"$value\"");
					continue;
				}
				$permission = $this->castPermission($permission);
				$msg .= $permission." ";
				$return[$permission] =true;
			}
		}else{
			foreach($this->getPermissions() as $permission){
				$return[$permission] = false;
			}
		}
		foreach($this->getPermissions() as $permission){
			$newcmd = $this->config->get('subcmd')[$permission];
			$newcmd[$cmd][$sub] = $return[$permission];
			$this->config->set("subcmd", array_merge($this->config->get("subcmd"), array($permission => $newcmd)));
			$this->config->save();
		}
		if(empty($msg)){
			$player->sendMessage("[Permission+] \"/".$cmd." ".$sub."\" was disabled.");
		}else{
			$player->sendMessage("[Permission+] Assigned ".$msg."to \"/".$cmd." ".$sub."\".");
		}
	}

	public function showPPermissionsList($sender){
		foreach($this->config->get("player") as $username => $permission){
			$player = $sender->getServer()->getPlayerExact($username);
			if($player instanceof Player){
				$online = "ONLINE";
			}else{
				$online = "OFFLINE";
			}
			$pname = substr($permission, 0, 5);
			switch($online){
				case "OFFLINE":
				if(strlen($pname) === 5){
					$sender->sendMessage(TextFormat::DARK_BLUE."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE.":  ".$username."");
				}else{
					$space = str_repeat(" ", 6-strlen($pname)-1);
					$sender->sendMessage(TextFormat::DARK_BLUE."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE."".$space.":  ".$username."");
				}
				break;
				case "ONLINE":
				if(strlen($pname) === 5){
					$sender->sendMessage(TextFormat::DARK_RED."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE.":   ".$username."");
				}else{
					$space = str_repeat(" ", 6-strlen($pname)-1);
					$sender->sendMessage(TextFormat::DARK_RED."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE."".$space.":   ".$username."");
				}
				break;
			}
		}
	}

	public function showCPermissionsList($sender){
		$output ="";
		$permission = array();
		$clist = $this->config->get('command');
		foreach($this->getPermissions() as $prm){
			$pname = substr($prm, 0, 5);
			foreach($clist[$prm] as $command => $enable){
				if($enable){
					if($this->config->get("permission")[$prm]){
						$permission[$prm][$command] ="[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]";
					}else{
						$space = str_repeat(" ", 6-strlen($pname)-1);
						$permission[$prm][$command] = "[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]".$space."";
					}
				}else{
					$permission[$prm][$command] = "       ";
				}
			}
		}
		$line = "|";
		foreach($this->getCommands() as $command){
			foreach($this->getPermissions() as $prm){
				if(isset($permission[$prm][$command])){
					$output .= "".$line."".$permission[$prm][$command];
				}else{
					$output .= $line."[".TextFormat::RED."Error".TextFormat::WHITE."]";
				}
			}
			$output .= " :  /".$command."\n";
		}
		$sender->sendMessage($output);
	}

	public function showSPermissionsList($sender){
		$output = "";
		$permission = array();
		$clist = $this->config->get('subcmd');
		foreach($this->getPermissions() as $prm){
			$pname = substr($prm, 0, 5);
			foreach($clist[$prm] as $command => $subcmds){
				foreach($subcmds as $sub => $enable){
					if($enable){
						if($this->config->get("permission")[$prm]){
							$permission[$prm][$command."_".$sub] = "[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]";
						}else{
							$space =str_repeat(" ", 6-strlen($pname)-1);
							$permission[$prm][$command."_".$sub] = "[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]" .$space;
						}
					}else{
						$permission[$prm][$command."_".$sub] = "       ";
					}
				}
			}
		}
		foreach($this->config->get("subcmd")["ADMIN"] as $command => $subcmds){
			foreach(array_keys($subcmds) as $sub){
				$line = "|";
				foreach($this->getPermissions() as $prm){
					if(isset($permission[$prm][$command."_".$sub])){
						$output .= "".$line."".$permission[$prm][$command."_".$sub];
					}else{
						$output .= "".$line."[".TextFormat::RED."Error".TextFormat::WHITE."]";
					}
				}
				$sender->sendMessage("".$output.":  /".$command." ".$sub."");
				$output = "";
			}
		}
	}

	public function checkPermission($player,$cmd,$sub,$notice,$usage){
		$permission = $this->getUserPermission($player);
		if($notice and !isset($this->config->get('command')['ADMIN'][$cmd])){
			$usage->info("NOTICE: \"/".$cmd."\" permission is not setted!");
			$usage->info("Usage: /ppcommand ".$cmd." (g) (t) (a)");
		}
		if(!empty($sub)){
			if(isset($this->config->get('subcmd')[$permission][$cmd][$sub]) && !$this->config->get('subcmd')[$permission][$cmd][$sub]){
				return false;
			}
		}
		if(isset($this->alias[$permission][$cmd]) and !$this->alias[$permission][$cmd]){
			return false;
		}
		return true;
	}

	public function getUserPermission($username){
		if(isset($this->config->get("player")[$username])){
			return $this->config->get("player")[$username];
		}else{
			return false;
		}
	}

	public function getCommands(){
		$cmds = array_keys($this->config->get('command')['ADMIN']);
		return $cmds;
	}

	public function getPermissions(){
		$prms = array_keys($this->config->get("permission"));
		return $prms;
	}

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

	public function castPermission($permission){
		$permission = strtoupper($permission);
		switch($permission){
			case "A":
			case "ADMIN":
				$permission = "ADMIN";
				return $permission;
				break;
			case "T":
			case "TRUST":
				$permission = "TRUST";
				return $permission;
				break;
			case "G":
			case "GUEST":
				$permission = "GUEST";
				return $permission;
				break;
			default:
				if(in_array($permission, $this->getPermissions())){
					return $permission;
				}
				return false;
				break;
		}
		return true;
	}

	public function permissionUsage($type){
		switch($type){
			case "p":
				$output ="<";
				$border= "";
				foreach($this->getPermissions() as $prm){
					$prm = strtolower($prm);
					$output .= $border.$prm;
					$border =" | ";
				}
				$output .= ">";
				break;
			case "c":
				$output ="";
				foreach($this->getPermissions() as $prm){
					$prm = strtolower($prm);
					$output .= "(".$prm.")";
				}
				break;
			default:
				return false;
		}
		return $output;
	}

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
				return "default";
				break;
		}
	}

// Config ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function CreateConfig(){
		$this->config = new Config($this->getDataFolder()."config.yml", CONFIG::YAML, array(
			"notice" => true,
			"autoop" => false,
			"PerName" => false,
			"lang" => "en",
			"cmd-whitelist" => true,
			"player" => array(),
			"permission" => array(
				"GUEST" => true,
				"TRUST" => true,
				"ADMIN" => true,
				),
			"subcmd" => array(
				"ADMIN" => array(),
				"TRUST" => array(),
				"GUEST" => array(),
				),
			"command" => array(
				"ADMIN" => array(
					'ban' => true,
					'ban-ip' => true,
					'banlist' => true,
					'defaultgamemode' => true,
					'deop' => true,
					'difficulty' => true,
					'gamemode' => true,
					'give' => true,
					'help' => true,
					'kick' => true,
					'kill' => true,
					'list' => true,
					'me' => true,
					'op' => true,
					'pardon' => true,
					'pardon-ip' => true,
					'plugins' => true,
					'reload' => true,
					'save-all' => true,
					'save-off' => true,
					'save-on' => true,
					'say' =>true,
					'seed' => true,
					'setworldspawn' => true,
					'spawnpoint' => true,
					'status' => true,
					'stop' => true,
					'tell' => true,
					'time' => true,
					'timings' => true,
					'tp' => true,
					'version' => true,
					'whitelist' => true,
					'ppplayer' => true,
					'ppcommand' => true,
					'ppconfig' => true,
					),
				"TRUST" => array(
					'ban' => false,
					'ban-ip' => false,
					'banlist' => false,
					'defaultgamemode' => false,
					'deop' => true,
					'difficulty' => false,
					'gamemode' => false,
					'give' => true,
					'help' => true,
					'kick' => true,
					'kill' => false,
					'list' => true,
					'me' => true,
					'op' => true,
					'pardon' => false,
					'pardon-ip' => false,
					'plugins' => true,
					'reload' => true,
					'save-all' => false,
					'save-off' => false,
					'save-on' => false,
					'say' => true,
					'seed' => true,
					'setworldspawn' => true,
					'spawnpoint' => true,
					'status' => true,
					'stop' => false,
					'tell' => true,
					'time' => true,
					'timings' => true,
					'tp' => true,
					'version' => false,
					'whitelist' => false,
					'ppplayer' => false,
					'ppcommand' => false,
					'ppconfig' => false,
					),
				"GUEST" => array(
					'ban' => false,
					'ban-ip' => false,
					'banlist' => false,
					'defaultgamemode' => false,
					'deop' => false,
					'difficulty' => false,
					'gamemode' => false,
					'give' => false,
					'help' => false,
					'kick' => false,
					'kill' => false,
					'list' => true,
					'me' => false,
					'op' => false,
					'pardon' => false,
					'pardon-ip' => false,
					'plugins' => false,
					'reload' => false,
					'save-all' => false,
					'save-off' => false,
					'save-on' => false,
					'say' => false,
					'seed' => false,
					'setworldspawn' => false,
					'spawnpoint' => false,
					'status' => false,
					'stop' => false,
					'tell' => false,
					'time' => false,
					'timings' => false,
					'tp' => false,
					'version' => false,
					'whitelist' => false,
					'ppplayer' => false,
					'ppcommand' => false,
					'ppconfig' => false,
					),
				),
				));
		$this->config->save();
	}

	public function FormatConfig(){
		if(file_exists($this->getDataFolder(). "Account.yml") and file_exists($this->getDataFolder(). "Permission.yml") and file_exists($this->getDataFolder(). "Command.yml")){
			$Permission = new Config($this->getDataFolder()."Permission.yml", CONFIG::YAML);
			$Command = new Config($this->getDataFolder()."Command.yml", CONFIG::YAML);
			$Account = new Config($this->getDataFolder()."Account.yml", CONFIG::YAML);
			foreach($Permission->getAll() as $name => $data){
				$this->config->set($name,$data);
			}
			foreach($Command->getAll() as $name => $data){
				$this->config->set($name,$data);
			}
			$players = $this->config->get("player");
			foreach($Account->getAll() as $username => $per){
				$players[$username] = $per["Permission"];
			}
			$this->config->set("player",$players);
			$this->config->save();
			if(unlink($this->getDataFolder()."Account.yml") and unlink($this->getDataFolder()."Permission.yml") and unlink($this->getDataFolder()."Command.yml")){
				$this->getLogger()->info("ImportCompletion");
			}else{
				$this->getLogger()->info("ImportError");
			}
		}
	}

	public function addPermission($permission){
		$permission = strtoupper($permission);
		$permissions = $this->getPermissions();
		if(!in_array($permission, array_merge(array("g", "t", "a"), $permissions))){
			$this->config->set("permission", array_merge($this->config->get("permission"), array($permission => false)));
			$this->config->set("command", array_merge($this->config->get("command"), array($permission => array_fill_keys($this->getCommands(),false))));
			$this->config->set("subcmd", array_merge($this->config->get("subcmd"), array($permission => array())));
			$new_cmd = [];
			foreach($this->config->get("subcmd")["ADMIN"] as $cmd => $subcmds){
				$new_cmd[$cmd] = [];
				foreach(array_keys($subcmds) as $sub){
					$new_cmd[$cmd][$sub] = false;
				}
				$this->config->set("subcmd", array_merge($this->config->get("subcmd"), array($permission => $new_cmd)));
			}
			$this->config->save();
			return true;
		}
		return false;
	}

	public function removePermission($permission){
		$permission = strtoupper($permission);
		if(isset($this->config->get("permission")[$permission]) && !$this->config->get("permission")[$permission]){
			$permissions = $this->config->get("permission");
			unset($permissions[$permission]);
			$this->config->set("permission",$permissions);
			$cper = $this->config->get("command");
			$sper = $this->config->get("subcmd");
			unset($cper[$permission]);
			unset($sper[$permission]);
			$this->config->set("command",$cper);
			$this->config->set("subcmd",$sper);
			$this->config->save();
			return true;
		}
		return false;
	}

	public function ResetPermission($permission){
		foreach($this->config->get("player") as $username => $per){
			if($per === $permission){
				$players = $this->config->get("player");
				$players[$username] = "GUEST";
				$this->config->set("player",$players);
			}
		}
		$this->config->save();
	}

// CommandPermission関連 ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function getPermission($permission){
		foreach($this->getServer()->getCommandMap()->getCommands() as $command){
			if($command->getPermission() === $permission){
				return $command;
			}
		}
		return false;
	}

	public function setPermission($player){
		if($this->config->get("cmd-whitelist")){
			$attachment = $this->getAttachment($player);
			$attachment->clearPermissions();
			$per = $this->getUserPermission($player->getName());
			$old_alias = [];
			if(isset($this->alias[$per])){
				$old_alias[$per] = $this->alias[$per];
			}else{
				$old_alias[$per] = [];
			}
			$this->alias[$per] = [];
			foreach($this->config->get('command')[$per] as $new_perm => $en){
				$command = $this->getServer()->getCommandMap()->getCommand($new_perm);
				if($command instanceof Command){
					if($en){
						foreach($command->getAliases() as $alias){
							$this->alias[$per][$alias] = true;
						}
						if(strstr($command->getPermission(),';') or isset($old_alias[$per][$command->getName()])){
							$this->alias[$per][$command->getName()] = true;
						}
						$command->setPermission("permissionplus.command.".$command->getName()."");
						$attachment->setPermission($command->getPermission(),true);
					}else{
						foreach($command->getAliases() as $alias){
							$this->alias[$per][$alias] = false;
						}
						if(strstr($command->getPermission(),';') or isset($old_alias[$per][$command->getName()])){
							$this->alias[$per][$command->getName()] = false;
						}
						$command->setPermission("permissionplus.command.".$command->getName()."");
						$attachment->setPermission($command->getPermission(),false);
					}
				}else{
					$command = $this->getPermission($new_perm);
					if($command instanceof Command){
						if($en){
							foreach($command->getAliases() as $alias){
								$this->alias[$per][$alias] = true;
							}
							if(strstr($command->getPermission(),';') or isset($old_alias[$per][$command->getName()])){
								$this->alias[$per][$command->getName()] = true;
							}
							$attachment->setPermission($command->getPermission(),true);
						}else{
							foreach($command->getAliases() as $alias){
								$this->alias[$per][$alias] = false;
							}
							if(strstr($command->getPermission(),';') or isset($old_alias[$per][$command->getName()])){
								$this->alias[$per][$command->getName()] = false;
							}
							$attachment->setPermission($command->getPermission(),false);
						}
					}
				}
			}
			$player->recalculatePermissions();
		}
	}

	public function getAttachment($player){
		if(!isset($this->attachment[$player->getName()])){
			$this->attachment[$player->getName()] = $player->addAttachment($this);
		}
		return $this->attachment[$player->getName()];
	}

	public function removeAttachment($player){
		$player->removeAttachment($this->getAttachment($player));
		unset($this->attachment[$player->getName()]);
	}

	public function MainCommand($text){
		$maincmd = "";
		$mainend = "";
		for($number = 1; ; $number++){
			if(!isset($text[$number]) or $text[$number] === "" or $text[$number] === " "){
				$mainend = $number;
				break;
			}
			$maincmd .= $text[$number];
		}
		return [$maincmd,$mainend];
	}

	public function SubCommand($text,$amount){
		$subcmd = "";
		for($number = $amount; ; $number++){
			if(!isset($text[$number]) or $text[$number] === "" or $text[$number] === " "){
				$number = $amount;
				break;
			}
			$subcmd .= $text[$number];
		}
		return [$subcmd,$number];
	}

// Event ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$username = $player->getName();
		if(!$this->getUserPermission($username)){
			$players = $this->config->get("player");
			$players[$username] = "GUEST";
			$this->config->set("player",$players);
			$this->config->save();
		}
		if($this->config->get("autoop")){
			$this->giveOP($username);
		}
		if($this->config->get("PerName")){
			$this->changeName($player);
		}
		$this->setPermission($player);
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if($this->config->get("PerName")){
			$this->changeName2($player);
		}
		$this->removeAttachment($player);
	}

	public function onCommandEvent(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$username = $player->getName();
		$text = $event->getMessage();
		if($text[0] === "/" and $this->config->get("cmd-whitelist")){
			$Main = $this->MainCommand($text);
			$Sub = $this->SubCommand($text,$Main[1]+1);
			$cmdCheck = $this->checkPermission($username,$Main[0],$Sub[0],$this->config->get("notice"),$this->getLogger());
			if(!$cmdCheck){
				$player->sendMessage("You don't have permissions to use this command.");
				$event->setCancelled(true);
			}
		}
	}

// 名前変更 ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function changeNametoEveryone(){
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$username = $player->getName();
			$Permission = $this->getUserPermission($username);
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
		$Permission = $this->getUserPermission($username);
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