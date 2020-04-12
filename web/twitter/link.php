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
	
$_SESSION["oauth_callback_action"] = "link";

// Step # 1

require('../lib/twitter/oauth/140dev/oauth_lib.php');
$connection = get_connection();
$connection->request(
	'POST', 
	$connection->url('https://api.twitter.com/oauth/request_token'), 
	array('oauth_callback' => 'https://www.twittelegram.com/twitter/oauth_callback.php', 'x_auth_access_type' => 'write')
);
parse_str($connection->response['response'], $response);

// Step # 2

if($response["oauth_callback_confirmed"] == "true") {
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Link authentication.";
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
	if($_SESSION["screen_name"] && $_SESSION["chat"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["linkedChats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Link authentication.";
		$j["linkedChats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
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
	header("Location: https://api.twitter.com/oauth/authorize?oauth_token=".$response["oauth_token"]);
	die();
}

?>
