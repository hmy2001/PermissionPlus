<?php

namespace PermissionPlus;

use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class CommandSystem{
        private static $api = null;

        public function __construct(){
                $this->DataFolder = Server::getInstance()->getPluginManager()->getPlugin("PermissionPlus")->getDataFolder();
        }

        public static function init(){
                if(is_null(self::$api)){
                        self::$api = new self;
                }
        }

        public static function API(){
                if (is_null(self::$api)) {
                        self::$api = new self;
                }
                return self::$api;
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	Config
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function CreateConfig(){
                $this->Command = new Config($this->DataFolder."Command.yml", CONFIG::YAML, array("subcmd" => array( "ADMIN" => array(), "TRUST" => array(), "GUEST" => array(), ),"command" => array("ADMIN" => array( 'ban' => true, 'ban-ip' => true, 'banlist' => true, 'defaultgamemode' => true, 'deop' => true, 'difficulty' => true, 'gamemode' => true, 'give' => true, 'help' => true, 'kick' => true, 'kill' => true, 'list' => true, 'me' => true, 'op' => true, 'pardon' => true, 'pardon-ip' => true, 'plugins' => true, 'reload' => true, 'save-all' => true, 'save-off' => true, 'save-on' => true, 'say' =>true, 'seed' => true, 'setworldspawn' => true, 'spawnpoint' => true, 'status' => true, 'stop' => true, 'tell' => true, 'time' => true, 'timings' => true, 'tp' => true, 'version' => true, 'whitelist' => true, 'ppplayer' => true, 'ppcommand' => true, 'ppconfig' => true),"TRUST" => array( 'ban' => false, 'ban-ip' => false, 'banlist' => false, 'defaultgamemode' => false, 'deop' => true, 'difficulty' => false, 'gamemode' => false, 'give' => true, 'help' => true, 'kick' => true, 'kill' => false, 'list' => true, 'me' => true, 'op' => true, 'pardon' => false, 'pardon-ip' => false, 'plugins' => true, 'reload' => true, 'save-all' => false, 'save-off' => false, 'save-on' => false, 'say' => true, 'seed' => true, 'setworldspawn' => true, 'spawnpoint' => true, 'status' => true, 'stop' => false, 'tell' => true, 'time' => true, 'timings' => true, 'tp' => true, 'version' => false, 'whitelist' => false, 'ppplayer' => false, 'ppcommand' => false, 'ppconfig' => false),"GUEST" => array( 'ban' => false, 'ban-ip' => false, 'banlist' => false, 'defaultgamemode' => false, 'deop' => false, 'difficulty' => false, 'gamemode' => false, 'give' => false, 'help' => false, 'kick' => false, 'kill' => false, 'list' => true, 'me' => false, 'op' => false, 'pardon' => false, 'pardon-ip' => false, 'plugins' => false, 'reload' => false, 'save-all' => false, 'save-off' => false, 'save-on' => false, 'say' => false, 'seed' => false, 'setworldspawn' => false, 'spawnpoint' => false, 'status' => false, 'stop' => false, 'tell' => false, 'time' => false, 'timings' => false, 'tp' => false, 'version' => false, 'whitelist' => false, 'ppplayer' => false, 'ppcommand' => false, 'ppconfig' => false))));
                $this->Command->save();
                return true;
        }

        public function FormatConfig(){
                $config = new Config($this->DataFolder."config.yml", CONFIG::YAML);
                if($config->get("version")){
                        $version = $config->get("version");
                        if(Main::VERSION > $version){
                                $config = $config->getAll();
                                foreach($config["subcmd"] as $per => $data){
                                        if(!isset($this->Command->get("subcmd")[$per])){
                                                $newcmd = array();
                                                foreach ($this->Command->get("subcmd")["ADMIN"] as $cmd => $subcmds){
                                                        $newcmd[$cmd] = array();
                                                        foreach (array_keys($subcmds) as $sub){
                                                                $newcmd[$cmd][$sub] = false;
                                                        }
                                                }
                                                $this->Command->set("subcmd", array_merge($this->Command->get("subcmd"), array($per => $newcmd)));
                                                unset($newcmd);
                                        }
                                        $newcmd = $this->Command->get('subcmd')[$per];
                                        foreach($data as $cmd => $subcmds){
                                                foreach($subcmds as $sub => $en){
                                                        $newcmd[$cmd][$sub] = $en;
                                                }
                                        }
                                        unset($this->Command->get('subcmd')[$per]);
                                        $this->Command->set("subcmd", array_merge($this->Command->get("subcmd"), array($per => $newcmd)));
                                }
                                foreach($config["command"] as $per => $data){
                                        if(!isset($this->Command->get("command")[$per])){
                                                $this->Command->set("command", array_merge($this->Command->get("command"), array($per => array_fill_keys($this->getCommands(),false))));
                                        }
                                        $newcmd = $this->Command->get('command')[$per];
                                        foreach($data as $cmd => $en){
                                                $newcmd[$cmd]= $en;
                                        }
                                        unset($this->Command->get('command')[$per]);
                                        $this->Command->set("command", array_merge($this->Command->get("command"), array($per => $newcmd)));
                                }
                                $this->Command->save();
                                return true;
                        }
                        return false;
                }
                return false;
        }






//死ぬぽよ



/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	データセーブ
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function saveData(){
                $this->Command->save();
        }

}