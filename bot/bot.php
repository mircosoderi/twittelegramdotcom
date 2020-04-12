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

require_once("../lib/ssh.php");
require_once('../lib/twitter/oauth/140dev/oauth_lib.php');

$TELEGRAMUSERSPATH = "your/path/to/telegram/users/folder/";
$TWITTERUSERSPATH = "your/path/to/twitter/users/folder/";
$INSPECTFILE = "your/inspect/file.json";
$SHAREDFILE = "your/shared/file.json";
$WEBHOOKSFOLDER = "your/webhooks/folder";

$TXT1 = "OK, do you wish to put any text in your %s?";
$TXT2 = "OK, now please send me the text, or /skip if you have changed your mind.";
$TXT3 = "OK, how many photos and videos?";
$TXT4 = "OK, now please send me them one by one.";
$TXT5 = "No text, no photo, no video, it seems that you have missed something :-)";
$TXT6 = "OK, your %s is ready. Fire?";

$responseTextForLogging = null;

function wrtLog($message,$responseTextForLogging,$start) {
	$j = json_decode(file_get_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].$message["callback_query"]["from"]["id"].".json"),true);
	$microtime = "".microtime(true);
	$j["chats"][$message["message"]["chat"]["id"].$message["callback_query"]["message"]["chat"]["id"]]["log"][$microtime]["upd"] = $message["update_id"];
	$j["chats"][$message["message"]["chat"]["id"].$message["callback_query"]["message"]["chat"]["id"]]["log"][$microtime]["ref"] = $message["message"]["reply_to_message"]["text"].$message["callback_query"]["message"]["text"]?$message["message"]["reply_to_message"]["text"].$message["callback_query"]["message"]["text"]:null;
	$j["chats"][$message["message"]["chat"]["id"].$message["callback_query"]["message"]["chat"]["id"]]["log"][$microtime]["txt"] = $message["message"]["text"].$message["callback_query"]["data"];
	$j["chats"][$message["message"]["chat"]["id"].$message["callback_query"]["message"]["chat"]["id"]]["log"][$microtime]["att"] = $message["message"]["media_group_id"]?"medias":($message["message"]["photo"]?"photo":($message["message"]["video"]?"video":($message["message"]["document"]?"document":null)));
	$j["chats"][$message["message"]["chat"]["id"].$message["callback_query"]["message"]["chat"]["id"]]["log"][$microtime]["rpl"] = $responseTextForLogging;
	$j["chats"][$message["message"]["chat"]["id"].$message["callback_query"]["message"]["chat"]["id"]]["log"][$microtime]["cst"] = abs(microtime(true) - $start);
	file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].$message["callback_query"]["from"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
}

