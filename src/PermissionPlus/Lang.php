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
			if(isset($this->Text["languagefile.error"])){
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
		}elseif(isset($this->Text[$textname]["text.error"])){
			return $this->Text["text.error"];
		}else{
			return "Failed to read the text...";
		}
	}

	public function transactionText($textname, $transaction){
		$text = $this->getText($textname);
		if($text !== $this->Text["text.error"]){
			if(is_array($transaction)){
				for($i = 0; $i < count($transaction); $i++){
					$text = str_replace("{%$i}", $transaction[$i], $text);
				}
			}
			return $text;
		}
		return $this->Text["text.error"];
	}

	public function getLogger(){
		return MainLogger::getLogger();
	}

}