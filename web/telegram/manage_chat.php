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
session_start();
session_regenerate_id();

require_once("../lib/ssh.php");
require_once("../lib/Mobile_Detect.php");
require_once("../lib/const.php");
$detect = new Mobile_Detect;

// login

function getData($tkn) {
	$files = array();
	if ($handle = opendir($TELEGRAMUSERSPATH)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
			   $files[filemtime($file)] = $file;
			}
		}
		closedir($handle);
		krsort($files);
		$mtimes = array_keys($files);
		foreach($mtimes as $mtime) {
			$candidate = json_decode(file_get_contents($TELEGRAMUSERSPATH.$files[$mtime]),true);
			$chats_ids = array_keys($candidate["chats"]);
			foreach($chats_ids as $chat_id) {
				if(array_key_exists("mgmtTmpPwd",$candidate["chats"][$chat_id]) && $tkn == md5($candidate["chats"][$chat_id]["mgmtTmpPwd"])) {
					unset($candidate["chats"][$chat_id]["mgmtTmpPwd"]);
					file_put_contents($TELEGRAMUSERSPATH.$files[$mtime],json_encode($candidate, JSON_PRETTY_PRINT));	
					$_SESSION["user"] = $candidate;
					$_SESSION["chat"] = $chat_id;
					return true;					
				}
			}
		}
	}
	return false;
}

function isTimezone($v) {
	$isTimezone = false;
	$tzgs = json_decode(file_get_contents("timezones.json"),true); $tzs = []; foreach($tzgs as $tzg) $tzs = array_merge($tzs,$tzg["zones"]);
	foreach($tzs as $tz) if($v == $tz["value"]) $isTimezone = true;
	return $isTimezone;	
}

$authorized = false;
$tkn = filter_input(INPUT_GET, 'tkn', FILTER_SANITIZE_URL);
if($tkn) { $authorized = getData($tkn); } 
else { $authorized = $_SESSION["user"] && $_SESSION["chat"]; }

if($authorized) {

$_SESSION["user"] = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);

// manage

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_URL);

if($action == "start") {
	$_SESSION["user"]["chats"][$_SESSION["chat"]]["active"] = true;	
	file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($_SESSION["user"], JSON_PRETTY_PRINT));	
	header("Location: http://www.twittelegram.com/telegram/manage_chat.php");
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Activation of the Twittelegram service for this chat.";	
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

if($action == "stop") {
	$_SESSION["user"]["chats"][$_SESSION["chat"]]["active"] = false;	
	file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($_SESSION["user"], JSON_PRETTY_PRINT));	
	header("Location: http://www.twittelegram.com/telegram/manage_chat.php");
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Deactivation of the Twittelegram service for this chat.";	
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
  
if($action == "unlink") {
	$_SESSION["user"]["chats"][$_SESSION["chat"]]["linkTmpPwd"] = uniqid();
	file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($_SESSION["user"], JSON_PRETTY_PRINT));
	$_SESSION["addBackLink"] = true;
	header("Location: http://www.twittelegram.com/telegram/unlink.php?tkn=".md5($_SESSION["user"]["chats"][$_SESSION["chat"]]["linkTmpPwd"]));	
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Pre-initialization of the procedure for canceling the link with a Twitter profile.";	
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

if($action == "link") {
	$_SESSION["user"]["chats"][$_SESSION["chat"]]["linkTmpPwd"] = uniqid();
	file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($_SESSION["user"], JSON_PRETTY_PRINT));
	$_SESSION["addBackLink"] = true;
	header("Location: http://www.twittelegram.com/telegram/link.php?tkn=".md5($_SESSION["user"]["chats"][$_SESSION["chat"]]["linkTmpPwd"]));	
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Link pre-initialization.";	
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

if($action == "cancel") {
	$src = filter_input(INPUT_GET, 'src', FILTER_SANITIZE_URL);	
	dellnxjob($_SESSION["user"]["lnx"]["usr"], $_SESSION["user"]["lnx"]["pwd"], $src);	
	unset($_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"][$src]);	
	file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($_SESSION["user"], JSON_PRETTY_PRINT));
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Deletion of the following rule: ".$_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"][$src]["stmt"];
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
}