function httpPost($method, $data)
{
    
	global $responseTextForLogging;
	$responseTextForLogging = $data["text"];
	if(array_key_exists("reply_markup",$data)) {
		$reply_markup = json_decode($data["reply_markup"],true);
		if(array_key_exists("inline_keyboard",$reply_markup)) {
			$inline_keyboard = $reply_markup["inline_keyboard"];
			foreach($inline_keyboard as $row) {
				foreach($row as $col) {
					$responseTextForLogging.=" [".$col["text"]."] ";
				}
			}
		}
	}
	$curl = curl_init("https://api.telegram.org/yourbot/$method");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function getFileUrl($file_id) {
	$curl = curl_init("https://api.telegram.org/yourbot/getFile?file_id=$file_id");
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
	$file_path = json_decode($response,true)["result"]["file_path"];
    return "https://api.telegram.org/file/yourbot/$file_path";
}

function tweet($user, $status, $documentMeta)
{
	$document = null;
	if($documentMeta) $document = httpGetFile($documentMeta["file_id"]);
	$twitterUser = json_decode(file_get_contents("$TWITTERUSERSPATH$user.json"),true);
	$connection = get_auth_connection($twitterUser["oauth_token"], $twitterUser["oauth_token_secret"]);	
	$connection->request(
		'POST', 
		$connection->url('https://api.twitter.com/1.1/statuses/update.json'), 
		array( "status" => $status, "media_ids" => "1212495953724420096" )
	);
	file_put_contents($INSPECTFILE,$connection->response["response"]);
	return $connection->response["response"];
}

function undoTweet()
{
	global $message;
	$telegramUser = json_decode(file_get_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json"),true);
	$oauthToken = $telegramUser["chats"][$message["callback_query"]["message"]["chat"]["id"]]["links"][explode(" ",$message["callback_query"]["data"])[2]]["oauth_token"];
	$oauthTokenSecret = $telegramUser["chats"][$message["callback_query"]["message"]["chat"]["id"]]["links"][explode(" ",$message["callback_query"]["data"])[2]]["oauth_token_secret"];
	$connection = get_auth_connection($oauthToken, $oauthTokenSecret);	
	$connection->request(
		'POST', 
		$connection->url('https://api.twitter.com/1.1/statuses/destroy/'.explode(" ",$message["callback_query"]["data"])[3].'.json'),
		array()
	);
	return $connection->response["response"];
}

function isTimezone($v) {
	$isTimezone = false;
	$tzgs = json_decode(file_get_contents("../telegram/timezones.json"),true); $tzs = []; foreach($tzgs as $tzg) $tzs = array_merge($tzs,$tzg["zones"]);
	foreach($tzs as $tz) if($v == $tz["value"]) $isTimezone = true;
	return $isTimezone;	
}

function tweetWizardStep1($userData, $chatID) {
	global $message; global $responseTextForLogging; global $start;
	if(!$userData["chats"][$chatID]["active"]) {
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Bot is stopped, /start it now!"));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	$links = array_keys($userData["chats"][$chatID]["links"]);
	if($links) {
		$shared = json_decode(file_get_contents($SHAREDFILE),true);
		if(count($shared["tweets"]) == 300 && $shared["tweets"][0]["time"] > time() - 60*60*3) {
			httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Twitter regulations allow each app to publish a maximum of 300 statuses every 3 hours, and we have reached that limit. You can publish your status now through the Twitter app or Website, or wait some time and try again with Twittelegram, or ask a quotation to have a dedicated instance of the Twittelegram service."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}			
		$vbuttons = [];
		foreach($links as $link) { $buttons = []; $buttons[] = array( "text" => $link, "callback_data" => $link ); $vbuttons[] = $buttons; }
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Tweet as...", "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));
		$userData["chats"][$chatID]["wizard"] = array("id" => uniqid(), "context" => "tweet", "chatID" => $chatID, "status" => "draft");
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		wrtLog($message,$responseTextForLogging,$start); die();	
	}
	else {
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Please /link to a Twitter account first."));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
}

function tweetWizardStep2($userData, $chatID, $txt) {
	$vbuttons = [];
	$buttons = [];
	$buttons[] = array( "text" => "0", "callback_data" => "0" ); 
	$buttons[] = array( "text" => "1", "callback_data" => "1" );
	$buttons[] = array( "text" => "2", "callback_data" => "2" );
	$buttons[] = array( "text" => "3", "callback_data" => "3" );
	$buttons[] = array( "text" => "4", "callback_data" => "4" );
	$buttons2 = [];
	$buttons2[] = array( "text" => "GO BACK", "callback_data" => "BACK" );
	$vbuttons[] = $buttons;
	$vbuttons[] = $buttons2;
	// $userData["chats"][$chatID]["log"][time()] = $txt;
	file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
	httpPost("sendMessage",array("chat_id" => $chatID, "text" => $txt, "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));			
}

function tweetWizardLastStep($userData,$chatID,$txtBack,$txtReady) {
	global $message; global $responseTextForLogging; global $start;
	if(($userData["chats"][$chatID]["wizard"]["textYesNo"] == "YES" && $userData["chats"][$chatID]["wizard"]["text"] && $userData["chats"][$chatID]["wizard"]["text"] != "/skip" ) || 0 < $userData["chats"][$chatID]["wizard"]["mediaCount"]) {
		$vbuttons = [];
		$buttons2 = [];
		$buttons2[] = array( "text" => "FIRE!", "callback_data" => "FIRE!" );
		$vbuttons[] = $buttons2;
		$buttons3 = [];
		$buttons3[] = array( "text" => "GO BACK", "callback_data" => "BACK" );
		$vbuttons[] = $buttons3;
		// $userData["chats"][$chatID]["log"][time()] = $txtReady;
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => $txtReady, "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));			
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	else {
		$vbuttons = [];
		$buttons2 = [];
		$buttons2[] = array( "text" => "RESTART", "callback_data" => "BACK" );
		$vbuttons[] = $buttons2;
		// $userData["chats"][$chatID]["log"][time()] = $txtBack;
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => $txtBack, "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));			
		wrtLog($message,$responseTextForLogging,$start); die();
	}
}

function replyWizardStep1($userData, $chatID, $replyTo = null) {
	if(!$userData["chats"][$chatID]["active"]) {
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Bot is stopped, /start it now!"));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	$links = array_keys($userData["chats"][$chatID]["links"]);
	if($links) {
		$shared = json_decode(file_get_contents($SHAREDFILE),true);
		if(count($shared["tweets"]) == 300 && $shared["tweets"][0]["time"] > time() - 60*60*3) {
			httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Twitter regulations allow each app to publish a maximum of 300 statuses every 3 hours, and we have reached that limit. You can send your reply now through the Twitter app or Website, or wait some time and try again with Twittelegram, or ask a quotation to have a dedicated instance of the Twittelegram service."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}			
		$vbuttons = [];
		foreach($links as $link) { $buttons = []; $buttons[] = array( "text" => $link, "callback_data" => $link ); $vbuttons[] = $buttons; }
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Reply as...", "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));
		$userData["chats"][$chatID]["wizard"] = array("id" => uniqid(), "context" => "reply", "chatID" => $chatID, "status" => "draft");
		if($replyTo) $userData["chats"][$chatID]["wizard"]["replyTo"] = $replyTo;
		// $userData["chats"][$chatID]["log"][time()] = "Reply as...";
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		wrtLog($message,$responseTextForLogging,$start); die();	
	}
	else {
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Please /link to a Twitter account first."));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
}

function retweetWizardStep1($userData, $chatID, $retweetThis = null) {
	if(!$userData["chats"][$chatID]["active"]) {
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Bot is stopped, /start it now!"));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	$links = array_keys($userData["chats"][$chatID]["links"]);
	if($links) {
		$shared = json_decode(file_get_contents($SHAREDFILE),true);
		if(count($shared["tweets"]) == 300 && $shared["tweets"][0]["time"] > time() - 60*60*3) {
			httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Twitter regulations allow each app to publish a maximum of 300 statuses every 3 hours, and we have reached that limit. You can retweet now through the Twitter app or Website, or wait some time and try again with Twittelegram, or ask a quotation to have a dedicated instance of the Twittelegram service."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}			
		$vbuttons = [];
		foreach($links as $link) { $buttons = []; $buttons[] = array( "text" => $link, "callback_data" => $link ); $vbuttons[] = $buttons; }
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Retweet as...", "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));
		$userData["chats"][$chatID]["wizard"] = array("id" => uniqid(), "context" => "retweet", "chatID" => $chatID, "status" => "draft");
		if($retweetThis) $userData["chats"][$chatID]["wizard"]["retweetThis"] = $retweetThis;
		// $userData["chats"][$chatID]["log"][time()] = "Retweet as...";
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		wrtLog($message,$responseTextForLogging,$start); die();	
	}
	else {
		httpPost("sendMessage",array("chat_id" => $chatID, "text" => "Please /link to a Twitter account first."));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
}

$rawMessage = file_get_contents('php://input');
$message = json_decode($rawMessage, true);

if(array_key_exists("message",$message)) { 

	$authorized = false;
	if($message["message"]["chat"]["type"] == "private") {
		$authorized = true;
	}
	else if($message["message"]["chat"]["all_members_are_administrators"]) {
		$authorized = true;
	}
	else if($message["message"]["text"] == "/unlink") {
		$authorized = true;
	}
	else {
		$rawChatMember = httpPost("getChatMember",array( "chat_id" => $message["message"]["chat"]["id"], "user_id" => $message["message"]["from"]["id"]));
		$chatMember = json_decode($rawChatMember, true);
		$status = $chatMember["result"]["status"];	
		if($status == "creator" || $status == "administrator") $authorized = true;
	}
	if(!$authorized) {
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Sorry, only the creator and the administrators can issue commands."));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if(!file_exists("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json")) {		
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Sorry, you have not an active subscription.", "reply_markup" => '{"inline_keyboard": [[{"text":"Terms and conditions", "url": "https://www.twittelegram.com/terms.php"}],[{"text":"Privacy policy", "url": "https://www.twittelegram.com/privacy.php"}],[{"text":"Subscribe Now!", "url": "https://www.twittelegram.com/#telegram_subscribe"}]]}' ));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	$userData = json_decode(file_get_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json"),true);
	if(!$userData["subscription"]["active"]) {
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Sorry, you have not an active subscription.", "reply_markup" => '{"inline_keyboard": [[{"text":"Terms and conditions", "url": "https://www.twittelegram.com/terms.php"}],[{"text":"Privacy policy", "url": "https://www.twittelegram.com/privacy.php"}],[{"text":"Resume Now!", "url": "https://www.twittelegram.com/#telegram_subscribe"}]]}' ));
		wrtLog($message,$responseTextForLogging,$start); die();
	}

	$folderName = $WEBHOOKSFOLDER.$message["message"]["from"]["id"];
	if(!file_exists($folderName)) mkdir($folderName);
	$fileName = $message["update_id"];
	file_put_contents("$folderName/$fileName.json",json_encode($message,JSON_PRETTY_PRINT));

	file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));

	if($message["message"]["text"] == "/start" || $message["message"]["text"] == "/start@twittelegramdotcom_bot") {
		$userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"] = uniqid();
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			$alreadyExists = false;
			if($message["message"]["chat"]["type"] == "private") {
				$chats = array_keys($userData["chats"]);
				foreach($chats as $chat) {
					if($userData["chats"][$chat]["type"] == "private") {
						$alreadyExists = $chat;
					}
				}
			}
			if(!$alreadyExists) {
				$userData["chats"][$message["message"]["chat"]["id"]]["title"] = $message["message"]["chat"]["title"];
				$userData["chats"][$message["message"]["chat"]["id"]]["type"] = $message["message"]["chat"]["type"];	
				$userData["chats"][$message["message"]["chat"]["id"]]["active"] = true;	
			}
			else {
				if($message["message"]["chat"]["id"] != $chat) {
					$userData["chats"][$message["message"]["chat"]["id"]] = $userData["chats"][$chat];
					$userData["chats"][$message["message"]["chat"]["id"]]["resumedFrom"][time()] = $chat;	
					unset($userData["chats"][$chat]);
				}
				$userData["chats"][$message["message"]["chat"]["id"]]["active"] = true;			
			}			
			file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Started.", "reply_markup" => '{"inline_keyboard": [[{"text":"Manage", "url": "https://www.twittelegram.com/telegram/manage_chat.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"]).'"}]]}'));	
		}
		else {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Already started.", "reply_markup" => '{"inline_keyboard": [[{"text":"Manage", "url": "https://www.twittelegram.com/telegram/manage_chat.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"]).'"}]]}'));	
		}
		wrtLog($message,$responseTextForLogging,$start); die();
	}

	if($message["message"]["text"] == "/stop" || $message["message"]["text"] == "/stop@twittelegramdotcom_bot") {
		if($userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			$userData["chats"][$message["message"]["chat"]["id"]]["active"] = false;	
			file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Stopped."));
		}
		else {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Already stopped."));
		}
		wrtLog($message,$responseTextForLogging,$start); die();
	}

	if($message["message"]["text"] == "/link" || $message["message"]["text"] == "/link@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		$chatType = $message["message"]["chat"]["type"];
		if($chatType == "private") $chatType = "private chat";
		$userData["chats"][$message["message"]["chat"]["id"]]["linkTmpPwd"] = uniqid();
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		if(empty(array_keys($userData["chats"][$message["message"]["chat"]["id"]]["links"]))) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "No Twitter account is linked to this $chatType at now.", "reply_markup" => '{"inline_keyboard": [[{"text":"Link", "url": "https://www.twittelegram.com/telegram/link.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["linkTmpPwd"]).'"}]]}'));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			$linksTxt = implode(", ",array_keys($userData["chats"][$message["message"]["chat"]["id"]]["links"]));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "This $chatType is linked to these Twitter profiles: $linksTxt.", "reply_markup" => '{"inline_keyboard": [[{"text":"Link more", "url": "https://www.twittelegram.com/telegram/link.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["linkTmpPwd"]).'"}],[{"text":"Unlink", "url": "https://www.twittelegram.com/telegram/unlink.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["linkTmpPwd"]).'"}]]}'));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
	}

	if($message["message"]["text"] == "/unlink" || $message["message"]["text"] == "/unlink@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		$chatType = $message["message"]["chat"]["type"];
		if($chatType == "private") $chatType = "private chat";
		$userData["chats"][$message["message"]["chat"]["id"]]["linkTmpPwd"] = uniqid();
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		if(empty(array_keys($userData["chats"][$message["message"]["chat"]["id"]]["links"]))) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "No Twitter account is linked to this $chatType at now."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			$linksTxt = implode(", ",array_keys($userData["chats"][$message["message"]["chat"]["id"]]["links"]));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "This $chatType is linked to these Twitter profiles: $linksTxt.", "reply_markup" => '{"inline_keyboard": [[{"text":"Unlink", "url": "https://www.twittelegram.com/telegram/unlink.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["linkTmpPwd"]).'"}]]}'));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
	}

	if($message["message"]["text"] == "/cancel" || $message["message"]["text"] == "/cancel@twittelegramdotcom_bot") {
		
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		
		
		$userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"] = uniqid();
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		
		$options = "";
		foreach(array_keys($userData["chats"][$message["message"]["chat"]["id"]]["readings"]) as $url) {
			$textparts = explode("/",str_replace("https://twitter.com/","",$url));
			if(count($textparts) == 1) {
				$text = "Timeline of ".$textparts[0];
			}
			else {
				$text = "List ".$textparts[2]." of ".$textparts[0];
			}
			$options.='[{"text":"'.$text.'", "url": "https://www.twittelegram.com/telegram/cancel.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"]).'&src='.urlencode($url).'"}],';
		}
		$options = trim($options,",");
		
		if(!$options) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "This chat is not reading from any Twitter list or timeline."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Tap on lists and timelines that you wish to stop following. No further confirmation is asked.", "reply_markup" => '{"inline_keyboard": ['.$options.']}'));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		
	}

	if($message["message"]["text"] == "/tweet" || $message["message"]["text"] == "/tweet@twittelegramdotcom_bot") {		
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["status"] == "publishing") {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Please wait."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			tweetWizardStep1($userData,$message["message"]["chat"]["id"]);
		}
	}

	if($message["message"]["text"] == "/reply" || $message["message"]["text"] == "/reply@twittelegramdotcom_bot") {
		if(!(array_key_exists("reply_to_message",$message["message"]) && 0 === strpos($message["message"]["reply_to_message"]["text"],"https://twitter.com"))) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Unexpected here. Ignored. Long tap a tweet that you have received from me the bot in this chat, then hit \"Reply\", and issue the command."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["status"] == "publishing") {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Please wait."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			replyWizardStep1($userData,$message["message"]["chat"]["id"],substr($message["message"]["reply_to_message"]["text"],1+strrpos($message["message"]["reply_to_message"]["text"],"/")));
		}	
	}

	if($message["message"]["text"] == "/retweet" || $message["message"]["text"] == "/retweet@twittelegramdotcom_bot") {
		if(!(array_key_exists("reply_to_message",$message["message"]) && 0 === strpos($message["message"]["reply_to_message"]["text"],"https://twitter.com"))) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Unexpected here. Ignored. Long tap a tweet that you have received from me the bot in this chat, then hit \"Reply\", and issue the command."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["status"] == "publishing") {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Please wait."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			retweetWizardStep1($userData,$message["message"]["chat"]["id"],substr($message["message"]["reply_to_message"]["text"],1+strrpos($message["message"]["reply_to_message"]["text"],"/")));
		}	
	}

	if($message["message"]["text"] == "/read" || $message["message"]["text"] == "/read@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		$readings = array_keys($userData["chats"][$message["message"]["chat"]["id"]]["readings"]);
		for($i = 0; $i < count($readings); $i++) {
			$rurl = $readings[$i];
			if($userData["chats"][$message["message"]["chat"]["id"]]["readings"][$rurl]) {
				if($i > 0 && $i == count($readings)-1) $t .= " and ";
				else if($i > 0) $t .= ", ";
				if(explode("/",$rurl)[4] == "lists") $t .= "from the list ".explode("/",$rurl)[5]." owned by ".explode("/",$rurl)[3];
				else $t .= "from the timeline of ".explode("/",$rurl)[3];			
			}
		}
		if(empty($t)) $t = "nothing";
		$userData["chats"][$message["message"]["chat"]["id"]]["readTmpPwd"] = uniqid();
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "OK. This chat is currently reading $t. Hit the button below to open the page that will take you by the hand in the creation of a new reading rule. A reading rule is where you specify that you wish this chat to receive contents from a given Twitter profile and list of your interest, also indicating days, time interval, and periodicity of readings. Add as many rules as you wish!", "reply_markup" => '{"inline_keyboard": [[{"text":"Create reading rule", "url": "https://www.twittelegram.com/telegram/read.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["readTmpPwd"]).'"}]]}'));		
		wrtLog($message,$responseTextForLogging,$start); die();
	}

	if($message["message"]["text"] == "/fund" || $message["message"]["text"] == "/fund@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Not ready yet."));
		wrtLog($message,$responseTextForLogging,$start); die();	
	}

	if($message["message"]["text"] == "/settings" || $message["message"]["text"] == "/settings@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		$userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"] = uniqid();	
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT)); 
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "OK, hit the button below here to enter the Web-based configuration tool.", "reply_markup" => '{"inline_keyboard": [[{"text":"Configure", "url": "https://www.twittelegram.com/telegram/manage_chat.php?tkn='.md5($userData["chats"][$message["message"]["chat"]["id"]]["mgmtTmpPwd"]).'"}]]}'));	
		wrtLog($message,$responseTextForLogging,$start); die();
	} 

	if($message["message"]["text"] == "/help" || $message["message"]["text"] == "/help@twittelegramdotcom_bot") {
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Twittelegram allows communities to have a customized selection of Twitter contents extracted from lists and timelines sent directly to their Telegram group or channel, where members can continue to freely discuss about received contents or anything else without that the Twittelegram bot could know anything of conversations among members, and even seamlessly post new statuses (tweets, replies, and retweets) directly from within the Telegram group on behalf of Twitter profiles that they will have previously linked. While the Twittelegram service is predominantly targeted to communities, you also can use it as an individual, starting a chat with the Twittelegram bot directly. To start using Twittelegram in your community's group, just add the @twittelegramdotcom_bot to the group, or connect to https://t.me/twittelegramdotcom_bot to have a private chat with the bot and therefore use the service as an individual. Then, issue the /start command. At your first time, you will be asked to subscribe. Take the time to read relevant documents, and feel free to ask any question directly to the project owner at mirco.soderi@gmail.com."));	
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if($message["message"]["reply_to_message"]["text"] == $TXT2) { 
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if($message["message"]["text"] != "/skip") $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["text"] = $message["message"]["text"];
		file_put_contents("$TELEGRAMUSERSPATH".$message["message"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		if(array_key_exists("photo",$message["message"]) || array_key_exists("video",$message["message"]) || array_key_exists("photo",$message["document"])) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Only text please. Media files later. /skip to jump to them."));
		}
		tweetWizardStep2($userData, $message["message"]["chat"]["id"], $TXT3);	
		wrtLog($message,$responseTextForLogging,$start); die();		
	}
	
	if( array_key_exists("photo",$message["message"]) || array_key_exists("video",$message["message"]) || array_key_exists("document",$message["message"]) ) { 
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if(!( ($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "tweet" || $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "reply") && $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"] > 0 && count($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["media"]) < $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"])) {
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Unexpected now. Ignored.", "reply_markup" => '{ "force_reply": true }' ));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		
		if(array_key_exists("media_group_id",$message["message"])) {
			if(!in_array($message["message"]["media_group_id"],$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["fucking_media_groups"])) {				
				$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["fucking_media_groups"][] = $message["message"]["media_group_id"];				
				file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));			
				httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Everything rejected. Please send media files one by one.", "reply_markup" => '{ "force_reply": true }' ));
			}
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		
		if(array_key_exists("document",$message["message"]) && 0 !== strpos($message["message"]["document"]["mime_type"],"image/") && 0 !== strpos($message["message"]["document"]["mime_type"],"video/")) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Rejected. Only photos and videos can be attached to tweets.", "reply_markup" => '{ "force_reply": true }' ));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		
		if(array_key_exists("document",$message["message"]) && 0 !== strpos($message["message"]["document"]["mime_type"],"image/") && $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"] > 1 ) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Rejected. You have indicated that you wish to attach a total of ".$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"]." media files to your ".$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"].". Twitter imposes that if you attach more than one media file, they all must be images.", "reply_markup" => '{ "force_reply": true }' ));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		
		if(array_key_exists("video",$message["message"]) && $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"] > 1 ) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Rejected. You have indicated that you wish to attach a total of ".$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"]." media files to your ".$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"].". Twitter imposes that if you attach more than one media file, they all must be images.", "reply_markup" => '{ "force_reply": true }' ));
			wrtLog($message,$responseTextForLogging,$start); die();
		}

		$media = null;
		if(array_key_exists("photo",$message["message"])) { $media = $message["message"]["photo"]; $media["type"] = "photo"; $media["url"] = getFileUrl($message["message"]["photo"][count($message["message"]["photo"])-1]["file_id"]); }
		else if(array_key_exists("video",$message["message"])) { $media = $message["message"]["video"]; $media["type"] = "video"; $media["url"] = getFileUrl($message["message"]["video"]["file_id"]); }
		else { $media = $message["message"]["document"]; $media["type"] = "document"; $media["url"] = getFileUrl($message["message"]["document"]["file_id"]); }		
		if( $media["type"] == "video" && ($media["duration"] == 0 || $media["duration"] > 140 || $media["file_size"] > 15*1024*1024 ) ) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Rejected. The maximum allowed duration of a video for Twitter usage is 140 seconds. Maximum allowed size is 15 MB.", "reply_markup" => '{ "force_reply": true }' ));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if( $media["type"] == "document" && $media["mime_type"] == "image/gif" && $media["file_size"] > 15*1024*1024 ) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Rejected. Maximum allowed size for Twitter usage is 15 MB.", "reply_markup" => '{ "force_reply": true }'));
			wrtLog($message,$responseTextForLogging,$start); die();
		}			
		
		$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["media"][] = $media;		
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));		
		httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Loaded. ".(count($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["media"]) == $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"] ? "/remove it now if you have changed your mind, or /continue to finalize." : "/remove it now if you have changed your mind, or send the next one."), "reply_markup" => '{ "force_reply": true }' ));
		wrtLog($message,$responseTextForLogging,$start); die();
		
	}
	
	if($message["message"]["text"] == "/skip" || $message["message"]["text"] == "/skip@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if(!( ($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "tweet" || $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "reply") && $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["textYesNo"] == "YES")) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Unexpected here. Ignored. I tell you when you can skip what."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["textYesNo"] = "NO";
			unset($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["text"]);
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			tweetWizardStep2($userData, $message["message"]["chat"]["id"], $TXT3);	
			wrtLog($message,$responseTextForLogging,$start); die();
		}
	}
	
	if($message["message"]["text"] == "/continue" || $message["message"]["text"] == "/continue@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if(!( ($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "tweet" || $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "reply") && count($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["media"]) == $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["mediaCount"])) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Unexpected here. Ignored. I tell you when you can continue what."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			tweetWizardLastStep($userData,$message["message"]["chat"]["id"],$TXT5,sprintf($TXT6,$userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"]));	
			wrtLog($message,$responseTextForLogging,$start); die();
		}
	}
	
	if($message["message"]["text"] == "/remove" || $message["message"]["text"] == "/remove@twittelegramdotcom_bot") {
		if(!$userData["chats"][$message["message"]["chat"]["id"]]["active"]) {
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Bot is stopped, /start it now!"));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		if(!( ($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "tweet" || $userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["context"] == "reply")  && count($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["media"]) > 0 )) {			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Unexpected here. Ignored. I tell you when you can remove what."));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			array_pop($userData["chats"][$message["message"]["chat"]["id"]]["wizard"]["media"]);			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "Removed. Now please send me a replacement for it.", "reply_markup" => '{ "force_reply": true }' ));
			wrtLog($message,$responseTextForLogging,$start); die();
		}
	}

	httpPost("sendMessage",array("chat_id" => $message["message"]["chat"]["id"], "text" => "I was not able to understand your message."));
	wrtLog($message,$responseTextForLogging,$start); die();
	
}

