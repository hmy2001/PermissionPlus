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
		switch($lang){
			case "jpn":
			$lang = "ja";
			break;
		}
		if(file_exists($this->path.$lang.".ini") and strlen($content = file_get_contents($this->path.$lang.".ini")) > 0){
			if(isset($this->Text)){
				unset($this->Text);
			}
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
			$this->LangName = $lang;
			return true;
		}else{
			if(isset($this->Text)){
				$this->getLogger()->error($this->getText("languagefile.error"));
			}else{
				$this->getLogger()->error("Failed to read the language file...");
			}
			return false;
		}
	}

	public function getText($textname){
		if(isset($this->Text[$textname])){
			return $this->Text[$textname];
		}else{
			return "文字列が見つかりませんでした。";
		}
	}

	public function transactionText($textname, $transaction){
		if($text = $this->Text[$textname] !== "文字列が見つかりませんでした。"){
			//TODO
		}
		return "文字列が見つかりませんでした。";
	}

	public function getLogger(){
		return MainLogger::getLogger();
	}

}