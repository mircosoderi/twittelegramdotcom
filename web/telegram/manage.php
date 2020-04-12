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
 
require_once("../lib/Mobile_Detect.php");
require_once("../lib/const.php");

$start = microtime(true);
session_start();
session_regenerate_id();

$detect = new Mobile_Detect;

define('BOT_USERNAME', '@twittelegramdotcom_bot'); 
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

$id = $tg_user['id'];
$first_name = $tg_user['first_name'];
$last_name = $tg_user['last_name'];
$photo_url = $tg_user['photo_url'];

$datafile = [];
if(file_exists("$TELEGRAMUSERSPATH$id.json")) {
	$datafile = json_decode(file_get_contents("$TELEGRAMUSERSPATH$id.json"),true);
}
$datafile["id"] = $id;
$datafile["firstName"] = $first_name;
$datafile["lastName"] = $last_name;
$datafile["photoUrl"] = $photo_url;
file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));	

if($_GET["subscribe"] == "yes") {		
	
	require_once("../lib/ssh.php");
	if($datafile["lnx"] && lnxchk($datafile["lnx"]["usr"],$datafile["lnx"]["pwd"])) { 
		$_SESSION["unsubscribing"] = true;
		$microtime = "".microtime(true);
		$datafile["subscription"]["log"][$microtime]["text"] = "Subscription attempt failed. Unsubscription was in progress.";
		$datafile["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$datafile["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$datafile["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}		
		file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));
		header("Location: https://www.twittelegram.com/telegram/manage.php");	
		die();
	}
	else {
		if($_SESSION["unsubscribing"]) unset($_SESSION["unsubscribing"]);
	}
	$datafile["subscription"]["active"] = true;
	$datafile["lnx"]["usr"] = "ttusr".$id;
	$datafile["lnx"]["pwd"] = bin2hex(openssl_random_pseudo_bytes(16));
	
	$securefile = fopen($APIKEYSFILEPATH,"r");
	$securejson = fread($securefile, filesize($APIKEYSFILEPATH));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);	 
	addlnxusr($securekeys["lnxusr"],$securekeys["lnxpwd"],$datafile["lnx"]["usr"],$datafile["lnx"]["pwd"]); 
	
	$microtime = "".microtime(true);
	$datafile["subscription"]["log"][$microtime]["text"] = "Twittelegram service subscription";
	$datafile["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
	if($_SESSION["generic_browsing_cost"]) {
		$microtime = "".microtime(true);
		$datafile["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
		$datafile["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
		unset($_SESSION["generic_browsing_cost"]);
	}
	
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));	
	
	header("Location: https://www.twittelegram.com/telegram/manage.php");
	
	die();
}

if($_GET["unsubscribe"] == "yes") {
	$datafile["subscription"]["active"] = false;
	//unset($datafile["chats"]);
	
	$microtime = "".microtime(true);
	$datafile["subscription"]["log"][$microtime]["text"] = "Twittelegram service subscription canceled";
	$datafile["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
	if($_SESSION["generic_browsing_cost"]) {
		$microtime = "".microtime(true);
		$datafile["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
		$datafile["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
		unset($_SESSION["generic_browsing_cost"]);
	}
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));	
	
	require_once("../lib/ssh.php");
	$securefile = fopen($APIKEYSFILEPATH,"r");
	$securejson = fread($securefile, filesize($APIKEYSFILEPATH));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);	
	dellnxusr($securekeys["lnxusr"],$securekeys["lnxpwd"],"ttusr".$id);
	
	header("Location: https://www.twittelegram.com/telegram/manage.php");
	
	die();
}

if($_GET["register_contrib"] == "yes") {
	if(!$datafile["contributions"]) $datafile["contributions"] = [];
	$datafile["contributions"][] = array( 
		"date" => $_GET["contrib_year"]."-".$_GET["contrib_month"]."-".$_GET["contrib_day"],
		"amount" => $_GET["contrib_euro"].".".$_GET["contrib_cent"],
		"status" => "unverified"
	);
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));
	header("Location: https://www.twittelegram.com/telegram/manage.php");
	die();
}

