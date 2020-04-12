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
				if(array_key_exists("linkTmpPwd",$candidate["chats"][$chat_id]) && $tkn == md5($candidate["chats"][$chat_id]["linkTmpPwd"])) {
					unset($candidate["chats"][$chat_id]["linkTmpPwd"]);
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

if(getData(filter_input(INPUT_GET, 'tkn', FILTER_SANITIZE_URL))) {

// manage

  
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

		</style>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced;">twit</span><span style="color: #0088cc; border: medium solid #0088cc; margin:0.1em; padding:0.1em;">telegram</span></h1>
			<h2>Link</h2>
			<p>Hit the button below and log in to the Twitter profile to be linked.</p>
			<p>The Twitter profile will be linked to this Telegram chat:</p>
			<table style="font-family:monospace; <?=$detect->isMobile()?"font-size:xx-large;":""?>">
				<tr><td style="font-weight:bold;">ID</td><td><?=htmlentities($_SESSION["chat"])?></td></tr>
				<tr><td style="font-weight:bold;">Type</td><td><?=htmlentities($_SESSION["user"]["chats"][$_SESSION["chat"]]["type"]) != "private" ? htmlentities($_SESSION["user"]["chats"][$_SESSION["chat"]]["type"]) : "private chat"?></td></tr>
				<tr><td style="font-weight:bold;">Title</td><td><?=htmlentities($_SESSION["user"]["chats"][$_SESSION["chat"]]["title"])?htmlentities($_SESSION["user"]["chats"][$_SESSION["chat"]]["title"]):"<span style=\"font-style:italic;\">Untitled</span>"?></td></tr>
			</table>
			<p><span style="padding: 0.5em; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='../twitter/link.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 14em;  padding-top:0.5em; <?=$detect->isMobile()?"font-size:xx-large;":""?> font-weight:bold;">Login with Twitter</span></span></p>
			<?php if($_SESSION["addBackLink"]) { ?>
			<p><span style="padding: 0.5em; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='https://www.twittelegram.com/telegram/manage_chat.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 14em; padding-top:0.5em; <?=$detect->isMobile()?"font-size:xx-large;":""?> font-weight:bold;">Back to home</span></span></p>
			<?php } ?>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>

<?php
	if($_SESSION["user"]["id"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Link initialization.";	
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
} 
else // chat not found
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
			<h2>Access denied</h2>
			<p>The one-time password you used is no longer valid.</p>
			<p>Try issuing the <strong>/link</strong> command again.</p>
			<p>If the problem does not solve, contact Mirco Soderi at <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>

<?php 
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Link initialization failed.";	
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
} ?>