if($action == "read") {
	
	$stmt = trim($_GET["stmt"],".");
	$a = explode(" ",str_replace("timeline at","timeline",str_replace("list at","list",preg_replace('!\s+!', ' ',$stmt))));
	
	array_splice($a,9,0,array("24H")); array_splice($a,12,0,array("24H")); 

	$parseErr = "";
	if(!lnxchk($_SESSION["user"]["lnx"]["usr"],$_SESSION["user"]["lnx"]["pwd"])) $parseErr = "Please wait while Twittelegram completes the setup of your account. It could take one minute or more. Thank you for your patience."; 
	if(strtolower($a[0]) != "read" && !$parseErr) $parseErr = "Syntax error. Found ".($a[0]?"\"".$a[0]."\"":"nothing").". Expected \"Read\".";
	if($a[1] != "from" && !$parseErr) $parseErr = "Syntax error. Found ".($a[1]?"\"".$a[1]."\"":"nothing").". Expected \"from\".";
	if($a[2] != "list" && $a[2] != "timeline" && !$parseErr) $parseErr = "Syntax error. Found ".($a[2]?"\"".$a[2]."\"":"nothing").". Expected \"list at\" or \"timeline at\".";	
	$u = explode("/", $a[3]);
	if((!$parseErr) && !strpos($a[3],"https://twitter.com/") === 0) {
		$parseErr = "The Web address that you have specified is not a valid Web address of a Twitter list or timeline. A valid Web address of a Twitter timeline is for example https://twitter.com/mircosoderi while a valid Web address of a Twitter list is for example https://twitter.com/mircosoderi/lists/tt-high";
	}	
	if((!$parseErr) && !(count($u) == 4 || (count($u) == 6 && $u[4] == "lists"))) {
		$parseErr = "The Web address that you have specified is not a valid Web address of a Twitter list or timeline. A valid Web address of a Twitter timeline is for example https://twitter.com/mircosoderi while a valid Web address of a Twitter list is for example https://twitter.com/mircosoderi/lists/tt-high";
	}
	if(!$parseErr) {
		if(!file_exists($TWITTERUSERSPATH.$u[3].".json")) {
			$parseErr = "The Twitter user ".$u[3]." has not subscribed to the Twittelegram service yet.";
		}
		else {		
			$srcData = json_decode(file_get_contents($TWITTERUSERSPATH.$u[3].".json"),true);
			if(!$srcData["subscription"]["active"]) {
				$parseErr = "The Twitter user ".$u[3]." has not an active subscription to the Twittelegram service at the moment.";
			}
			else {		
				$ok = false;
				$ls = array_keys($srcData["lists"]);
				foreach($ls as $l) {
					if(str_replace("https://twitter.com","",$a[3]) == $srcData["lists"][$l]["uri"] && $srcData["lists"][$l]["access"] == "granted") {
						if($srcData["lists"][$l]["whocanread"] == "everybody") {
							$ok = true;
						}
						else if($srcData["lists"][$l]["whocanread"] == "nonblacklisted") {
							if(!in_array($_SESSION["chat"],$srcData["lists"][$l]["wblisted"])) {
								$ok = true;
							}
						}
						else if($srcData["lists"][$l]["whocanread"] == "whitelisted") {
							if(in_array($_SESSION["chat"],$srcData["lists"][$l]["wblisted"])) {
								$ok = true;
							}
						}
						$descr = $_SESSION["user"]["chats"][$_SESSION["chat"]]["type"] == "group" ? "Group ".$_SESSION["user"]["chats"][$_SESSION["chat"]]["title"]." managed by ".$_SESSION["user"]["firstName"]." ".$_SESSION["user"]["lastName"]:"Private chat of ".$_SESSION["user"]["firstName"]." ".$_SESSION["user"]["lastName"]." with the Twittelegram bot";
						$srcData["lists"][$l]["requesters"][$_SESSION["chat"]] = $descr;
						file_put_contents($TWITTERUSERSPATH.$u[3].".json",json_encode($srcData, JSON_PRETTY_PRINT));
					}
					if(str_replace("https://twitter.com","",$a[3]) == "/".$u[3] && $srcData["lists"]["0"]["access"] == "granted") {
						if($srcData["lists"]["0"]["whocanread"] == "everybody") {
							$ok = true;
						}
						else if($srcData["lists"]["0"]["whocanread"] == "nonblacklisted") {
							if(!in_array($message["message"]["chat"]["id"],$srcData["lists"]["0"]["wblisted"])) {
								$ok = true;
							}
						}
						else if($srcData["lists"]["0"]["whocanread"] == "whitelisted") {
							if(in_array($message["message"]["chat"]["id"],$srcData["lists"]["0"]["wblisted"])) {
								$ok = true;
							}
						}
						$descr = $_SESSION["user"]["chats"][$_SESSION["chat"]]["type"] == "group" ? "Group ".$_SESSION["user"]["chats"][$_SESSION["chat"]]["title"]." managed by ".$_SESSION["user"]["firstName"]." ".$_SESSION["user"]["lastName"]:"Private chat of ".$_SESSION["user"]["firstName"]." ".$_SESSION["user"]["lastName"]." with the Twittelegram bot";
						$srcData["lists"]["0"]["requesters"][$_SESSION["chat"]] = $descr;
						file_put_contents($TWITTERUSERSPATH.$u[3].".json",json_encode($srcData, JSON_PRETTY_PRINT));
					}
				}
				if(!$ok) {
					$softParseErr = "Your request has been stored, but be warned that ".$u[3]." has imposed some rules that limit access to contents at ".$a[3].". Ask directly to ".$u[3]." to learn more.";
				}
			}
		}

		if((!($u[0] == "https:" && $u[1] === "" && $u[2] == "twitter.com" && ( ( $a[2] == "list" && $u[4] == "lists" && count($u) == 6 ) || ( $a[2] == "timeline" && count($u) == 4 ) ) ) ) && !$parseErr) $parseErr = "Invalid ".($a[2]=="list"?"list":"profile")." address. Found ".($a[3]?"\"".$a[3]."\"":"nothing").". Expected Web address of ".($a[2]=="list"?"a Twitter list":"a Twitter profile").".";		
		if($a[7] != "from" && !$parseErr) $parseErr = "Syntax error. Found ".($a[7]?"\"".$a[7]."\"":"nothing").". Expected \"from\".";
		$h = explode(":",$a[8]); $a[8] = str_pad($h[0],2,"0",STR_PAD_LEFT).":".str_pad($h[1],2,"0",STR_PAD_LEFT); $h = explode(":",$a[8]);	
		if($a[9] != "AM" && $a[9] != "PM" && $a[9] != "24H" && !$parseErr) $parseErr = "Invalid start time.";
		if(!(ctype_digit($h[0]) && $h[0] >= (($a[9] == "24H"?0:1)) && $h[0] <= ($a[9] == "24H"?23:12) && ctype_digit($h[1]) && $h[1] >= 0 && $h[1] < 60 ) && !$parseErr) $parseErr = "Invalid start time.";	
		if($a[10] != "to" && !$parseErr) $parseErr = "Syntax error. Found ".($a[10]?"\"".$a[10]."\"":"nothing").". Expected \"to\".";
		$h = explode(":",$a[11]); $a[11] = str_pad($h[0],2,"0",STR_PAD_LEFT).":".str_pad($h[1],2,"0",STR_PAD_LEFT); $h = explode(":",$a[11]);
		if($a[12] != "AM" && $a[12] != "PM" && $a[12] != "24H" && !$parseErr) $parseErr = "Invalid end time.";
		if(!(ctype_digit($h[0]) && $h[0] >= ($a[12] == "24H"?0:1) && $h[0] <= ($a[12] == "24H"?24:12) && ctype_digit($h[1]) && $h[1] >= 0 && $h[1] < 60 ) && !$parseErr) $parseErr = "Invalid end time.";	
		if((!isTimezone($a[13])) && !$parseErr) $parseErr = "Invalid time zone. Found ". ($a[13]?"\"".$a[13]."\"":"nothing").". Expected a TZ database time zone name."; 
		if($a[14] != "time" && !$parseErr) $parseErr = "Syntax error. Found ".($a[14]?"\"".$a[14]."\"":"nothing").". Expected \"time\".";	
		if($a[15] != "on" && !$parseErr) $parseErr = "Syntax error. Found ".($a[15]?"\"".$a[15]."\"":"nothing").". Expected \"on\".";
		$on = substr($stmt, 4+strpos($stmt," on "));
		$v = false;
		if(trim($on) == "weekends") $v = true;
		if(trim($on) == "working days") $v = true;	
		if(!$v) {
			$ona = explode(",",$on);
			if(count($ona) > 0) {
				$v = true;
				foreach($ona as $ond) {
					if(trim(strtolower($ond)) != "monday" && trim(strtolower($ond)) != "tuesday" && trim(strtolower($ond)) != "wednesday" && 
						trim(strtolower($ond)) != "thursday" && trim(strtolower($ond)) != "friday" && trim(strtolower($ond)) != "saturday" &&
						trim(strtolower($ond)) != "sunday") {
							$v = false;
					}
				}
			}
		}
		if((!$v) && !$parseErr) $parseErr = "Invalid weekdays. Found ".($on?"\"".$on."\"":"nothing").". Expected \"working days\", or \"weekends\", or a comma-separated list of weekday names.";

		if(!$parseErr) {					
			$jbe = mklnxjob($a,$_SESSION["chat"],$_SESSION["user"]["id"],$a[3],$a[13]);			
			addlnxjob($_SESSION["user"]["lnx"]["usr"], $_SESSION["user"]["lnx"]["pwd"], $jbe);		
			$_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"][$a[3]]["stmt"] = $stmt;
			$_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"][$a[3]]["job"] = $jbe;
			file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($_SESSION["user"], JSON_PRETTY_PRINT));			
			if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
				$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
				$microtime = "".microtime(true);
				$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = $stmt;
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
		}	
	}
}

