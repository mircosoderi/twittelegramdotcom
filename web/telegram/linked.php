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
require_once("../lib/Mobile_Detect.php");
require_once("../lib/const.php");
$detect = new Mobile_Detect;
$status = $_SESSION["link_status"];
$linkedTelegram = htmlentities($_SESSION["linkedTelegram"]);
$linkedTwitter = htmlentities($_SESSION["linkedTwitter"]);
unset($_SESSION["link_status"]);
unset($_SESSION["linkedTelegram"]);
unset($_SESSION["linkedTwitter"]);
?><!DOCTYPE html>
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
			<?php if("OK" == $status) { ?>
				<h2>Link OK</h2>
				<p>The Telegram chat <strong><?=$linkedTelegram?></strong> is now linked to the Twitter profile <strong><?=$linkedTwitter?></strong>.</p>
			<?php } else if ("Already" == $status) { ?>
				<h2>Link already established</h2>
				<p>The Telegram chat <strong><?=$linkedTelegram?></strong> was already linked to the Twitter profile <strong><?=$linkedTwitter?></strong>.</p>						
				<p>No changes were found to be necessary to the configuration of nor the Twitter neither the Telegram account.</p>
				<p>If it sounds you wrong, please contact Mirco Soderi at <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
			<?php } else { ?>
				<h2>Link Error</h2>
				<p>The selected Twitter account may not have an active subscription to the Twittelegram service.</p>
				<p>Hit the button below to login to Twittelegram through Twitter and subscribe, then repeat the linking procedure from the beginning.</p>
				<p>If the problem does not solve, contact Mirco Soderi at <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
				<p><span style="padding: 0.5em; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='../twitter/login.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 14em; margin:auto; padding-top:0.5em;"><img src="../img/twitter_white.png" style="position:relative; top:-0.2em; height:1.7em;"><span style="position: relative; top:-0.7em; font-weight:bold; <?=$detect->isMobile()?"font-size:xx-large;":""?>">Log in with Twitter</span></span></span></p>
			<?php } ?>			
			<?php if($_SESSION["addBackLink"]) { ?>
			<p><span style="padding: 0.5em; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='https://www.twittelegram.com/telegram/manage_chat.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 14em; margin:auto; padding-top:0.5em; <?=$detect->isMobile()?"font-size:xx-large;":""?> font-weight:bold;">Back to home</span></span></p>
			<?php } ?>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>
<?php
	if($_SESSION["linkedTelegramUserID"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["linkedTelegramUserID"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["text"] = "Completed the procedure for the linking of chat $linkedTelegram to $linkedTwitter with status $status.";	
		$j["chats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents($TELEGRAMUSERSPATH.$_SESSION["linkedTelegramUserID"].".json",json_encode($j, JSON_PRETTY_PRINT));
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
	if($linkedTwitter) {
		$j = json_decode(file_get_contents("$TWITTERUSERSPATH$linkedTwitter.json"),true);
		$microtime = "".microtime(true);
		$j["linkedChats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["text"] = "Completed the procedure for the linking of chat $linkedTelegram to $linkedTwitter with status $status.";
		$j["linkedChats"][$_SESSION["linkedTelegramID"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents("$TWITTERUSERSPATH$linkedTwitter.json",json_encode($j, JSON_PRETTY_PRINT));
	}	
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
?>