if(array_key_exists("callback_query",$message)) { 

	$authorized = false;
	if($message["callback_query"]["message"]["chat"]["type"] == "private") {
		$authorized = true;
	}
	else if($message["callback_query"]["message"]["chat"]["all_members_are_administrators"]) {
		$authorized = true;
	}
	else {
		$rawChatMember = httpPost("getChatMember",array( "chat_id" => $message["callback_query"]["message"]["chat"]["id"], "user_id" => $message["callback_query"]["from"]["id"]));
		$chatMember = json_decode($rawChatMember, true);
		$status = $chatMember["result"]["status"];	
		if($status == "creator" || $status == "administrator") $authorized = true;
	}
	if(!$authorized) {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"], "text" => "Only the group creators and the administrators can interact with the bot.", "show_alert" => true));		
		wrtLog($message,$responseTextForLogging,$start); die();
	}

	if(!file_exists("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json")) {		
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"], "text" => "It seems that you don't have an active subscription for the Twittelegram service.", "show_alert" => true));		
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	$userData = json_decode(file_get_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json"),true);
	if(!$userData["subscription"]["active"]) {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"], "text" => "It seems that you don't have an active subscription for the Twittelegram service.", "show_alert" => true));		
		wrtLog($message,$responseTextForLogging,$start); die();
	}

	$folderName = $WEBHOOKSFOLDER.$message["callback_query"]["from"]["id"];
	if(!file_exists($folderName)) mkdir($folderName);
	$fileName = $message["update_id"];
	file_put_contents("$folderName/$fileName.json",$rawMessage);
	
	httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "parse_mode" => "html", "text" => "Selection: <b>".(strpos($message["callback_query"]["data"],"undo") !== 0 ? $message["callback_query"]["data"] : "Noo!! Delete it!")."</b>" ));			

	file_put_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));

	if($message["callback_query"]["message"]["text"] == "Tweet as..." || $message["callback_query"]["message"]["text"] == "Reply as...") {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));
		$vbuttons = [];
		$buttons = [];
		$buttons[] = array( "text" => "YES", "callback_data" => "YES" ); 
		$buttons[] = array( "text" => "NO", "callback_data" => "NO" );
		$buttons2 = [];
		$buttons2[] = array( "text" => "GO BACK", "callback_data" => "BACK" );
		$vbuttons[] = $buttons;
		$vbuttons[] = $buttons2;
		httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => sprintf($TXT1,$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"]), "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));			
		$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["user"] = $message["callback_query"]["data"];		
		file_put_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if($message["callback_query"]["message"]["text"] == "Retweet as..." ) {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));
		$vbuttons = [];
		$buttons = [];
		$buttons[] = array( "text" => "FIRE!", "callback_data" => "FIRE!" ); 
		$buttons2 = [];
		$buttons2[] = array( "text" => "GO BACK", "callback_data" => "BACK" );
		$vbuttons[] = $buttons;
		$vbuttons[] = $buttons2;
		httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => "OK. Fire?", "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));			
		$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["user"] = $message["callback_query"]["data"];		
		file_put_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if($message["callback_query"]["message"]["text"] == sprintf($TXT1,$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"])) {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));
		$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["textYesNo"] = $message["callback_query"]["data"];		
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		if("YES" == $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["textYesNo"]) {
			httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => $TXT2, "reply_markup" => json_encode(array("force_reply" => true)) ));			
		}
		else if("NO" == $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["textYesNo"]) {
			tweetWizardStep2($userData, $message["callback_query"]["message"]["chat"]["id"],$TXT3);			
		}
		else {
			if($userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"] == "tweet") tweetWizardStep1($userData,$message["callback_query"]["message"]["chat"]["id"]);
			else replyWizardStep1($userData,$message["callback_query"]["message"]["chat"]["id"]);
		}
		wrtLog($message,$responseTextForLogging,$start); die();		
	}
	
	if($message["callback_query"]["message"]["text"] == "OK. Fire?") {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));		
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		if("FIRE!" == $message["callback_query"]["data"]) {					
			$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["status"] = "publishing";
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			$shared = json_decode(file_get_contents($SHAREDFILE),true);
			$shared["tweets"][] = array( "time" => time(), "type" => $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"], "user" => $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["user"], "tweet" => $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["text"]);
			if(count($shared["tweets"]) > 300) $shared["tweets"] = array_shift($shared["tweets"]);		
			file_put_contents($SHAREDFILE,json_encode($shared, JSON_PRETTY_PRINT));	
			httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => "OK, your retweet will be online shortly."));				
		}
		else {
			retweetWizardStep1($userData,$message["callback_query"]["message"]["chat"]["id"]);
		}
		wrtLog($message,$responseTextForLogging,$start); die();		
	}
	
	if($message["callback_query"]["message"]["text"] == $TXT3) {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));
		if($message["callback_query"]["data"] == "BACK") {
			$vbuttons = [];
			$buttons = [];
			$buttons[] = array( "text" => "YES", "callback_data" => "YES" ); 
			$buttons[] = array( "text" => "NO", "callback_data" => "NO" );
			$buttons2 = [];
			$buttons2[] = array( "text" => "GO BACK", "callback_data" => "BACK" );
			$vbuttons[] = $buttons;
			$vbuttons[] = $buttons2;			
			file_put_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => sprintf($TXT1,$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"]), "reply_markup" => '{"inline_keyboard": '.json_encode($vbuttons).'}' ));			
			wrtLog($message,$responseTextForLogging,$start); die();
		}
		else {
			$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["mediaCount"] = $message["callback_query"]["data"];			
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			if(0 < $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["mediaCount"]) {
				httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => $TXT4, "reply_markup" => json_encode(array("force_reply" => true)) ));			
			}
			else {
				tweetWizardLastStep($userData, $message["callback_query"]["message"]["chat"]["id"], $TXT5, sprintf($TXT6,$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"]));
			}
			wrtLog($message,$responseTextForLogging,$start); die();	
		}		
	}

	if(0 === strpos($message["callback_query"]["message"]["text"],"Twitted!") || 0 === strpos($message["callback_query"]["message"]["text"],"Replied!") || 0 === strpos($message["callback_query"]["message"]["text"],"Retwitted!")) {			
		$response = json_decode(undoTweet(),true);
		if(!array_key_exists("errors",$response)) {
			httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));		
			httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => "Deleted."));				
		}
		else {
			httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"], "show_alert" => true));		
			httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => "Failed. You can try deleting it manually from the Twitter app or Website. Please let me know about this error reporting error code ".$response["errors"][0]["code"]." and error message ".$response["errors"][0]["message"]));	
		}
		file_put_contents("$TELEGRAMUSERSPATH".$message["callback_query"]["from"]["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if($message["callback_query"]["message"]["text"] == "OK" && 0 === strpos($message["callback_query"]["data"],"Remove media ")) { 
		$url = explode(" ",$message["callback_query"]["data"])[2];
		$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["media"] = array_filter($userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["media"], function($m) { return $m["url"] != $url; });
		file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
		httpPost("answerCallbackQuery",array("text" => "Removed","callback_query_id" => $message["callback_query"]["id"]));
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if($message["callback_query"]["message"]["text"] == $TXT5) {
		httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));
		if($userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"] == "tweet") tweetWizardStep1($userData,$message["callback_query"]["message"]["chat"]["id"]);
		else replyWizardStep1($userData,$message["callback_query"]["message"]["chat"]["id"]);
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	if($message["callback_query"]["message"]["text"] == sprintf($TXT6,$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"])) {
		if($message["callback_query"]["data"] == "FIRE!") {			
			httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));						
			$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["status"] = "publishing";
			file_put_contents("$TELEGRAMUSERSPATH".$userData["id"].".json",json_encode($userData, JSON_PRETTY_PRINT));
			$shared = json_decode(file_get_contents($SHAREDFILE),true);
			$shared["tweets"][] = array( "time" => time(), "type" => $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"], "user" => $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["user"], "tweet" => $userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["text"]);
			if(count($shared["tweets"]) > 300) $shared["tweets"] = array_shift($shared["tweets"]);		
			file_put_contents($SHAREDFILE,json_encode($shared, JSON_PRETTY_PRINT));	
			httpPost("sendMessage",array("chat_id" => $message["callback_query"]["message"]["chat"]["id"], "text" => "OK, your ".$userData["chats"][$message["callback_query"]["message"]["chat"]["id"]]["wizard"]["context"]." will be online shortly."));				
		}	
		else {
			httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"]));
			tweetWizardStep2($userData, $message["callback_query"]["message"]["chat"]["id"],$TXT3);		
		}
		wrtLog($message,$responseTextForLogging,$start); die();
	}
	
	httpPost("answerCallbackQuery",array("callback_query_id" => $message["callback_query"]["id"], "text" => "Something wrong happened. I could not understand your selection."));
	
}

?>