$tzgs = json_decode(file_get_contents("timezones.json"),true); $tzs = []; foreach($tzgs as $tzg) $tzs = array_merge($tzs,$tzg["zones"]);

?>

<html>
	<head>
		<title>twittelegram</title>
		<style>
			body {
				font-family: sans-serif;
				min-height: 100%;
				-webkit-background-size: cover;
				-moz-background-size: cover;
				-o-background-size: cover;
				background-size: cover;
				background: #00aced;
				background-repeat:no-repeat;
				background: -webkit-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: -moz-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: -ms-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: -o-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
			}
			
			div#container {
				background: white;
				padding: 1em;
				margin: 100px;
			}
			
			a {
				text-decoration: none;
				font-weight: bold;
				color: inherit;
			}
			
			h2 {
				border: medium solid black;
				padding:0.2em;
				color: black;
				background-color: #00aced;
				margin-top:2em;
			}

		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script>
		 function buildReading() {
			if(document.getElementById('reading_where').value == "") {
				document.getElementById('reading_where').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_where').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_url').value == "") {
				document.getElementById('reading_url').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_url').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_periodicity').value == "" || isNaN(document.getElementById('reading_periodicity').value)) {
				document.getElementById('reading_periodicity').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_periodicity').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_period_type').value == "") {
				document.getElementById('reading_period_type').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_period_type').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_start_hour').value == "" || isNaN(document.getElementById('reading_start_hour').value)) {
				document.getElementById('reading_start_hour').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_start_hour').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_start_minute').value == "" || isNaN(document.getElementById('reading_start_minute').value)) {
				document.getElementById('reading_start_minute').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_start_minute').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_start_ampm').value == "") {
				document.getElementById('reading_start_ampm').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_start_ampm').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_end_hour').value == "" || isNaN(document.getElementById('reading_end_hour').value)) {
				document.getElementById('reading_end_hour').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_end_hour').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_end_minute').value == "" || isNaN(document.getElementById('reading_end_minute').value)) {
				document.getElementById('reading_end_minute').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_end_minute').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_end_ampm').value == "") {
				document.getElementById('reading_end_ampm').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_end_ampm').style.border = "thin solid darkgray";
			}
			if(document.getElementById('reading_weekdays').value == "") {
				document.getElementById('reading_weekdays').style.border = "thin solid red";
				return false;
			}
			else {
				document.getElementById('reading_weekdays').style.border = "thin solid darkgray";
			}
			var weekdays = $('#reading_weekdays').val().join(", ");
			document.getElementById("new_read").value = "Read from "+document.getElementById('reading_where').value+
				" https://twitter.com/" + document.getElementById('reading_url').value + 
				" every "+document.getElementById('reading_periodicity').value +
				" " + document.getElementById('reading_period_type').value +
				" from " + document.getElementById('reading_start_hour').value + ":" + document.getElementById('reading_start_minute').value  +
				" " + document.getElementById('reading_start_ampm').value + " to " +  
				document.getElementById('reading_end_hour').value + ":" + document.getElementById('reading_end_minute').value  +
				" " + document.getElementById('reading_end_ampm').value + " Europe/Rome time on " + weekdays;
				return true;
			
		 }
		 
		 var getUrlParameter = function getUrlParameter(sParam) {
			var sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split('&'),
				sParameterName,
				i;

			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === sParam) {
					return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
		};
		 
		 $(function() {
			if(getUrlParameter("reading_where")) $("reading_where").val(getUrlParameter("reading_where"));
			if(getUrlParameter("reading_url")) $("reading_url").val(getUrlParameter("reading_url"));
			if(getUrlParameter("reading_periodicity")) $("reading_periodicity").val(getUrlParameter("reading_periodicity"));
			if(getUrlParameter("reading_period_type")) $("reading_period_type").val(getUrlParameter("reading_period_type"));
			if(getUrlParameter("reading_start_hour")) $("reading_start_hour").val(getUrlParameter("reading_start_hour"));
			if(getUrlParameter("reading_start_minute")) $("reading_start_minute").val(getUrlParameter("reading_start_minute"));
			if(getUrlParameter("reading_start_ampm")) $("reading_start_ampm").val(getUrlParameter("reading_start_ampm"));
			if(getUrlParameter("reading_end_hour")) $("reading_end_hour").val(getUrlParameter("reading_end_hour"));
			if(getUrlParameter("reading_end_minute")) $("reading_end_minute").val(getUrlParameter("reading_end_minute"));
			if(getUrlParameter("reading_end_ampm")) $("reading_end_ampm").val(getUrlParameter("reading_end_ampm"));
			if(getUrlParameter("reading_weekdays")) $("reading_weekdays").val(getUrlParameter("reading_weekdays").split(", "));
			
		});
		</script>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced;">twit</span><span style="color: #0088cc; border: medium solid #0088cc; margin:0.1em; padding:0.1em;">telegram</span></h1>
			<h2>Manage <?=htmlentities($_SESSION["user"]["chats"][$_SESSION["chat"]]["type"])?> <?=$_SESSION["user"]["chats"][$_SESSION["chat"]]["title"]?htmlentities($_SESSION["user"]["chats"][$_SESSION["chat"]]["title"]):"chat"?></h2>
			<p>This is the Twittelegram configuration page for your <?=$_SESSION["user"]["chats"][$_SESSION["chat"]]["type"] == "private"?"private chat with the bot.":"group <span style=\"font-style:italic;\">".$_SESSION["user"]["chats"][$_SESSION["chat"]]["title"].".</span>" ?>
			<h2>Status</h2>		
			<p>The Twittelegram bot is currently <?=$_SESSION["user"]["chats"][$_SESSION["chat"]]["active"]?"<span style=\"font-weight:bold; color:green;\">running</span>":"<span style=\"font-weight:bold; color:red;\">stopped</span>"?> for this Telegram chat.</p>
			<p style="font-size:small; color:darkgray;">When running, the bot reads from Twitter lists that you have configure, and it sends tweets to the Telegram chat. Also, if properly configured, the bot allows you to post to Twitter directly from the Telegram chat. In end, the bot receives and executes configuration commands that you issue directly from your Telegram chat. When stopped, the bot does nothing of the above.</p>
			<?php if(!$_SESSION["user"]["chats"][$_SESSION["chat"]]["active"]) { ?><p><span style="<?=$detect->isMobile()?"padding: 0.5em; ":""?>color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='?action=start';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 100%; padding-top:0.5em;  font-weight:bold; <?=$detect->isMobile()?"margin:auto; font-size:xx-large;":""?>">Start bot</span></span></p><?php } ?>
			<?php if($_SESSION["user"]["chats"][$_SESSION["chat"]]["active"]) { ?><p><span style="<?=$detect->isMobile()?"padding: 0.5em; ":""?>color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='?action=stop';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 100%; padding-top:0.5em; font-weight:bold; <?=$detect->isMobile()?"margin:auto; font-size:xx-large;":""?>">Stop bot</span></span></p><?php } ?>
			<h2>Links</h2>
			<?php if(!empty(array_keys($_SESSION["user"]["chats"][$_SESSION["chat"]]["links"]))) { ?>
			<p>This Telegram chat is linked to the following Twitter profiles:</p>
			<table<?=$detect->isMobile()?" style=\"font-size:xx-large;\"":""?>>
				<?php foreach(array_keys($_SESSION["user"]["chats"][$_SESSION["chat"]]["links"]) as $link) { ?><tr><td><a href="https://twitter.com/<?=htmlentities($link)?>" title="<?=htmlentities($link)?>" style="color:#00aced;"><?=htmlentities($link)?></td><td><a style="color:red;" href="https://www.twittelegram.com/telegram/manage_chat.php?action=unlink&profile=<?=urlencode($link)?>" title="Unlink <?=htmlentities($link)?>">[unlink]</a></td></tr><?php } ?>
			</table>
			<?php } else { ?>
			<p>This Telegram chat is not linked to any Twitter profile.</p>
			<?php } ?>
			<p style="font-size:small; color:darkgray;">Linking a Twitter profile to your chat, you will be able to post to Twitter directly from the Telegram chat, on behalf of the linked profile. This way, the process of discussing about what to post, and posting, is made straightforward.</p>
			<p><span style="<?=$detect->isMobile()?"padding: 0.5em; ":""?>color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='?action=link';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 100%; padding-top:0.5em; font-weight:bold; <?=$detect->isMobile()?"margin:auto; font-size:xx-large;":""?>">Add a link</span></span></p>
			<h2>Readings</h2>	
			<?php if($stmt && $softParseErr && !$parseErr) { ?><p style="font-weight:bold; color:orange; font-size:small;"><?=$softParseErr?></p><?php } ?>
				<?php if(array_keys($_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"])) echo("<ul>"); ?>
			<?php foreach(array_keys($_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"]) as $reading) { ?>
				<li><?=ucfirst($_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"][$reading]["stmt"])."."?>&nbsp;<a href="?action=cancel&src=<?=urlencode($reading)?>" title="cancel" style="color:red;">[cancel]</a></li>
			<?php } ?>		
				<?php if(array_keys($_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"])) echo("</ul>"); ?>			
			<?php if(!array_keys($_SESSION["user"]["chats"][$_SESSION["chat"]]["readings"])) echo("<p>This chat is not receiving contents from any Twitter list or timeline.</p>"); ?>			
			
			<p><span style="<?=$detect->isMobile()?"padding: 0.5em; ":""?>color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="window.location.href='read.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 100%; padding-top:0.5em; font-weight:bold; <?=$detect->isMobile()?"margin:auto; font-size:xx-large;":""?>">Create new reading rule</span></span></p>						
			<h2>Configuration Home</h2>
			<p style="font-size:small;">From the Configuration Home, you can manage your subscription to the Twittelegram service (as a Telegram user), and you can browse to configuration pages of other Telegram chats that you manage.</p>
			<p><span style="<?=$detect->isMobile()?"padding: 0.5em; ":""?>color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='manage.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 100%; padding-top:0.5em; font-weight:bold; <?=$detect->isMobile()?"margin:auto; font-size:xx-large;":""?>">Configuration Home</span></span></p>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>

<?php
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Access to chat configuration.";	
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
} 
else 
{
?>

<!DOCTYPE html>
<html>
	<head>
		<title>twittelegram</title>
		<style>
			body {
				font-family: sans-serif;
				min-height: 100%;
				-webkit-background-size: cover;
				-moz-background-size: cover;
				-o-background-size: cover;
				background-size: cover;
				background: #00aced;
				background-repeat:no-repeat;
				background: -webkit-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: -moz-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: -ms-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: -o-linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
				background: linear-gradient( 90deg, #00aced 50%, #0088cc 50%);
			}
			
			div#container {
				background: white;
				padding: 1em;
				margin: 100px;
			}
			
			a {
				text-decoration: none;
				font-weight: bold;
				color: inherit;
			}

		</style>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced;">twit</span><span style="color: #0088cc; border: medium solid #0088cc; margin:0.1em; padding:0.1em;">telegram</span></h1>
			<h2>Session Expired</h2>
			<p>The provided access token is no longer valid.</p>
			<p>Try issuing the <strong>/settings</strong> command again from within your Telegram chat, or browse to the <a href="manage.php" title="manage.php">settings home</a>.</p>
			<p>If the problem does not solve, contact Mirco Soderi.</p>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>

<?php 
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Chat configuration failed due to an invalid token.";	
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
} ?>