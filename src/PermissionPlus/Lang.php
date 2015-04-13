<?php

namespace PermissionPlus;

use pocketmine\utils\MainLogger;

class Lang{
	protected $Text = [];

	public function __construct($path){
		$this->path = $path;
	}

	public function LoadLang($lang){
		$lang = strtolower($lang);
		if(isset($this->Text)){
			unset($this->Text);
		}
		if(file_exists($this->path.$lang.".ini") and strlen($content = file_get_contents($this->path.$lang.".ini")) > 0){
			foreach(explode("\n", $content) as $line){
				$line = trim($line);
				if($line === "" or $line{0} === "#"){
					continue;
				}
				$t = explode("=", $line);
				if(count($t) < 2){
					continue;
				}
				$key = trim(array_shift($t));
				$value = trim(implode("=", $t));
				if($value === ""){
					continue;
				}
				$this->Text[$key] = $value;
			}
			print_r($this->Text);
			return true;
		}else{
			$this->getLogger()->error("Failed to read the language file...");
			return false;
		}
	}

	public function getText($textname){
		if(isset($this->Text[$textname])){
			return $this->Text[$textname];
		}else{
			return null;
		}
	}

	public function getLogger(){
		return MainLogger::getLogger();
	}

}