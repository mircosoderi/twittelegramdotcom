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
require_once("../lib/Mobile_Detect.php");
require_once("../lib/const.php");
$detect = new Mobile_Detect;
if(!$_SESSION["screen_name"]) {
	session_destroy();
	header("Location: https://www.twittelegram.com/");	
	if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
	$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + abs(microtime(true) - $start);
	die();
}
if($_GET["async"] == "yes") {		
	$key = $_GET["key"];
	$val = $_GET["val"];
	$keyp = explode("_",$key);
	$list = $keyp[1];
	$action = $keyp[2];
	$logmex = "";
	if($action == "whopays") {
		$datafile = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$datafile["lists"][$list]["whopays"] = $val;
		file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
		$logmex = "Set $action equal to $val.";
		header('HTTP/1.1 200 OK');		
	}
	else if($action == "whocanread") {
		$datafile = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$datafile["lists"][$list]["whocanread"] = $val;
		file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
		$logmex = "Set $action equal to $val.";
		header('HTTP/1.1 200 OK');
	}
	else if($action == "r") {
		$datafile = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);	
		$datafile["lists"][$list]["wblisted"][$keyp[3]] = $val;
		file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));
		$logmex = "Chat # ".$keyp[3].($val?" is ":" is not ")."wblisted.";
		header('HTTP/1.1 200 OK');
	}
	else {		
		$logmex = "Set UNEXPECTED $action equal to $val. Error returned.";
		header('HTTP/1.1 500 Internal Server Error');		
	}
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["lists"][$list]["log"][$microtime]["text"] = $logmex;
		$j["lists"][$list]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
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
if(!isset($_SESSION["antihijacking"])) {
	session_destroy();
	header("Location: https://www.twittelegram.com/");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Antihijacking check failed at management page.";
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
if(!isset($_SESSION["antihijacking"]["enforce"])) {
	$_SESSION["antihijacking"] = array_intersect_assoc($_SESSION["antihijacking"], $_SERVER);
	$_SESSION["antihijacking"]["enforce"] = true;
}
else {
	foreach ($_SESSION["antihijacking"] as $key => $value) {
		if($key != "enforce" && $_SERVER[$key] != $value) {
			session_destroy();
			header("Location: https://www.twittelegram.com/");
			if($_SESSION["screen_name"]) {
				$microtime = "".microtime(true);
				$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
				$j["subscription"]["log"][$microtime]["text"] = "Antihijacking enforcing failed at management page.";
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
	}
	
}
if(isset($_SESSION["action"])) {
	$action = $_SESSION["action"];
	$action_status = $_SESSION["action_status"];
	$action_comment = $_SESSION["action_comment"];
	unset($_SESSION["action"]);
	unset($_SESSION["action_status"]);
	unset($_SESSION["action_comment"]);
}

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
$datafile = "";
if(file_exists($TWITTERUSERSPATH.$_SESSION["screen_name"].".json")) {
		
	$datafile = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
}
if($_GET["subscribe"] == "yes") {	
	$_SESSION["twitterSubscribe"] = "yes";
	header("Location: https://www.twittelegram.com/twitter/login.php");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Service subscription request.";
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

if($_SESSION["twitterSubscribe"] == "yes") {
	unset($_SESSION["twitterSubscribe"]);
	$datafile["subscription"]["active"] = true;
	file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/twitter/manage.php");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Successful service subscription.";
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

if($_GET["unsubscribe"] == "yes") {
	$datafile["subscription"]["active"] = false;
	unset($datafile["oauth_token"]);
	unset($datafile["oauth_token_secret"]);
	foreach(array_keys($datafile["linkedChats"]) as $linkedChat) {		
		foreach($datafile["linkedChats"][$linkedChat]["telegramUser"] as $telegramUser) {
			$telegramData = json_decode(file_get_contents("$TELEGRAMUSERSPATH$telegramUser.json"),true);
			foreach(array_keys($telegramData["chats"]) as $chat) {
				unset($telegramData["chats"][$chat]["links"][$datafile["screen_name"]]);
			}		
			file_put_contents("$TELEGRAMUSERSPATH$telegramUser.json",json_encode($telegramData, JSON_PRETTY_PRINT));	
		}
	}
	file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/twitter/manage.php");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["subscription"]["log"][$microtime]["text"] = "Service subscription canceled.";
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

if($_GET["register_contrib"] == "yes") {
	if(!$datafile["contributions"]) $datafile["contributions"] = [];
	$datafile["contributions"][] = array( 
		"date" => $_GET["contrib_year"]."-".$_GET["contrib_month"]."-".$_GET["contrib_day"],
		"amount" => $_GET["contrib_euro"].".".$_GET["contrib_cent"],
		"status" => "unverified"
	);
	file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/twitter/manage.php");
	die();
}

if($_GET["cancel_contrib"] == "yes") {
	array_splice($datafile["contributions"], $_GET["contrib_no"], 1);
	file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/twitter/manage.php");
	die();
}
		
$updateLists = false;
foreach(array_keys($datafile["lists"]) as $listID) {
	if(isset($_GET["list_$listID"]) && $datafile["lists"][$listID]["access"] != $_GET["list_$listID"]) {
		$datafile["lists"][$listID]["access"] = $_GET["list_$listID"];
		$logmex = "Set access equal to ".$_GET["list_$listID"];
		$loglist = $listID;
		$updateLists = true;
	}
}
if($updateLists) {
	file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($datafile, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/twitter/manage.php");
	if($_SESSION["screen_name"]) {
		$microtime = "".microtime(true);
		$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
		$j["lists"][$loglist]["log"][$microtime]["text"] = $logmex;
		$j["lists"][$loglist]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
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

$subscribed = isset($datafile["subscription"]) && $datafile["subscription"]["active"];

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
			table#premium tbody tr:nth-child(odd) td {
				font-weight:bold; 
			}
			h1 {
				cursor: pointer;
			}
			a {
				text-decoration: none;
				font-weight: bold;
				color: inherit;
			}
			
			.switch {
			  position: relative;
			  display: inline-block;
			  width: 60px;
			  height: 34px;
			}

			.switch input { 
			  opacity: 0;
			  width: 0;
			  height: 0;
			}

			.slider {
			  position: absolute;
			  cursor: pointer;
			  top: 0;
			  left: 0;
			  right: 0;
			  bottom: 0;
			  background-color: darkgray;
			  -webkit-transition: .4s;
			  transition: .4s;
			}

			.slider:before {
			  position: absolute;
			  content: "";
			  height: 26px;
			  width: 26px;
			  left: 4px;
			  bottom: 4px;
			  background-color: white;
			  -webkit-transition: .4s;
			  transition: .4s;
			}

			input:checked + .slider {
			  background-color: #00aced;
			}

			input:focus + .slider {
			  box-shadow: 0 0 1px #00aced;
			}

			input:checked + .slider:before {
			  -webkit-transform: translateX(26px);
			  -ms-transform: translateX(26px);
			  transform: translateX(26px);
			}

			/* Rounded sliders */
			.slider.round {
			  border-radius: 34px;
			}

			.slider.round:before {
			  border-radius: 50%;
			}
			
			h2 {
				border: medium solid black;
				padding:0.2em;
				color: black;
				background-color: #00aced;
			}
		
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script>
			$(function() {
				<?php foreach(array_keys($datafile["lists"]) as $listID) { ?>
					$("#list_<?=htmlentities($listID)?>_whopays").val("<?=$datafile["lists"][$listID]["whopays"]?>");
					$("#list_<?=htmlentities($listID)?>_whocanread").val("<?=$datafile["lists"][$listID]["whocanread"]?>");
					<?php foreach(array_keys($datafile["lists"][$listID]["requesters"]) as $requester) { ?>						
						$("#list_<?=htmlentities($listID)?>_r_<?=$requester?>").prop("checked",<?=$datafile["lists"][$listID]["wblisted"][$requester] == "on"?"true":"false"?>);
					<?php } ?>					
				<?php } ?>
			});
		</script>
		<script>
			function update(e) {
				jQuery.ajax({
					url: 'manage.php?async=yes&key='+e.name+'&val='+e.value,
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						alert('Twittelegram Error: some of your recent configurations were not stored. Please reload this page and double check each of them. If the problem does not solve, contact Mirco Soderi at mirco.soderi@gmail.com.');
					}
				});
			}
			function updateWblist(e) {
				jQuery.ajax({
					url: 'manage.php?async=yes&key='+e.name+'&val='+(e.checked?'on':'off'),
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						alert('Twittelegram Error: some of your recent configurations were not stored. Please reload this page and double check each of them. If the problem does not solve, contact Mirco Soderi at mirco.soderi@gmail.com.');
					}
				});
			}
		</script>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced; border:medium solid #00aced; margin:0.1em; padding:0.1em;">twit</span><span style="color: #0088cc; ">telegram</span></h1>
			<?php if(isset($action)) { ?>
				<h2 style="color: <?=$action_status == "OK" ? "green" : "red" ?>"><?=htmlentities($action)?></h2>
				<p style="color: <?=$action_status == "OK" ? "green" : "red" ?>"><?=htmlentities($action_comment)?></p>
			<?php } ?>			
			<h2>What is this page for</h2>
			<p>Welcome in the Twittelegram configuration page dedicated to Twitter users. From here within, you can activate and deactivate your subscription at any time in one click, and most of all you can decide for each of your Twitter lists and for your timeline, if it can be accessed or not by the Twittelegram service, and who of the Telegram users that are interested in your contents can actually get them. Also, you can decide if tweets that are delivered from each of your Twitter lists should increase your recommended monetary contribution to the project, or if they should instead increase the recommended monetary contribution of Telegram users that ask for your contents. Obviously, in any case, the recommended monetary contribution is nothing more than a soft indication based on the workload generated by your account, that you can choose to take into consideration, or not.</p>
			<h2>Identity</h2>
			<p>You are now logged in as <strong><?=htmlentities($_SESSION["screen_name"])?></strong>.</p>			
			<h2>Actions</h2>
			<ul>
			<?php if($subscribed) { ?>
			<li><a href="#lists" title="Lists">Manage Lists</a> - Select lists that you wish to make available to Telegram users.</li>			
			<li><a href="#unsubscribe" title="Unsubscribe">Unsubscribe</a> - You can unsubscribe from the service at any time.</li>			
			<?php } else { ?>
			<li><a href="#subscribe" title="Subscribe">Subscribe</a> - Subscribing is the very first step for making a selection of your contents available to Telegram users.</li>
			<?php } ?>
			</ul>
			<?php if($subscribed) { ?>
			<h2>Manage Lists</h2>
			<a name="lists" id="lists"></a>
			<p>Below here are your Twitter lists. By default, all of them are grayed, therefore not accessible from Telegram.</p>
			<p>Operate the switches on the left to enable those lists that you wish to make accessible from Telegram.</p>
			<p>The <span style="font-style:italic;">Timeline</span> is a pseudo-list that includes all tweets that appear in your Twitter timeline (homepage).</p>

			<?php foreach(array_keys($datafile["lists"]) as $listID) { ?>
					<?php if($datafile["lists"][$listID]["access"] == "granted") { ?>
						<label class="switch">
						  <input onchange="document.location.href='?list_<?=htmlentities($listID)?>=denied';" name="list_<?=htmlentities($listID)?>" value="granted" type="checkbox" checked>
						  <span class="slider round"></span>
						</label>
						&nbsp;<a style="<?=$datafile["lists"][$listID]["name"] == "Timeline" ? "font-style:italic; ":"" ?>color: #00aced; font-size:xx-large; position: relative; top:0.5em;" href="https://twitter.com<?=htmlentities($datafile["lists"][$listID]["uri"]?$datafile["lists"][$listID]["uri"]:"/".$_SESSION["screen_name"]) ?>" title="<?=htmlentities($datafile["lists"][$listID]["name"])?>"><?=htmlentities($datafile["lists"][$listID]["name"])?></a>
						<p>Traffic from this list increases recommended contribution of...</p><p><select onchange="update(this);" style="border: thin solid darkgray; <?=$detect->isMobile()?"font-size:xx-large;":""?>" id="list_<?=htmlentities($listID)?>_whopays" name="list_<?=htmlentities($listID)?>_whopays"><option value="consumer">Telegram users</option><option value="producer">Me, the list owner</option></select></p>
						<p>Who can receive contents?</p><p><select onchange="update(this); if(this.value == 'everybody') $('#list_<?=htmlentities($listID)?>_requesters').hide(); else $('#list_<?=htmlentities($listID)?>_requesters').show(); " style="border: thin solid darkgray; <?=$detect->isMobile()?"font-size:xx-large;":""?>" id="list_<?=htmlentities($listID)?>_whocanread" name="list_<?=htmlentities($listID)?>_whocanread"><option value="everybody">All chats</option><option value="nonblacklisted">All chats apart those checked here below</option><option value="whitelisted">Only chats that are checked here below</option></select></p>
						<?php if(array_keys($datafile["lists"][$listID]["requesters"])) { ?><div id="list_<?=htmlentities($listID)?>_requesters" style="<?=$datafile["lists"][$listID]["whocanread"] == "everybody"?"display:none; ":""?> line-height:2; font-size:small;"><?php foreach(array_keys($datafile["lists"][$listID]["requesters"]) as $requesterID) { echo("<span style=\"white-space:nowrap;\"><input onchange=\"updateWblist(this);\" type=\"checkbox\" name=\"list_".$listID."_r_".$requesterID."\" id=\"list_".$listID."_r_".$requesterID."\">".$datafile["lists"][$listID]["requesters"][$requesterID]."&nbsp;&nbsp;&nbsp;</span><br>"); }?></div><?php } ?>
						<?php if(!array_keys($datafile["lists"][$listID]["requesters"])) { ?><div id="list_<?=htmlentities($listID)?>_requesters" style="<?=$datafile["lists"][$listID]["whocanread"] == "everybody"?"display:none; ":""?>">No chats are subscribed to this list at the moment.</div><?php } ?>
					<?php } else { ?>
						<label class="switch">
						  <input onchange="document.location.href='?list_<?=htmlentities($listID)?>=granted';" name="list_<?=htmlentities($listID)?>" value="denied" type="checkbox">
						  <span class="slider round"></span>
						</label>
						&nbsp;<a style="<?=$datafile["lists"][$listID]["name"] == "Timeline" ? "font-style:italic; ":"" ?>color: darkgray; font-size:xx-large; position: relative; top:0.5em;" href="https://www.twitter.com<?=htmlentities($datafile["lists"][$listID]["uri"]?$datafile["lists"][$listID]["uri"]:"/".$_SESSION["screen_name"]) ?>" title="<?=htmlentities($datafile["lists"][$listID]["name"])?>"><?=htmlentities($datafile["lists"][$listID]["name"])?></a><br>
					<?php } ?>					
			<?php } ?>
			
			<h2>Usage and contribution</h2>
			<a name="usage" id="usage"></a>
			<p>In this section, you can find detailed activity logs, and your recommended and actual contribution. Make Paypal donations through the button at the end of this section if you wish to contribute for that the service could stay alive and improve over the time.</p>
			<?php 
				$recommended = 0;
				$pdfs = [];
				foreach(array_keys($datafile["subscription"]["log"]) as $entry) {
					if((!empty(number_format($datafile["subscription"]["log"][$entry]["cost"],5))) || !empty(number_format($datafile["subscription"]["log"][$entry]["cost"],5))) {
						if($datafile["subscription"]["log"][$entry]["cost"]) $recommended += $datafile["subscription"]["log"][$entry]["cost"];						
						$micro = sprintf("%06d",($entry - floor($entry)) * 1000000);
						$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $entry) );		
						$Y = $d->format("Y");					
						$F = $d->format("F");					
						if(!$pdfs[$Y]) $pdfs[$Y] = [];
						if(!$pdfs[$Y][$F]) $pdfs[$Y][$F] = [];
						if($datafile["subscription"]["log"][$entry]["text"]) $pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"General",$datafile["subscription"]["log"][$entry]["text"],number_format($datafile["subscription"]["log"][$entry]["cost"],5));						
					}
				}
				foreach(array_keys($datafile["lists"]) as $list) {
					foreach(array_keys($datafile["lists"][$list]["log"]) as $entry) {
						if((!empty(number_format($datafile["lists"][$list]["log"][$entry]["cost"],5))) || !empty(number_format($datafile["lists"][$list]["log"][$entry]["cost"],5))) {
							if($datafile["lists"][$list]["log"][$entry]["cost"]) $recommended += $datafile["lists"][$list]["log"][$entry]["cost"];							
							$micro = sprintf("%06d",($entry - floor($entry)) * 1000000);
							$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $entry) );		
							$Y = $d->format("Y");					
							$F = $d->format("F");					
							if(!$pdfs[$Y]) $pdfs[$Y] = [];
							if(!$pdfs[$Y][$F]) $pdfs[$Y][$F] = [];
							if($datafile["lists"][$list]["log"][$entry]["text"]) $pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"Lists","#$list ".$datafile["lists"][$list]["log"][$entry]["text"],number_format($datafile["lists"][$list]["log"][$entry]["cost"],5));							
						}
					}
				}
				foreach(array_keys($datafile["linkedChats"]) as $chat) {
					foreach(array_keys($datafile["linkedChats"][$chat]["log"]) as $entry) {
						if((!empty(number_format($datafile["linkedChats"][$chat]["log"][$entry]["cost"],5))) || !empty(number_format($datafile["linkedChats"][$chat]["log"][$entry]["cost"],5))) {
							if($datafile["linkedChats"][$chat]["log"][$entry]["cost"]) $recommended += $datafile["linkedChats"][$chat]["log"][$entry]["cost"];							
							$micro = sprintf("%06d",($entry - floor($entry)) * 1000000);
							$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $entry) );		
							$Y = $d->format("Y");					
							$F = $d->format("F");					
							if(!$pdfs[$Y]) $pdfs[$Y] = [];
							if(!$pdfs[$Y][$F]) $pdfs[$Y][$F] = [];
							if($datafile["linkedChats"][$chat]["log"][$entry]["text"]) $pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"Chats","#$chat ".$datafile["linkedChats"][$chat]["log"][$entry]["text"],number_format($datafile["linkedChats"][$chat]["log"][$entry]["cost"],5));							
						}
					}
				}

				$_SESSION["pdfs"] = $pdfs;
			?>
			<h3>Recommended contribution</h3>
			<p>EUR <?=number_format($recommended,2) ?></p>	
			<h3>Send a contribution now</h3>
			<p>Browse to <a href="https://www.paypal.me/mircosoderi" title="https://www.paypal.me/mircosoderi">https://www.paypal.me/mircosoderi</a>.</p>
			<p>Please be whimsical, offer 97.43 &euro;, not 100.00 &euro; &#128536;</p>
			<p>Verification is made manually, time by time.</p>
			<p>Then, use the form below to register your contribution.</p>	
			<form method="get" action="https://www.twittelegram.com/twitter/manage.php"><input type="hidden" name="register_contrib" value="yes">Register my contribution of <input type="text" size="4" id="contrib_euro" name="contrib_euro">.<input type="text" size="2" id="contrib_cent" name="contrib_cent"> &euro; made on <input type="text" size="4" name="contrib_year" id="contrib_year" value="<?=date("Y")?>">-<input type="text" size="2" name="contrib_month" id="contrib_month" value="<?=date("m")?>">-<input type="text" size="2" name="contrib_day" id="contrib_day" value="<?=date("d")?>">.&nbsp;<input type="submit" value="Register"></form>
			<h3>The history of your contributions</h3>
			<?php
			$ci = 0;
			foreach($datafile["contributions"] as $contribution) {
				$status = $contribution["status"];
				if($status == "unverified") $color = "black"; 
				if($status == "verified") $color = "green";
				if($status == "fake") $color = "red";				
				echo("<p style=\"color: $color;".($color != "black"?"font-weight: bold;":"")."\">On ".$contribution["date"].", ".$contribution["amount"]." &euro; ".($status != "verified"?" <a ".($color == "black"?"style=\"font-weight:normal;\" ":"")."href=\"?cancel_contrib=yes&contrib_no=$ci\" title=\"cancel\">[delete]</a>":"")."</p>");			
				$ci++;
			}
			if(!$datafile["contributions"]) { echo("<p>It is so short mate &#128549;</p>"); }
			?>
			<h3>Activity logs</h3>			
			<?php 
				foreach(array_keys($pdfs) as $year) {
						echo("<strong>$year</strong> ");
						foreach(array_keys($pdfs[$year]) as $month) {
								echo("<a style=\"font-weight:normal;\" href=\"log.php?Y=$year&F=$month\" title=\"Actitivy log $month $year\">$month</a> ");
						}
				}
			?>			

			
			<h2>Unsubscribe</h2>
			<a name="unsubscribe" id="unsubscribe"></a>
			<p>You can unsubscribe from the service at any time.</p>
			<p>If there is a specific reason for which you leave, I would be glad if you could tell me of that at <a href="mailto:mirco.soderi@gmail.com" title="mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
			<p onclick="document.location.href='?unsubscribe=yes';" style=" cursor: pointer; background: rgba(0,172,237,0.5); padding: 1em;"><span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center; ">UNSUBSCRIBE NOW</span></p>						
			<?php } else { ?>
			<h2>Subscribe</h2>
			<a name="subscribe" id="subscribe"></a>
			<p>Subscribing is the very first step for making a selection of your contents available to Telegram users.</p>
			<p>Be sure that you have read, understood and that you agree with the <a href="../terms.php" title="Terms and conditions">Twittelegram Terms and conditions</a> and <a href="../privacy.php" title="Privacy policy">Privacy policy</a> before subscribing.</p>
			<p>If you already have subscribed and you are still asked to do that, contact Mirco Soderi at <a href="mailto:mirco.soderi@gmail.com" title="mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
			<p onclick="document.location.href='?subscribe=yes';" style="cursor: pointer; background: rgba(0,172,237,0.5); padding: 1em;"><span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center; ">SUBSCRIBE NOW</span></p>
			<?php } ?>
			<h2>Download</h2>
			<p>Click <a href="download.php" title="download.php">here</a> to download the integral data file about you. Authentication tokens and Linux credentials are obfuscated for security reasons.</p>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>
		</div>
	</body>
</html>
<?php
if($_SESSION["screen_name"]) {
	$microtime = "".microtime(true);
	$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
	$j["subscription"]["log"][$microtime]["text"] = "Loading of management page.";
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
?>