if($_GET["cancel_contrib"] == "yes") {
	array_splice($datafile["contributions"], $_GET["contrib_no"], 1);
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));
	header("Location: https://www.twittelegram.com/telegram/manage.php");
	die();
}

if($_GET["action"] == "configure") {
	$datafile["chats"][$_GET["chat"]]["mgmtTmpPwd"] = uniqid();	
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($datafile, JSON_PRETTY_PRINT));	
	$j = json_decode(file_get_contents("$TELEGRAMUSERSPATH$id.json"),true);
	$microtime = "".microtime(true);
	$j["chats"][$_GET["chat"]]["log"][$microtime]["text"] = "Access to chat configuration";
	$j["chats"][$_GET["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
	if($_SESSION["generic_browsing_cost"]) {
		$microtime = "".microtime(true);
		$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
		$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
		unset($_SESSION["generic_browsing_cost"]);
	}
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($j, JSON_PRETTY_PRINT));	
	header("Location: https://www.twittelegram.com/telegram/manage_chat.php?tkn=".md5($datafile["chats"][$_GET["chat"]]["mgmtTmpPwd"]));	
	die();	
}
$subscribed = isset($datafile["subscription"]) && $datafile["subscription"]["active"];
 
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
			}
			
		</style>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced;">twit</span><span style="color: #0088cc; border: medium solid #0088cc; margin:0.1em; padding:0.1em;">telegram</span></h1>		
			<h2>Identity</h2>
			<p style="line-height:1.5;"><img src="<?=htmlentities($photo_url)?>" title="<?=htmlentities($first_name)?> <?=htmlentities($last_name)?>" style="float:left; height:3em; margin-right:1em;"><?=!$detect->isMobile()?"You are now logged in as<br>":""?><span style="<?=!$detect->isMobile()?"font-weight:bold;":""?> font-size:large;"><?=htmlentities($first_name)?> <?=htmlentities($last_name)?></span></p>			
			<?php if($_SESSION["unsubscribing"]) { ?>
			<h2 style="background-color:red; color:white;">Please wait...</h2>
			<p style="color:red;">Please wait while Twittelegram clears your account. It could take one minute or more. After that, you will be allowed to subscribe again. Thank you for your patience.</p>
			<?php } ?>
			<h2>Actions</h2>
			<?php if($subscribed) { ?>
				<p><a href="#groups" title="Groups">Manage Chats</a> - Configure Telegram chats that you have added.</p>			
				<p><a href="#unsubscribe" title="Unsubscribe">Unsubscribe</a> - You can unsubscribe from the service at any time.</p>	
				<p><a href="#usage" title="Usage">Usage and contribution</a> - Activity logs and your contribution to the project.</p>	
			<?php } else { ?>
				<p><a href="#subscribe" title="Subscribe">Subscribe</a> - Subscribing is the very first step for having Twitter contents sent to you via Telegram.</p>				
			<?php } ?>
			<?php if($subscribed) { ?>
			<h2>Manage Chats</h2>
			<a name="groups" id="groups"></a>
			<?php foreach(array_keys($datafile["chats"]) as $chat) { ?>
				<p><a href="?action=configure&chat=<?=$chat?>" title="<?=$datafile["chats"][$chat]["title"]?$datafile["chats"][$chat]["title"]:"Private chat"?>"><?=$datafile["chats"][$chat]["title"]?$datafile["chats"][$chat]["title"]:"<span style=\"font-style:italic;\">Your private chat with the bot</span>"?></a></p>
			<?php } ?>
			<?php if(!array_keys($datafile["chats"])) { ?>
				<p>No chats added until now.</p>
				<p style="text-decoration:underline;">Do one of the following to get started:</p>
				<ul style="line-height:2;">
				<li>To use Twittelegram in a Telegram group or channel that you administrate, add the <strong>@twittelegramdotcom_bot</strong> to the group and issue the <strong>/start</strong> command.</li>
				<li>To use Twittelegram privately, browse to the <a href="https://telegram.me/twittelegramdotcom_bot" title="@twittelegramdotcom_bot">bot Web page</a> with your mobile phone and start a chat with the bot.</li>
				</ul>
			<?php } ?>
			<h2>Unsubscribe</h2>
			<a name="unsubscribe" id="unsubscribe"></a>
			<p>You can unsubscribe from the service at any time.</p>
			<p>If there is a specific reason for which you leave, I would be glad if you could tell me of that at <a href="mailto:mirco.soderi@gmail.com" title="mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
			<p onclick="document.location.href='?unsubscribe=yes';" style=" cursor: pointer; background: rgba(0,172,237,0.5); padding: 1em;"><span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center; ">UNSUBSCRIBE NOW</span></p>						
			<h2>Usage and contribution</h2>
			<a name="usage" id="usage"></a>
			<p>In this section, you can find detailed activity logs, and your recommended and actual contribution.</p>
			<?php 
				$recommended = 0;
				$pdfs = [];
				foreach(array_keys($datafile["subscription"]["log"]) as $entry) {
					if((!empty(number_format($datafile["subscription"]["log"][$entry]["cost"],5))) || !empty(number_format($datafile["subscription"]["log"][$entry]["cost"],5))) {
						if($datafile["subscription"]["log"][$entry]["cost"]) $recommended += $datafile["subscription"]["log"][$entry]["cost"];
						if($datafile["subscription"]["log"][$entry]["cst"]) $recommended += $datafile["subscription"]["log"][$entry]["cst"];
						$micro = sprintf("%06d",($entry - floor($entry)) * 1000000);
						$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $entry) );		
						$Y = $d->format("Y");					
						$F = $d->format("F");					
						if(!$pdfs[$Y]) $pdfs[$Y] = [];
						if(!$pdfs[$Y][$F]) $pdfs[$Y][$F] = [];
						if($datafile["subscription"]["log"][$entry]["text"]) $pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"General",$datafile["subscription"]["log"][$entry]["text"],number_format($datafile["subscription"]["log"][$entry]["cost"],5));
						if($datafile["subscription"]["log"][$entry]["txt"]) $pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"General",$datafile["subscription"]["log"][$entry]["txt"],number_format($datafile["subscription"]["log"][$entry]["cst"],5));
					}
				}
				foreach(array_keys($datafile["chats"]) as $chat) {
					foreach(array_keys($datafile["chats"][$chat]["log"]) as $entry) {
						if((!empty(number_format($datafile["chats"][$chat]["log"][$entry]["cost"],5))) || (!empty(number_format($datafile["chats"][$chat]["log"][$entry]["cst"],5)))) {
							if($datafile["chats"][$chat]["log"][$entry]["cost"]) $recommended += $datafile["chats"][$chat]["log"][$entry]["cost"];
							if($datafile["chats"][$chat]["log"][$entry]["cst"]) $recommended += $datafile["chats"][$chat]["log"][$entry]["cst"];
							$micro = sprintf("%06d",($entry - floor($entry)) * 1000000);
							$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $entry) );		
							$Y = $d->format("Y");					
							$F = $d->format("F");					
							if(!$pdfs[$Y]) $pdfs[$Y] = [];
							if(!$pdfs[$Y][$F]) $pdfs[$Y][$F] = []; 
							if($datafile["chats"][$chat]["log"][$entry]["text"]) $pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"Chats","#$chat ".$datafile["chats"][$chat]["log"][$entry]["text"],number_format($datafile["chats"][$chat]["log"][$entry]["cost"],5));							
							if($datafile["chats"][$chat]["log"][$entry]["txt"]) {
								$datafile["chats"][$chat]["log"][$entry]["ref"] = $datafile["chats"][$chat]["log"][$entry]["ref"] ? " - ".$datafile["chats"][$chat]["log"][$entry]["ref"] : $datafile["chats"][$chat]["log"][$entry]["ref"];
								$datafile["chats"][$chat]["log"][$entry]["txt"] = $datafile["chats"][$chat]["log"][$entry]["txt"] ? " - ".$datafile["chats"][$chat]["log"][$entry]["txt"] : $datafile["chats"][$chat]["log"][$entry]["txt"];
								$datafile["chats"][$chat]["log"][$entry]["att"] = $datafile["chats"][$chat]["log"][$entry]["att"] ? " - ".$datafile["chats"][$chat]["log"][$entry]["att"] : $datafile["chats"][$chat]["log"][$entry]["att"];
								$datafile["chats"][$chat]["log"][$entry]["rpl"] = $datafile["chats"][$chat]["log"][$entry]["rpl"] ? " - ".$datafile["chats"][$chat]["log"][$entry]["rpl"] : $datafile["chats"][$chat]["log"][$entry]["rpl"];							
								$pdfs[$Y][$F][] = array($d->format("Y-m-d H:i:s"),"Chats","#$chat ".$datafile["chats"][$chat]["log"][$entry]["ref"].$datafile["chats"][$chat]["log"][$entry]["txt"].$datafile["chats"][$chat]["log"][$entry]["att"].$datafile["chats"][$chat]["log"][$entry]["rpl"],number_format($datafile["chats"][$chat]["log"][$entry]["cst"],5));						
							}
						}
					}
				}
				$_SESSION["pdfs"] = $pdfs;
			?>
			<h3>Recommended total contribution</h3>
			<p><?=number_format($recommended,2) ?> &euro;</p>	
			<h3>Send a contribution now</h3>
			<p>Browse to <a href="https://www.paypal.me/mircosoderi" title="https://www.paypal.me/mircosoderi">https://www.paypal.me/mircosoderi</a>.</p>
			<p>Please be whimsical, offer 97.43 &euro;, not 100.00 &euro; &#128536;</p>
			<p>Verification is made manually, time by time.</p>
			<p>Then, use the form below to register your contribution.</p>	
			<form method="get" action="https://www.twittelegram.com/telegram/manage.php"><input type="hidden" name="register_contrib" value="yes">Register my contribution of <input type="text" size="4" id="contrib_euro" name="contrib_euro">.<input type="text" size="2" id="contrib_cent" name="contrib_cent"> &euro; made on <input type="text" size="4" name="contrib_year" id="contrib_year" value="<?=date("Y")?>">-<input type="text" size="2" name="contrib_month" id="contrib_month" value="<?=date("m")?>">-<input type="text" size="2" name="contrib_day" id="contrib_day" value="<?=date("d")?>">.&nbsp;<input type="submit" value="Register"></form>
			<h3>The history of your contributions</h3>
			<?php
			$ci = 0;
			foreach($datafile["contributions"] as $contribution) {
				$status = $contribution["status"];
				if($status == "unverified") $color = "black"; 
				if($status == "verified") $color = "green";
				if($status == "fake") $color = "red";				
				echo("<p style=\"color: $color;".($color != "black"?"font-weight: bold !important;":"font-weight: normal !important;")."\">On ".$contribution["date"].", ".$contribution["amount"]." &euro; ".($status != "verified"?" <a ".($color == "black"?"style=\"font-weight: normal;\" ":"")."href=\"?cancel_contrib=yes&contrib_no=$ci\" title=\"cancel\">[delete]</a>":"")."</p>");			
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
			<?php } else { ?>
			<h2>Subscribe</h2>
			<a name="subscribe" id="subscribe"></a>
			<p>Subscribing is the very first step for having Twitter contents sent to you via Telegram.</p>
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
	$j = json_decode(file_get_contents("$TELEGRAMUSERSPATH$id.json"),true);
	$microtime = "".microtime(true);
	$j["subscription"]["log"][$microtime]["text"] = "Access to account configuration";
	$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
	if($_SESSION["generic_browsing_cost"]) {
		$microtime = "".microtime(true);
		$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
		$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
		unset($_SESSION["generic_browsing_cost"]);
	}
	file_put_contents("$TELEGRAMUSERSPATH$id.json",json_encode($j, JSON_PRETTY_PRINT));	
} 
?>

<?php if($tg_user === false) { ?>
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
			<h2>Login Failed</h2>
			<p>It was not possible to authenticate you as a Telegram user.</p>
			<p>Browse back to the <a href="https://www.twittelegram.com/" title="twittelegram">Twittelegram Homepage</a> and try again.</p>
			<p>If the problem does not solve, contact Mirco Soderi at <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>.</p>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>
<?php
if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
?>
<?php } ?>