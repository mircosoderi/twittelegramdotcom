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
if(!isset($_SESSION["antihijacking"])) {
	session_destroy();
	header("Location: https://www.twittelegram.com/");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Antihijacking check failed at the callback of the Web authentication.";
		$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($j, JSON_PRETTY_PRINT));
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
	die();
}
$_SESSION["antihijacking"] = array_intersect_assoc($_SESSION["antihijacking"], $_SERVER);
require('../lib/twitter/oauth/140dev/oauth_lib.php');

if($_SESSION["oauth_callback_action"] == "login") {
	
	$oauth_token = filter_input(INPUT_GET, 'oauth_token', FILTER_SANITIZE_URL); 
	$oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier', FILTER_SANITIZE_URL); 
	$securefile = fopen($APIKEYSFILEPATH,"r");
	$securejson = fread($securefile, filesize($APIKEYSFILEPATH));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);
	$consumer_key = $securekeys["apiKey"];
	$connection = get_connection();
	$connection->request(
		'POST', 
		$connection->url('https://api.twitter.com/oauth/access_token'), 
		array(
			'oauth_consumer_key' => $consumer_key, 
			'oauth_token' => $oauth_token,
			'oauth_verifier' => $oauth_verifier
		)
	);
	parse_str($connection->response['response'], $response);
	
	$_SESSION["screen_name"] = $response["screen_name"];
	
	$datafile = [];
	if(file_exists($TWITTERUSERSPATH.$response["screen_name"].".json")) {
		$datafile = json_decode(file_get_contents($TWITTERUSERSPATH.$response["screen_name"].".json"),true);
	}
	$datafile["oauth_token"] = $response["oauth_token"];
	$datafile["oauth_token_secret"] = $response["oauth_token_secret"];
	$datafile["screen_name"] = $response["screen_name"];
	
	$datafile["lists"][0]["uri"] = "/".$datafile["screen_name"];
	$datafile["lists"][0]["name"] = "Timeline";
	$datafile["lists"][0]["description"] = "";
	if(!isset($datafile["lists"][0]["access"])) {
		$datafile["lists"][0]["access"] = "denied";
	}
	
	try {
		$connection = get_auth_connection($response["oauth_token"], $response["oauth_token_secret"]);
		$connection->request(
			'GET', 
			$connection->url('https://api.twitter.com/1.1/lists/list.json'), 
			array(
				'screen_name' => $response["screen_name"]
			)
		);		
		$lists = json_decode($connection->response['response'],true);		
		foreach($lists as $list) {		
			$datafile["lists"][$list["id_str"]]["uri"] = $list["uri"];
			$datafile["lists"][$list["id_str"]]["name"] = $list["name"];
			$datafile["lists"][$list["id_str"]]["description"] = $list["description"];		
			if(!isset($datafile["lists"][$list["id_str"]]["access"])) {
				$datafile["lists"][$list["id_str"]]["access"] = "denied";
			}
			$datafile["name"] = $list["user"]["name"];
			$datafile["description"] = $list["user"]["description"];		
		}	
	}
	catch(Exception $e) {
		$_SESSION["action"] = "Lists Error";
		$_SESSION["action_comment"] = "It was not possible to retrieve up-to-date information about your Twitter lists. Try not to login for a while. Too many frequent login attempts could be one reason indeed. If the problem is not solved, contact Mirco Soderi at <a href=\"mailto:mirco.soderi@gmail.com\" title=\"mirco.soderi@gmail.com\">mirco.soderi@gmail.com</a>.";
		$_SESSION["action_status"] = "KO";
		$datafile["errors"][time()] = "Lists error";
	}
	
	file_put_contents($TWITTERUSERSPATH.$response["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/twitter/manage.php");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Successful Web authentication.";
		$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}		
		file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($j, JSON_PRETTY_PRINT));
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
	die();

}

