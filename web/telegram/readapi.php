<?php

/*
 * Copyright 2020 Mirco Soderi
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 *
 */

 require_once("../lib/const.php");
$start = microtime(true);
session_start();
if(!$_GET["tkn"] == $_SESSION["tkn"]) {
	if($_SESSION["user"]["id"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Invocation of configuration API for reading from Twitter lists.";
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
	header("HTTP/1.1 401 Unauthorized");
	die();
}
switch($_GET["op"]) {

	case "p":
		$search=trim($_GET["search"]);
		$offset=intval($_GET["offset"]);
		$files = array();
		if ($handle = opendir($TWITTERUSERSPATH)) {			
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
				   $files[filemtime("$TWITTERUSERSPATH$file")] = "$TWITTERUSERSPATH$file";
				}
			}
			closedir($handle);
			ksort($files);
			$out = [];
		
			foreach(array_keys($files) as $time) {				
			   $o = json_decode(file_get_contents($files[$time]),true);	
			   if($_GET["screen_name"] == $o["screen_name"]) {
				   header('Content-Type: application/json');
				   echo(json_encode(array( "name" => $o["name"], "screenName" => $o["screen_name"], "description" => empty($o["description"])?null:$o["description"])));
				   if($_SESSION["user"]["id"]) {
						$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
						$microtime = "".microtime(true);
						$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Successful API invocation while configuring a reading from a Twitter list.";
						$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
						file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
				   }
				   	else {
						if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
						$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
					}
				   die();
			   }
			   if(empty($search) || strpos($o["name"],$search) !== false || strpos($o["screen_name"],$search) !== false || strpos($o["description"],$search) !== false )  {				   
				   $out[] = array( "name" => $o["name"], "screenName" => $o["screen_name"], "description" => empty($o["description"])?null:$o["description"]);				   				   
			   }
			   if(count($out) == $offset+max(array(100,2*$offset))) {
					$out = array_slice($out,$offset);
					break;
			   }
			}
			header('Content-Type: application/json');
			echo(json_encode($out));
		    if($_SESSION["user"]["id"]) {
				$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
				$microtime = "".microtime(true);
				$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Successful API invocation while configuring a reading from a Twitter list.";
				$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
				if($_SESSION["generic_browsing_cost"]) {
					$microtime = "".microtime(true);
					$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
					$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
					unset($_SESSION["generic_browsing_cost"]);
				}
				file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
		    }
			else {
				if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
				$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
			}
			die();
		}
		else {
			if($_SESSION["user"]["id"]) {
				$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
				$microtime = "".microtime(true);
				$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "API invocation resulted in error while configuring a reading from a list";
				$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
				if($_SESSION["generic_browsing_cost"]) {
					$microtime = "".microtime(true);
					$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
					$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
					unset($_SESSION["generic_browsing_cost"]);
				}				
				file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
			}
			else {
				if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
				$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
			}
			header("HTTP/1.1 500 Internal Server Error");
			die();
		}
		break;
	case "l":
		$o = json_decode(file_get_contents($TWITTERUSERSPATH.$_GET["screen_name"].".json"),true);
		$o["lists"]["0"]["description"] = "The timeline of ".$_GET["screen_name"];		
		header('Content-Type: application/json');
		echo(json_encode($o["lists"]));
		if($_SESSION["user"]["id"]) {
			$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
			$microtime = "".microtime(true);
			$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Successful API invocation while configuring a reading from a Twitter list.";
			$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
			if($_SESSION["generic_browsing_cost"]) {
				$microtime = "".microtime(true);
				$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
				$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
				unset($_SESSION["generic_browsing_cost"]);
			}
			file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
		}
		else {
			if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
			$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
		}
		die();
		
}

?>