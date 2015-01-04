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
                                                foreach($this->Command->get("subcmd")["ADMIN"] as $cmd => $subcmds){
                                                        $newcmd[$cmd] = array();
                                                        foreach(array_keys($subcmds) as $sub){
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
                                @unlink($this->getDataFolder()."config.yml");
                                return true;
                        }
                        return false;
                }else{
                        return false;
                }
        }

        public function get($type){
                return $this->Command->get($type);
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	Command
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function showCPermissionsList($sender){
                $output ="";
                $permission =array();
                $clist = $this->Command->get('command');
                foreach(PermissionSystem::API()->getPermissions() as $prm){
                        $pname = substr($prm, 0, 5);
                        foreach($clist[$prm] as $command => $enable){
                                if($enable){
                                        if(PermissionSystem::API()->get("permission")[$prm]){
                                                $permission[$prm][$command] ="[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]";
                                        }else{
                                                $space =str_repeat(" ", 6-strlen($pname)-1);
                                                $permission[$prm][$command] ="[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]" .$space;
                                        }
                                }else{
                                        $permission[$prm][$command] ="       ";
                                }
                        }
                }
                $line = "|";
                foreach ($this->getCommands() as $command) {
                        foreach (PermissionSystem::API()->getPermissions() as $prm) {
                                $output .= "$line".$permission[$prm][$command];
                        }
                        $output .= " :  /".$command."\n";
                }
                $sender->sendMessage($output);
        }

        public function getCommands() {
                $cmds =array_keys($this->Command->get('command')['ADMIN']);
                return $cmds;
        }

        public function setCPermission($command,$permissions,$sender){
                $msg ="";
                $return = array_fill_keys(PermissionSystem::API()->getPermissions(), false);
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
                        foreach(PermissionSystem::API()->getPermissions() as $permission){
                                $return[$permission] = false;
                        }
                }
                foreach(PermissionSystem::API()->getPermissions() as $permission){
                        $newcmd = $this->Command->get('command')[$permission];
                        $newcmd[$command] = $return[$permission];
                        unset($this->Command->get('command')[$permission]);
                        $this->Command->set("command", array_merge($this->Command->get("command"), array($permission => $newcmd)));
                        $this->Command->save();
                }
                if(empty($msg)){
                        $sender->sendMessage("[Permission+] \"/".$command."\" was disabled.");
                }else{
                        $sender->sendMessage("[Permission+] Assigned ".$msg."to \"/".$command."\".");
                }
        }

        public function showSPermissionsList($sender) {
                $output = "";
                $permission = array();
                $clist = $this->Command->get('subcmd');
                foreach(PermissionSystem::API()->getPermissions() as $prm){
                        $pname =substr($prm, 0, 5);
                        foreach($clist[$prm] as $command => $subcmds){
                                foreach($subcmds as $sub => $enable){
                                        if($enable){
                                                if($this->Command->get("permission")[$prm]){
                                                        $permission[$prm][$command."_".$sub] ="[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]";
                                                }else{
                                                        $space =str_repeat(" ", 6-strlen($pname)-1);
                                                        $permission[$prm][$command."_".$sub] ="[".TextFormat::GREEN."".$pname."".TextFormat::WHITE."]" .$space;
                                                }
                                        }else{
                                                $permission[$prm][$command."_".$sub] ="       ";
                                        }
                                }
                        }
                }
                foreach($this->Command->get("subcmd")["ADMIN"] as $command => $subcmds){
                        foreach(array_keys($subcmds) as $sub){
                        $line = "|";
                                foreach(PermissionSystem::API()->getPermissions() as $prm){
                                        $output .= "$line".$permission[$prm][$command."_".$sub];
                                }
                                $sender->sendMessage("".$output.":  /".$command." ".$sub."");
                                $output ="";
                        }
                }
        }

        public function setSPermission($cmd, $sub, $permissions, $player) {
                $msg ="";
                $return = array_fill_keys(PermissionSystem::API()->getPermissions(), false);
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
                        foreach(PermissionSystem::API()->getPermissions() as $permission){
                                $return[$permission] = false;
                        }
                }
                foreach(PermissionSystem::API()->getPermissions() as $permission){
                        $newcmd = $this->Command->get('subcmd')[$permission];
                        $newcmd[$cmd][$sub] = $return[$permission];
                        unset($this->Command->get('subcmd')[$permission]);
                        $this->Command->set("subcmd", array_merge($this->Command->get("subcmd"), array($permission => $newcmd)));
                        $this->Command->save();
                }
                if(empty($msg)){
                        $player->sendMessage("[Permission+] \"/".$cmd." ".$sub."\" was disabled.");
                }else{
                        $player->sendMessage("[Permission+] Assigned ".$msg."to \"/".$cmd." ".$sub."\".");
                }
        }

        public function addPermission($permission){
                $this->Command->set("command", array_merge($this->Command->get("command"), array($permission => array_fill_keys($this->getCommands(),false))));
                $this->Command->set("subcmd", array_merge($this->Command->get("subcmd"), array($permission => array())));
                foreach($this->Command->get("subcmd")["ADMIN"] as $cmd => $subcmds){
                        $this->Command->set("subcmd")[$permission][$cmd] = array();
                        foreach(array_keys($subcmds) as $sub){
                                $this->Command->set("subcmd")[$permission][$cmd][$sub] = false;
                        }
                }
                $this->Command->save();
        }

        public function removePermission($permission){
                $cper = $this->Command->get("command");
                $sper = $this->Command->get("subcmd");
                unset($cper[$permission]);
                unset($sper[$permission]);
                $this->Command->set("command",$cper);
                $this->Command->set("subcmd",$sper);
                $this->Command->save();
        }

        public function checkPermission($player,$cmd,$sub,$notice,$usage,$aliasdata){
                $permission = PermissionSystem::API()->getUserPermission($player);
                if($notice and !isset($this->Command->get('command')['ADMIN'][$cmd])){
                        $usage->info("NOTICE: \"/".$cmd."\" permission is not setted!");
                        $usage->info("Usage: /ppcommand ".$cmd." (g) (t) (a)");
                }
                if(!empty($sub)){
                        if(isset($this->Command->get('subcmd')[$permission][$cmd][$sub]) && !$this->Command->get('subcmd')[$permission][$cmd][$sub]){
                                return false;
                        }
                }
                foreach($aliasdata as $data){
                        if(!$data[1] and $data[0] === $cmd){
                                return false;
                        }
                }
                return true;
        }

        public function castPermission($permission){
                $permission = strtoupper($permission);
                switch($permission){
                case "A":
                case "ADMIN":
                $permission ="ADMIN";
                return $permission;
                break;
                case "T":
                case "TRUST":
                $permission ="TRUST";
                return $permission;
                break;
                case "G":
                case "GUEST":
                $permission ="GUEST";
                return $permission;
                break;
                default:
                if(in_array($permission, PermissionSystem::API()->getPermissions())){
                        return $permission;
                }
                return false;
                }
                return true;
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	データセーブ
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function saveData(){
                $this->Command->save();
        }

}