if($_SESSION["oauth_callback_action"] == "link") {
	$oauth_token = filter_input(INPUT_GET, 'oauth_token', FILTER_SANITIZE_URL); 
	$oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier', FILTER_SANITIZE_URL); 
	$securefile = fopen($APIKEYSFILEPATH,"r");
	$securejson = fread($securefile, filesize($APIKEYSFILEPATH));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);
	$consumer_key = $securekeys["apiKey"];
	$connection = get_connection();
	$connection->request(
		'POST', 
		$connection->url('https://api.twitter.com/oauth/access_token'), 
		array(
			'oauth_consumer_key' => $consumer_key, 
			'oauth_token' => $oauth_token,
			'oauth_verifier' => $oauth_verifier
		)
	);
	parse_str($connection->response['response'], $response);
	
	$twitterAccount = $response["screen_name"];
	$telegramAccount = $_SESSION["user"]["id"];
	$telegramChat = $_SESSION["chat"];
	if(!file_exists("$TWITTERUSERSPATH$twitterAccount.json")) {
		$_SESSION["link_status"] = "Failed";		
	}
	else {	
		$telegramAccountData = json_decode(file_get_contents("$TELEGRAMUSERSPATH$telegramAccount.json"),true);		
		$twitterAccountData = json_decode(file_get_contents("$TWITTERUSERSPATH$twitterAccount.json"),true);
		if($twitterAccountData["subscription"]["active"]) {
			if(!in_array($twitterAccount,array_keys($telegramAccountData["chats"][$telegramChat]["links"]))) {
				$telegramAccountData["chats"][$telegramChat]["links"][$twitterAccount]["since"] = time();
				$telegramAccountData["chats"][$telegramChat]["links"][$twitterAccount]["oauth_token"] = $response["oauth_token"];
				$telegramAccountData["chats"][$telegramChat]["links"][$twitterAccount]["oauth_token_secret"] = $response["oauth_token_secret"];
				$twitterAccountData["linkedChats"][$telegramChat]["telegramUser"][] = $telegramAccountData["id"];
				$twitterAccountData["linkedChats"][$telegramChat]["activeLink"] = true;
				file_put_contents("$TWITTERUSERSPATH$twitterAccount.json",json_encode($twitterAccountData, JSON_PRETTY_PRINT));
				file_put_contents("$TELEGRAMUSERSPATH$telegramAccount.json",json_encode($telegramAccountData, JSON_PRETTY_PRINT));
				$_SESSION["link_status"] = "OK";
				$_SESSION["linkedTelegram"] = $telegramAccountData["chats"][$telegramChat]["title"];
				$_SESSION["linkedTelegramID"] = $telegramChat;
				$_SESSION["linkedTelegramUserID"] = $telegramAccount;
				$_SESSION["linkedTwitter"] = $twitterAccount;
			}
			else {
				$_SESSION["linkedTelegram"] = $telegramAccountData["chats"][$telegramChat]["title"];
				$_SESSION["linkedTelegramID"] = $telegramChat;
				$_SESSION["linkedTelegramUserID"] = $telegramAccount;
				$_SESSION["linkedTwitter"] = $twitterAccount;
				$_SESSION["link_status"] = "Already";	
			}
		}
		else {
			$_SESSION["link_status"] = "Failed";	
			$_SESSION["linkedTelegram"] = $telegramAccountData["chats"][$telegramChat]["title"];
			$_SESSION["linkedTelegramID"] = $telegramChat;
			$_SESSION["linkedTelegramUserID"] = $telegramAccount;
			$_SESSION["linkedTwitter"] = $twitterAccount;			
		}
	}
	header("Location: https://www.twittelegram.com/telegram/linked.php");
	if($_SESSION["linkedTelegramUserID"] && $_SESSION["linkedTelegramID"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["linkedTelegramUserID"].".json"),true);
		$j["chats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["text"] = "Linking chat ".$_SESSION["linkedTelegram"]." with Twitter profile ".$_SESSION["linkedTwitter"];
		$j["chats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
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
	if($_SESSION["linkedTwitter"] && $_SESSION["linkedTelegramID"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["linkedTwitter"].".json"),true);
		$j["linkedChats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["text"] = "Linking chat ".$_SESSION["linkedTelegram"]." with Twitter profile ".$_SESSION["linkedTwitter"];
		$j["linkedChats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents($TWITTERUSERSPATH.$_SESSION["linkedTwitter"].".json",json_encode($j, JSON_PRETTY_PRINT));		
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
	die();
	
}

if($_SESSION["oauth_callback_action"] == "unlink") {
	$oauth_token = filter_input(INPUT_GET, 'oauth_token', FILTER_SANITIZE_URL); 
	$oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier', FILTER_SANITIZE_URL); 
	$securefile = fopen($APIKEYSFILEPATH,"r");
	$securejson = fread($securefile, filesize($APIKEYSFILEPATH));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);
	$consumer_key = $securekeys["apiKey"];
	$connection = get_connection();
	$connection->request(
		'POST', 
		$connection->url('https://api.twitter.com/oauth/access_token'), 
		array(
			'oauth_consumer_key' => $consumer_key, 
			'oauth_token' => $oauth_token,
			'oauth_verifier' => $oauth_verifier
		)
	);
	parse_str($connection->response['response'], $response);
	
	$twitterAccount = $response["screen_name"];
	$telegramAccount = $_SESSION["user"]["id"];
	$telegramChat = $_SESSION["chat"];
	if(!file_exists("$TWITTERUSERSPATH$twitterAccount.json")) {
		$telegramAccountData = json_decode(file_get_contents("$TELEGRAMUSERSPATH$telegramAccount.json"),true);
		$_SESSION["linkedTelegram"] = $telegramAccountData["chats"][$telegramChat]["title"];
		$_SESSION["linkedTwitter"] = $twitterAccount;
		$_SESSION["link_status"] = "Already";		
		$_SESSION["linkedTelegramID"] = $telegramChat;
		$_SESSION["linkedTelegramUserID"] = $telegramAccount;
	}
	else {	
		$telegramAccountData = json_decode(file_get_contents("$TELEGRAMUSERSPATH$telegramAccount.json"),true);		
		$twitterAccountData = json_decode(file_get_contents("$TWITTERUSERSPATH$twitterAccount.json"),true);
		if(in_array($twitterAccount,array_keys($telegramAccountData["chats"][$telegramChat]["links"]))) {			
			unset($telegramAccountData["chats"][$telegramChat]["links"][$twitterAccount]);
			$twitterAccountData["linkedChats"][$telegramChat]["activeLink"] = false;
			file_put_contents("$TWITTERUSERSPATH$twitterAccount.json",json_encode($twitterAccountData, JSON_PRETTY_PRINT));
			file_put_contents("$TELEGRAMUSERSPATH$telegramAccount.json",json_encode($telegramAccountData, JSON_PRETTY_PRINT));
			$_SESSION["linkedTelegram"] = $telegramAccountData["chats"][$telegramChat]["title"];
			$_SESSION["linkedTwitter"] = $twitterAccount;
			$_SESSION["link_status"] = "OK";
			$_SESSION["linkedTelegramID"] = $telegramChat;
			$_SESSION["linkedTelegramUserID"] = $telegramAccount;
		}
		else {
			$_SESSION["linkedTelegram"] = $telegramAccountData["chats"][$telegramChat]["title"];
			$_SESSION["linkedTwitter"] = $twitterAccount;
			$_SESSION["link_status"] = "Already";	
			$_SESSION["linkedTelegramID"] = $telegramChat;
			$_SESSION["linkedTelegramUserID"] = $telegramAccount;
		}
	}
	header("Location: https://www.twittelegram.com/telegram/unlinked.php");
	if($_SESSION["linkedTelegramUserID"] && $_SESSION["linkedTelegramID"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["linkedTelegramUserID"].".json"),true);
		$j["chats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["text"] = "Unlinking chat ".$_SESSION["linkedTelegram"]." from Twitter profile ".$_SESSION["linkedTwitter"];
		$j["chats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
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
	if($_SESSION["linkedTwitter"] && $_SESSION["linkedTelegramID"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["linkedTwitter"].".json"),true);
		$j["linkedChats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["text"] = "Unlinking chat ".$_SESSION["linkedTelegram"]." from Twitter profile ".$_SESSION["linkedTwitter"];
		$j["linkedChats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}		
		file_put_contents($TWITTERUSERSPATH.$_SESSION["linkedTwitter"].".json",json_encode($j, JSON_PRETTY_PRINT));		
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
	die();
	
}
?>