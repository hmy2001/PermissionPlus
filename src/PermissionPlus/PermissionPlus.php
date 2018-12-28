<?php

namespace PermissionPlus;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\lang\BaseLang;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class PermissionPlus extends PluginBase implements Listener, CommandExecutor{
	/** @var BaseLang $lang */
	private $lang, $path;
	private $attachment = [], $alias = [], $Commands = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->CreateConfig();

		$prefixPath = $this->getFile();
		if($this->isPhar()){
			$prefixPath = \Phar::running(true);
		}
		$this->path = $prefixPath."src/PermissionPlus/lang/";
		$languageList = BaseLang::getLanguageList($this->path);

		$langName = $this->getConfig()->get("lang");
		if($langName === "PocketMine"){
			$langName = $this->getServer()->getProperty("settings.language", "eng");
		}

		if(!isset($languageList[$langName])){
			$this->getLogger()->error("Failed to load lang file.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			$this->getServer()->shutdown();
			return;
		}

		$this->lang = new BaseLang($langName, $this->path);
		$this->getLogger()->info($this->lang->translateString("select.lang"));

		$this->ResetPermissions();
	}

	public function onDisable(){
		$this->getConfig()->save();
	}

	// コマンド ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "pp":
				$msg = $this->permissionUsage("p");
				$sender->sendMessage($this->lang->translateString("usage")." /ppplayer ".$this->lang->translateString("usage.p")." $msg");
				$msg = $this->permissionUsage("c");
				$sender->sendMessage($this->lang->translateString("usage")." /ppcommand ".$this->lang->translateString("usage.c")." $msg");
				$msg = $this->permissionUsage("c");
				$sender->sendMessage($this->lang->translateString("usage")." /ppsub ".$this->lang->translateString("usage.sc")." $msg");
				$sender->sendMessage($this->lang->translateString("usage")." /ppconfig");
				break;
			case "ppplayer":
				$player = array_shift($args);
				$permission = array_shift($args);
				if(is_null($player) or is_null($permission)){
					if(!$sender instanceof Player){
						$this->showPPermissionsList($sender);
					}
					$msg = $this->permissionUsage("p");
					$sender->sendMessage($this->lang->translateString("usage")." /ppplayer ".$this->lang->translateString("usage.p")." $msg");
					break;
				}
				$this->setPPermission($player, $permission,$sender);
				$this->getConfig()->save();
				$player = $sender->getServer()->getPlayerExact($player);
				if($player instanceof Player){
					$this->setPermission($player);
					if($this->getConfig()->get("PerName")){
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
					$sender->sendMessage($this->lang->translateString("usage")." /ppcommand ".$this->lang->translateString("usage.c")." $msg");
					break;
				}
				$this->setCPermission($command,$args,$sender);
				$this->getConfig()->save();
				$this->ResetPermissions();
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
					$sender->sendMessage($this->lang->translateString("usage")." /ppsub ".$this->lang->translateString("usage.sc")." $msg");
					break;
				}
				$this->setSPermission($cmd, $subcmd, $args, $sender);
				$this->getConfig()->save();
				break;
			case "ppconfig":
				$config = array_shift($args);
				switch($config){
					case "notice":
						$bool = array_shift($args);
						if(!$this->castBool($bool)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig notice ".$this->lang->translateString("usage.onoff")."");
							break;
						}
						$this->getConfig()->set("notice", $bool);
						$this->getConfig()->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.on", ["notify"]));
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.off", ["notify"]));
						}
						break;
					case "autoop":
						$bool = array_shift($args);
						if(!$this->castBool($bool)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig autoop ".$this->lang->translateString("usage.onoff")."");
							break;
						}
						$this->getConfig()->set("autoop", $bool);
						$this->getConfig()->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.on", ["auto op"]));
							$this->giveOPtoEveryone();
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.off", ["auto op"]));
						}
						break;
					case "pername":
						$bool = array_shift($args);
						if(!$this->castBool($bool)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig pername ".$this->lang->translateString("usage.onoff")."");
							break;
						}
						$this->getConfig()->set("PerName", $bool);
						$this->getConfig()->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.on", ["PerName"]));
							$this->changeNametoEveryone();
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.off", ["PerName"]));
							$this->changeNametoEveryone2();
						}
						break;
					case "lang":
						$bool = array_shift($args);
						if($bool === "" or !isset($bool)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig lang ".$this->lang->translateString("usage.lang")."");
							break;
						}
						if($bool === "PocketMine"){
							$bool = $this->getServer()->getProperty("settings.language", "en");
						}

						$languageList = BaseLang::getLanguageList($this->path);
						if(isset($languageList[$bool])){
							$this->lang = new BaseLang($bool, $this->path);
							$this->getLogger()->info($this->lang->translateString("select.lang"));
							$this->getConfig()->set("lang", $bool);
							$this->getConfig()->save();
						}
						break;
					case "cmdwhitelist":
					case "cmdw":
						$bool = array_shift($args);
						if(!$this->castBool($bool)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig cmdwhitelist ".$this->lang->translateString("usage.onoff")."");
							break;
						}
						$this->getConfig()->set("cmd-whitelist", $bool);
						$this->getConfig()->save();
						if($bool){
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.on", ["cmd-whitelist"]));
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("pc.off", ["cmd-whitelist"]));
							$sender->sendMessage(TextFormat::DARK_RED."[Permission+] ".$this->lang->translateString("restart")."".TextFormat::WHITE."");
						}
						break;
					case "add":
						$name = array_shift($args);
						if(empty($name) || !$this->isAlnum($name)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig add ".$this->lang->translateString("usage.rankname")."");
							break;
						}
						if($this->addPermission($name)){
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("success")."");
							$this->ResetPermissions();
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("failed", [$this->lang->translateString("add")]));
						}
						break;
					case "rm":
					case "remove":
						$name = array_shift($args);
						if(empty($name) || !$this->isAlnum($name)){
							$sender->sendMessage($this->lang->translateString("usage")." /ppconfig remove ".$this->lang->translateString("usage.rankname")."");
							break;
						}
						if($this->removePermission($name)){
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("success")."");
							$this->ResetPermission($name);
							$this->ResetPermissions();
							foreach(Server::getInstance()->getOnlinePlayers() as $player){
								$this->changeName($player);
								$this->setPermission($player);
							}
						}else{
							$sender->sendMessage("[Permission+] ".$this->lang->translateString("failed", [$this->lang->translateString("remove")]));
						}
						break;
					case "":
					default:
						$sender->sendMessage($this->lang->translateString("usage")." /ppconfig notice ".$this->lang->translateString("usage.onoff")."");
						$sender->sendMessage($this->lang->translateString("usage")." /ppconfig autoop ".$this->lang->translateString("usage.onoff")."");
						$sender->sendMessage($this->lang->translateString("usage")." /ppconfig pername ".$this->lang->translateString("usage.onoff")."");
						$sender->sendMessage($this->lang->translateString("usage")." /ppconfig lang ".$this->lang->translateString("usage.lang")."");
						$sender->sendMessage($this->lang->translateString("usage")." /ppconfig add ".$this->lang->translateString("usage.rankname")."");
						$sender->sendMessage($this->lang->translateString("usage")." /ppconfig remove ".$this->lang->translateString("usage.rankname")."");
						break;
				}
				break;
		}
		return true;
	}

	// 他の処理 ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function setPPermission($player, $permission, CommandSender $sender){
		if(!$this->castPermission($permission)){
			$msg = $this->permissionUsage("p");
			$sender->sendMessage($this->lang->translateString("usage")." /ppplayer ".$this->lang->translateString("usage.p")." $msg");
			return;
		}
		$permission = $this->castPermission($permission);
		$players = $this->getConfig()->get("player");
		$players[$player] = $permission;
		$this->getConfig()->set("player",$players);
		$this->getConfig()->save();
		$sender->sendMessage("[Permission+] ".$this->lang->translateString("per.give.s", [$permission, $player])."");
		$player = $sender->getServer()->getPlayerExact($player);
		if($player instanceof Player){
			$player->sendMessage("[PermissionPlus] ".$this->lang->translateString("per.give.p", [$permission])."");
		}
	}

	public function setCPermission($command, $permissions, CommandSender $sender){
		$msg ="";
		$return = array_fill_keys($this->getPermissions(), false);
		if(!empty($permissions)){
			foreach($permissions as $permission){
				$value = $permission;
				if(!$this->castPermission($permission)){
					$sender->sendMessage("[Permission+] ".$this->lang->translateString("invalid.value").": \"$value\"");
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
			$newcmd = $this->getConfig()->get('command')[$permission];
			$newcmd[$command] = $return[$permission];
			$this->getConfig()->set("command", array_merge($this->getConfig()->get("command"), [$permission => $newcmd]));
			$this->getConfig()->save();
		}
		if(empty($msg)){
			$sender->sendMessage("[Permission+] ".$this->lang->translateString("cmd.disable", ["\"/".$command."\""])."");
		}else{
			$sender->sendMessage("[Permission+] ".$this->lang->translateString("cmd.change", ["\"/".$command."\"", $msg])."");
		}
	}

	public function setSPermission($cmd, $sub, $permissions, CommandSender $player){
		$msg ="";
		$return = array_fill_keys($this->getPermissions(), false);
		if(!empty($permissions)){
			foreach($permissions as $permission){
				$value = $permission;
				if(!$this->castPermission($permission)) {
					$player->sendMessage("[Permission+] ".$this->lang->translateString("invalid.value").": \"$value\"");
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
			$newcmd = $this->getConfig()->get('subcmd')[$permission];
			$newcmd[$cmd][$sub] = $return[$permission];
			$this->getConfig()->set("subcmd", array_merge($this->getConfig()->get("subcmd"), [$permission => $newcmd]));
			$this->getConfig()->save();
		}
		if(empty($msg)){
			$player->sendMessage("[Permission+] ".$this->lang->translateString("cmd.disable", ["\"/".$cmd." ".$sub."\""])."");
		}else{
			$player->sendMessage("[Permission+] ".$this->lang->translateString("cmd.change", ["\"/".$cmd." ".$sub."\"", $msg])."");
		}
	}

	public function showPPermissionsList(CommandSender $sender){
		foreach($this->getConfig()->get("player") as $username => $permission){
			$player = $this->getServer()->getPlayerExact($username);
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
					$space = str_repeat(" ", 5 - strlen($pname));
					$sender->sendMessage(TextFormat::DARK_BLUE."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE."".$space.":  ".$username."");
				}
				break;
				case "ONLINE":
				if(strlen($pname) === 5){
					$sender->sendMessage(TextFormat::DARK_RED."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE.":   ".$username."");
				}else{
					$space = str_repeat(" ", 5 - strlen($pname));
					$sender->sendMessage(TextFormat::DARK_RED."[".$online."]".TextFormat::GREEN."[".$pname."]".TextFormat::WHITE."".$space.":   ".$username."");
				}
				break;
			}
		}
	}

	public function showCPermissionsList(CommandSender $sender){
		$output ="";
		$permission = [];
		$clist = $this->getConfig()->get('command');
		foreach($this->getPermissions() as $prm){
			$pname = substr($prm, 0, 5);
			foreach($clist[$prm] as $command => $enable){
				if($enable){
					if($this->getConfig()->get("permission")[$prm]){
						$permission[$prm][$command] ="[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]";
					}else{
						$space = str_repeat(" ", 5 - strlen($pname));
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

	public function showSPermissionsList(CommandSender $sender){
		$output = "";
		$permission = [];
		$clist = $this->getConfig()->get('subcmd');
		foreach($this->getPermissions() as $prm){
			$pname = substr($prm, 0, 5);
			foreach($clist[$prm] as $command => $subcmds){
				foreach($subcmds as $sub => $enable){
					if($enable){
						if($this->getConfig()->get("permission")[$prm]){
							$permission[$prm][$command."_".$sub] = "[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]";
						}else{
							$space =str_repeat(" ", 5 - strlen($pname));
							$permission[$prm][$command."_".$sub] = "[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]" .$space;
						}
					}else{
						$permission[$prm][$command."_".$sub] = "       ";
					}
				}
			}
		}
		foreach($this->getConfig()->get("subcmd")["ADMIN"] as $command => $subcmds){
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

	public function checkPermission($player, $cmd, $sub, $notice){
		$permission = $this->getUserPermission($player);
		if($notice and !isset($this->getConfig()->get("command")["ADMIN"][$cmd])){
			$this->getLogger()->info($this->lang->translateString("per.not", ["\"/".$cmd."\""]));
			$this->getLogger()->info($this->lang->translateString("usage")." /ppcommand ".$cmd." (g) (t) (a)");
		}
		if(!empty($sub)){
			if(isset($this->getConfig()->get('subcmd')[$permission][$cmd][$sub]) && !$this->getConfig()->get('subcmd')[$permission][$cmd][$sub]){
				return false;
			}
		}
		if(isset($this->alias[$permission][$cmd]) and !$this->alias[$permission][$cmd]){
			return false;
		}
		return true;
	}

	public function getUserPermission($username){
		if(isset($this->getConfig()->get("player")[$username])){
			return $this->getConfig()->get("player")[$username];
		}else{
			return false;
		}
	}

	public function getCommands(){
		return array_keys($this->getConfig()->get('command')['ADMIN']);
	}

	public function getPermissions(){
		return array_keys($this->getConfig()->get("permission"));
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
			break;
		}
		return $output;
	}

	public function castBool(&$bool){
		$bool = strtoupper($bool);
		switch($bool){
			case "TRUE":
			case "ON":
			case "1":
				$bool = true;
			break;
			case "FALSE":
			case "OFF":
			case "0":
				$bool = false;
			break;
			default:
				return false;
			break;
		}
		return true;
	}

	// Config ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function CreateConfig(){
		$this->getConfig()->setAll([
			"notice" => true,
			"autoop" => false,
			"PerName" => false,
			"lang" => "eng",
			"cmd-whitelist" => true,
			"player" => [],
			"permission" => [
				"GUEST" => true,
				"TRUST" => true,
				"ADMIN" => true,
				],
			"subcmd" => [
				"ADMIN" => [],
				"TRUST" => [],
				"GUEST" => [],
				],
			"command" => [
				"ADMIN" => [
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
				],
				"TRUST" => [
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
				],
				"GUEST" => [
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
				],
			],
		]);
		$this->getConfig()->save();
	}

	public function addPermission($permission){
		$permission = strtoupper($permission);
		$permissions = $this->getPermissions();
		if(!in_array($permission, array_merge(["g", "t", "a"], $permissions))){
			$this->getConfig()->set("permission", array_merge($this->getConfig()->get("permission"), [$permission => false]));
			$this->getConfig()->set("command", array_merge($this->getConfig()->get("command"), [$permission => array_fill_keys($this->getCommands(), false)]));
			$this->getConfig()->set("subcmd", array_merge($this->getConfig()->get("subcmd"), [$permission => []]));
			$new_cmd = [];
			foreach($this->getConfig()->get("subcmd")["ADMIN"] as $cmd => $subcmds){
				$new_cmd[$cmd] = [];
				foreach(array_keys($subcmds) as $sub){
					$new_cmd[$cmd][$sub] = false;
				}
				$this->getConfig()->set("subcmd", array_merge($this->getConfig()->get("subcmd"), [$permission => $new_cmd]));
			}
			$this->getConfig()->save();
			return true;
		}
		return false;
	}

	public function removePermission($permission){
		$permission = strtoupper($permission);
		if(isset($this->getConfig()->get("permission")[$permission]) && !$this->getConfig()->get("permission")[$permission]){
			$permissions = $this->getConfig()->get("permission");
			unset($permissions[$permission]);
			$this->getConfig()->set("permission",$permissions);
			$cper = $this->getConfig()->get("command");
			$sper = $this->getConfig()->get("subcmd");
			unset($cper[$permission]);
			unset($sper[$permission]);
			$this->getConfig()->set("command",$cper);
			$this->getConfig()->set("subcmd",$sper);
			$this->getConfig()->save();
			return true;
		}
		return false;
	}

	public function ResetPermission($permission){
		foreach($this->getConfig()->get("player") as $username => $per){
			if($per === $permission){
				$players = $this->getConfig()->get("player");
				$players[$username] = "GUEST";
				$this->getConfig()->set("player",$players);
			}
		}
		$this->getConfig()->save();
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

	public function setPermission(Player $player){
		if($this->getConfig()->get("cmd-whitelist")){
			$attachment = $this->getAttachment($player);
			$attachment->clearPermissions();
			$per = $this->getUserPermission($player->getName());
			foreach($this->Commands[$per] as $cmd => $flag){
				$attachment->setPermission($cmd, $flag);
			}
			$player->recalculatePermissions();
		}
	}

	public function ResetPermissions(){
		$this->alias = [];
		$this->Commands = [];
		foreach($this->getConfig()->get('command') as $per => $cmd_perms){
			foreach($cmd_perms as $cmd => $flag){
				$command = $this->getServer()->getCommandMap()->getCommand($cmd);
				if($command instanceof Command){
					if($flag){
						foreach($command->getAliases() as $alias){
							$this->alias[$per][$alias] = true;
						}
						if(strstr($command->getPermission(),';') or isset($old_alias[$per][strtolower($command->getName())])){
							$this->alias[$per][strtolower($command->getName())] = true;
						}
						$command->setPermission("permissionplus.command.".$command->getName()."");
						$this->Commands[$per][$command->getPermission()] = true;
					}else{
						foreach($command->getAliases() as $alias){
							$this->alias[$per][$alias] = false;
						}
						if(strstr($command->getPermission(),';') or isset($old_alias[$per][strtolower($command->getName())])){
							$this->alias[$per][strtolower($command->getName())] = false;
						}
						$command->setPermission("permissionplus.command.".$command->getName()."");
						$this->Commands[$per][$command->getPermission()] = false;
					}
				}else{
					$command = $this->getPermission($cmd);
					if($command instanceof Command){
						if($flag){
							foreach($command->getAliases() as $alias){
								$this->alias[$per][$alias] = true;
							}
							if(strstr($command->getPermission(),';') or isset($old_alias[$per][strtolower($command->getName())])){
								$this->alias[$per][strtolower($command->getName())] = true;
							}
							$this->Commands[$per][$command->getPermission()] = true;
						}else{
							foreach($command->getAliases() as $alias){
								$this->alias[$per][$alias] = false;
							}
							if(strstr($command->getPermission(),';') or isset($old_alias[$per][strtolower($command->getName())])){
								$this->alias[$per][strtolower($command->getName())] = false;
							}
							$this->Commands[$per][$command->getPermission()] = false;
						}
					}
				}
			}
		}
	}

	public function getAttachment(Player $player){
		if(!isset($this->attachment[$player->getName()])){
			$this->attachment[$player->getName()] = $player->addAttachment($this);
		}
		return $this->attachment[$player->getName()];
	}

	public function removeAttachment(Player $player){
		$player->removeAttachment($this->getAttachment($player));
		unset($this->attachment[$player->getName()]);
	}

	// Event ///////////////////////////////////////////////////////////////////////////////////////////////////
	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$username = $player->getName();
		if(!$this->getUserPermission($username)){
			$players = $this->getConfig()->get("player");
			$players[$username] = "GUEST";
			$this->getConfig()->set("player",$players);
			$this->getConfig()->save();
		}
		if($this->getConfig()->get("autoop")){
			$this->giveOP($username);
		}
		if($this->getConfig()->get("PerName")){
			$this->changeName($player);
		}
		$this->setPermission($player);
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if($this->getConfig()->get("PerName")){
			$this->changeName2($player);
		}
		$this->removeAttachment($player);
	}

	public function onCommandEvent(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$username = $player->getName();
		$text = $event->getMessage();
		if($text[0] === "/" and $this->getConfig()->get("cmd-whitelist")){
			$Main = explode(" ", substr($text, 1));
			if(!isset($Main[1])){
				$Main[1] = "";
			}
			$cmdCheck = $this->checkPermission($username, strtolower($Main[0]), strtolower($Main[1]), $this->getConfig()->get("notice"));
			if(!$cmdCheck){
				$player->sendMessage($this->getServer()->getLanguage()->translateString(TextFormat::RED."%commands.generic.permission"));
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

	public function changeName(Player $player){
		$username = $player->getName();
		$Permission = $this->getUserPermission($username);
		if(is_null($Permission)){
			$Permission = "ERROR";
		}
		$player->setNameTag("[".$Permission."] ".$username."");
		$player->setDisplayName("[".$Permission."] ".$username."");
	}

	public function changeName2(Player $player){
		$username = $player->getName();
		$player->setNameTag($username);
		$player->setDisplayName($username);
	}

}