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
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
$datafile = "";
if(file_exists($TWITTERUSERSPATH.$_SESSION["screen_name"].".json")) {		
	$datafile = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
	$datafile["oauth_token"] = "***obfuscated***";
	$datafile["oauth_token_secret"] = "***obfuscated***";
	header('Content-Type: application/octet-stream');
	header("Content-Transfer-Encoding: Binary"); 
	header("Content-disposition: attachment; filename=\"".$_SESSION["screen_name"].".json\""); 
	echo(str_replace("\"cost\": ","\"weight\": ",json_encode($datafile,JSON_PRETTY_PRINT)));
	$microtime = "".microtime(true);
	$j = json_decode(file_get_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json"),true);
	$j["subscription"]["log"][$microtime]["text"] = "Download of integral data file";
	$j["subscription"]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
	file_put_contents($TWITTERUSERSPATH.$_SESSION["screen_name"].".json",json_encode($j, JSON_PRETTY_PRINT));
	die();
}
header("HTTP/1.1 404 Not Found");
die();
 ?>