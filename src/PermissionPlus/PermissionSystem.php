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
                $this->Permission = new Config($dataFolder."Permission.yml", CONFIG::YAML, array("notice" => true, "autoop" => false, "PerName" => false, "cmd-whitelist" => true, "permission" => array( "GUEST" => true, "TRUST" => true, "ADMIN" => true)));
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


//死ぬぽよ




}