<?php

namespace PermissionPlus;

use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class PermissionSystem{
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
                $this->Account = new Config($this->DataFolder."Account.yml", CONFIG::YAML);
                $this->Account->save();
                $this->Permission = new Config($this->DataFolder."Permission.yml", CONFIG::YAML, array("notice" => true, "autoop" => false, "PerName" => false, "cmd-whitelist" => true, "permission" => array( "GUEST" => true, "TRUST" => true, "ADMIN" => true)));
                $this->Permission->save();
                return true;
        }

        public function FormatConfig(){
                $config = new Config($dataFolder."config.yml", CONFIG::YAML);
                if($config->get("version")){
                        $version = $config->get("version");
                        if(Main::VERSION > $version){
                                $config = $config->getAll();
                                $this->Permission->set("notice", $config["notice"]);
                                $this->Permission->set("autoop", $config["autoop"]);
                                $this->Permission->set("cmd-whitelist", $config["cmd-whitelist"]);
                                foreach($config["permission"] as $per => $en){
                                        $this->Permission->set("permission", array_merge($this->Permission->get("permission"), array($per => $en)));
                                }
                                foreach($config["player"] as $player => $per){
                                        $this->Account->set($player, array("Permission" => $per));
                                }
                                $this->Account->save();
                                $this->Permission->save();
                                return true;
                        }
                        return false;
                }
                return false;
        }

        public function get($type){
                return $this->Permission->get($type);
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	アカウント
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function createAccount($username){
                if($this->Account->exists($username)){
                        return false;
                }else{
                        $this->Account->set($username, array("Permission" => "GUEST"));
                        return true;
                }
        }

        public function getUserPermission($username){
                if($this->Account->exists($username)){
                        return $this->Account->get($username)["Permission"];
                }else{
                        return false;
                }
        }

/*\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	     権限
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\*/

        public function set($type,$data){
                switch($type){
                case "notice":
                $this->Permission->set("notice", $data);
                break;
                case "autoop":
                $this->Permission->set("autoop", $data);
                break;
                case "PerName":
                $this->Permission->set("PerName", $data);
                break;
                case "cmdw":
                $this->Permission->set("cmd-whitelist", $data);
                break;
                }
                $this->Permission->save();
        }

        public function showPPermissionsList($sender){
                foreach($this->Account->getAll() as $username => $permission){
                        $player = $sender->getServer()->getPlayerExact($username);
                        if($player instanceof Player){
                                $online = "ONLINE";
                        }else{
                                $online = "OFFLINE";
                        }
                        switch($online){
                        case "OFFLINE":
                        $sender->sendMessage(TextFormat::DARK_BLUE."[".$online."]".TextFormat::GREEN."[".array_shift($permission)."]".TextFormat::WHITE.":  ".$username."");
                        break;
                        case "ONLINE":
                        $sender->sendMessage(TextFormat::DARK_RED."[".$online."]".TextFormat::GREEN."[".array_shift($permission)."]".TextFormat::WHITE.":   ".$username."");
                        break;
                        }
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
                }
                return $output;
        }

        public function getPermissions(){
                $prms = array_keys($this->Permission->get("permission"));
                return $prms;
        }

        public function setPPermission($player, $permission,$sender){
                if(!$this->castPermission($permission)){
                        $msg = $this->permissionUsage("p");
                        $sender->sendMessage("Usage: /ppplayer <player> $msg");
                        return;
                }
                if(!$this->castPermission($permission)){
                        $sender->sendMessage("[Permission+] Invalid value: \"$value\"");
                        continue;
                }
                $permission = $this->castPermission($permission);
                $this->Account->set($player, array("Permission" => $permission));
                $sender->sendMessage("[Permission+] Gived ".$permission." to ".$player.".");
                $player = $sender->getServer()->getPlayerExact($player);
                if($player instanceof Player){
                        $player->sendMessage("[PermissionPlus] Your permission has been changed into ".$permission." !");
                }
        }

        public function addPermission($permission){
                $permission = strtoupper($permission);
                $permissions = $this->getPermissions();
                if(!in_array($permission, array_merge(array("g", "t", "a"), $permissions))){
                        $this->Permission->set("permission", array_merge($this->Permission->get("permission"), array($permission => false)));
                        $this->Permission->save();
                        CommandSystem::API()->addPermission($permission);
                        return true;
                }
                return false;
        }

        public function removePermission($permission){
                $permission = strtoupper($permission);
                if(isset($this->Permission->get("permission")[$permission]) && !$this->Permission->get("permission")[$permission]){
                        unset($this->Permission->get("permission")[$permission]);
                        CommandSystem::API()->removePermission($permission);
                        $this->Permission->save();
                        return true;
                }
                return false;
        }

        public function castPermission($permission) {
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
                case "G"
                case "GUEST":
                $permission ="GUEST";
                return $permission;
                break;
                default:
                if(in_array($permission, $this->getPermissions())) {
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
                $this->Account->save();
                $this->Permission->save();
        }

}