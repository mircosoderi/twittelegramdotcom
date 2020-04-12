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
session_regenerate_id();

// login

define('BOT_USERNAME', '@twittelegramdotcom_bot'); // place username of your bot here
function getTelegramUserData() {
    if(isset($_GET["id"])) {		
		$auth_data_json = "{ \"id\": \"".$_GET["id"]."\", \"first_name\": \"".$_GET["first_name"]."\",  \"last_name\": \"".$_GET["last_name"]."\", \"photo_url\": \"".$_GET["photo_url"]."\" }";
		$_SESSION["telegram_auth_data"] = $auth_data_json;		
		$auth_data = json_decode($auth_data_json, true);		
		return $auth_data;
	}
	else if(isset($_SESSION["telegram_auth_data"])) {
		$auth_data = json_decode($_SESSION["telegram_auth_data"], true);
		return $auth_data;
	}
	else {
		return false;
	}
}

$tg_user = getTelegramUserData();
if($tg_user !== false) {

	// antihijacking

	if(!isset($_SESSION["antihijacking"])) {
		session_destroy();
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$tg_user['id'].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Antihijacking check failed at management page.";
		$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents($TELEGRAMUSERSPATH.$tg_user['id'].".json",json_encode($j, JSON_PRETTY_PRINT));
		header("Location: https://www.twittelegram.com/");
		die();
	}
	if(!isset($_SESSION["antihijacking"]["enforce"])) {
		$_SESSION["antihijacking"] = array_intersect_assoc($_SESSION["antihijacking"], $_SERVER);
		$_SESSION["antihijacking"]["enforce"] = true;
	}
	else {
		foreach ($_SESSION["antihijacking"] as $key => $value) {
			if($key != "enforce" && $_SERVER[$key] != $value) {
				session_destroy();
				$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$tg_user['id'].".json"),true);
				$microtime = "".microtime(true);
				$j["subscription"]["log"][$microtime]["text"] = "Antihijacking check failed at management page.";
				$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
				if($_SESSION["generic_browsing_cost"]) {
					$microtime = "".microtime(true);
					$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
					$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
					unset($_SESSION["generic_browsing_cost"]);
				}
				file_put_contents($TELEGRAMUSERSPATH.$tg_user['id'].".json",json_encode($j, JSON_PRETTY_PRINT));
				header("Location: https://www.twittelegram.com/");
				die();
			}
		}
		
	}

	// manage

	$id = $tg_user['id'];
	$first_name = $tg_user['first_name'];
	$last_name = $tg_user['last_name'];
	$photo_url = $tg_user['photo_url'];

	$datafile = [];
	if(file_exists("$TELEGRAMUSERSPATH$id.json")) {
		$datafile = json_decode(file_get_contents("$TELEGRAMUSERSPATH$id.json"),true);
		$datafile["lnx"]["usr"] = "***obfuscated***";
		$datafile["lnx"]["pwd"] = "***obfuscated***";
		foreach(array_keys($datafile["chats"]) as $chat) {
				foreach(array_keys($datafile["chats"][$chat]["links"]) as $link) {
					$datafile["chats"][$chat]["links"][$link]["oauth_token"] = "***obfuscated***";
					$datafile["chats"][$chat]["links"][$link]["oauth_token_secret"] = "***obfuscated***";
				}
		}
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary"); 
		header("Content-disposition: attachment; filename=\"".$id.".json\""); 
		echo(str_replace("\"cost\": ","\"weight\": ",json_encode($datafile,JSON_PRETTY_PRINT)));
		$j = json_decode(file_get_contents("$TELEGRAMUSERSPATH$id.json"),true);
		$microtime = "".microtime(true);
		$j["subscription"]["log"][$microtime]["text"] = "Download of integral data file";	
		$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		file_put_contents($TELEGRAMUSERSPATH.$tg_user['id'].".json",json_encode($j, JSON_PRETTY_PRINT));
		die();
	}
}
header("HTTP/1.1 404 Not Found");
die();