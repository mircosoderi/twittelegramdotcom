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
 
$start = microtime(true);

$chat = $argv[1];
$tuser = $argv[2];
$list = $argv[3];

require('lib/twitter/oauth/140dev/oauth_lib.php');

function httpPost($method, $data)
{
    $curl = curl_init("https://api.telegram.org/yourbot/$method");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

$user = substr($list,20);
$e = strpos($user,"/")>0?strpos($user,"/"):strlen($user);
$user = substr($user,0,$e);

$srcData = json_decode(file_get_contents("/home/ttusr$tuser/path/to/twitter/$user.json"),true);
if(!$srcData) die();
if(!$srcData["subscription"]["active"]) die();

foreach(array_keys($srcData["lists"]) as $listID) {
	if($srcData["lists"][$listID]["uri"] == substr($list,19)) {
		if($srcData["lists"][$listID]["access"] == "granted") {
			$authorized = false;
			$whocanread = $srcData["lists"][$listID]["whocanread"]?$srcData["lists"][$listID]["whocanread"]:"everybody";
			switch($whocanread) {
				case "whitelisted":
					$whitelist = $srcData["lists"][$listID]["wblisted"];					
					$whitelisted = [];
					foreach(array_keys($whitelist) as $chatID) {
						if($whitelist[$chatID] == "on") {
							$whitelisted[] = $chatID;
						}
					}
					$requesters = array_keys($srcData["lists"][$listID]["requesters"]);
					if(in_array($chat,$requesters) && in_array($chat,$whitelisted)) {
						$authorized = true;
					}
					break;
				case "nonblacklisted":
					$blacklist = [];
					if(array_key_exists("wblisted",$srcData["lists"][$listID])) {
						$blacklist = $srcData["lists"][$listID]["wblisted"];
					}
					$blacklisted = [];
					foreach(array_keys($blacklist) as $chatID) {
						if($blacklist[$chatID] == "on") {
							$blacklisted[] = $chatID;
						}
					}
					$requesters = array_keys($srcData["lists"][$listID]["requesters"]);
					if(in_array($chat,$requesters) && !in_array($chat,$blacklisted)) {
						$authorized = true;
					}
					break;
				case "everybody":
					$authorized = true;
					break;
				default:
					die();
			}
			if($authorized) {			
				$tData = json_decode(file_get_contents("/home/ttusr$tuser/path/to/telegram/$tuser.json"),true);
				if(!$tData) die();
				if(!$tData["subscription"]["active"]) die();
				if(!$tData["chats"][$chat]["active"]) die();
				$last = false;
				if(array_key_exists("last",$tData["chats"][$chat]["readings"][$list])) $last = $tData["chats"][$chat]["readings"][$list]["last"];
				$params = array();
				if($last) $params["since_id"] = $last;				
				$connection = get_auth_connection($srcData["oauth_token"], $srcData["oauth_token_secret"], $tuser);
				if(!$connection) die();
				if($listID == "0") {
					 $connection->request(
						'GET', 
						$connection->url('https://api.twitter.com/1.1/statuses/home_timeline.json'), 
						$params
					);
				}
				else {
					$params["list_id"] = $listID;
					$connection->request(
							'GET',
							$connection->url('https://api.twitter.com/1.1/lists/statuses.json'),
							$params
					);
				}			
				$response = json_decode($connection->response['response'],true);
				
				if(array_key_exists("errors",$response)) {
					die();
				}
				$done = false;
				$i = 0;
				while(!($done || $i == count($response))) {
					$tweet = $response[$i];
					$protected = false;
					$users = [];
					$users[] = $tweet["user"]["id"];
					if(array_key_exists("retweeted_status",$tweet)) $users[] = $tweet["retweeted_status"]["user"]["id"];					
					foreach($users as $userID) {
						$pconnection = get_auth_connection($srcData["oauth_token"], $srcData["oauth_token_secret"], $tuser);
						if(!$pconnection) die();
						$pconnection->request(
							'GET', 
							$pconnection->url('https://api.twitter.com/1.1/users/show.json'), 
							array("user_id" => $userID)
						);						
						$presponse = json_decode($pconnection->response['response'],true);	
						if(array_key_exists("errors",$presponse)) {
                            die();
                        }
						if($presponse["protected"]) $protected = true;
					}
					if(!$protected) {
						$tData["chats"][$chat]["readings"][$list]["last"] = $tweet["id"];												
						httpPost("sendMessage",array("chat_id" => $chat, "text" => "https://twitter.com/".$tweet["user"]["screen_name"]."/status/".$tweet["id_str"]));
						if((!$srcData["lists"][$listID]["whopays"]) || $srcData["lists"][$listID]["whopays"] == "consumer") {
							$microtime = "".microtime(true);
							$tData["chats"][$chat]["log"][$microtime]["text"] = "Received tweet ".($tweet["entities"]["urls"][0]["url"]?$tweet["entities"]["urls"][0]["url"]:$tweet["retweeted_status"]["entities"]["urls"][0]["url"]);
							$tData["chats"][$chat]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
						}
						else {							
							$microtime = "".microtime(true);
							$tData["chats"][$chat]["log"][$microtime]["text"] = "Received tweet ".($tweet["entities"]["urls"][0]["url"]?$tweet["entities"]["urls"][0]["url"]:$tweet["retweeted_status"]["entities"]["urls"][0]["url"]);
							$tData["chats"][$chat]["log"][$microtime]["cost"] = 0;
						}
						file_put_contents("/home/ttusr$tuser/path/to/telegram/$tuser.json",json_encode($tData));
						
						
						if($srcData["lists"][$listID]["whopays"] == "producer") {
							$microtime = "".microtime(true);
							$srcData["lists"][$listID]["log"][$microtime]["text"] = "Delivered tweet ".($tweet["entities"]["urls"][0]["url"]?$tweet["entities"]["urls"][0]["url"]:$tweet["retweeted_status"]["entities"]["urls"][0]["url"])." to chat # $chat";
							$srcData["lists"][$listID]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
						}
						else {
							$microtime = "".microtime(true);
							$srcData["lists"][$listID]["log"][$microtime]["text"] = "Delivered tweet ".($tweet["entities"]["urls"][0]["url"]?$tweet["entities"]["urls"][0]["url"]:$tweet["retweeted_status"]["entities"]["urls"][0]["url"])." to chat # $chat";
							$srcData["lists"][$listID]["log"][$microtime]["cost"] = 0;
						}
						file_put_contents("/home/ttusr$tuser/ttenv/users/twitter/$user.json",json_encode($srcData));
						
						$done = true;
					}					
					$i++;
				}
 			}
			else {
				die();
			}

		}
	}
}
?>
