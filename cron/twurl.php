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
 
$cfg = "---\nprofiles:\n";
$telegramUserFile = $argv[1].".json";
$telegramUserCfg = json_decode(file_get_contents("/path/to/telegram/users/folder/$telegramUserFile"),true);
$keys = json_decode(file_get_contents("/path/to/keys/file.json"),true);
$apiKey = $keys["apiKey"];
$apiSecretKey = $keys["apiSecretKey"];
$defaultuser = null;
foreach($telegramUserCfg["chats"] as $chat) {
	if(array_key_exists("links",$chat)) foreach(array_keys($chat["links"]) as $link) {
		$oauthtoken = $chat["links"][$link]["oauth_token"];
		$oauthtokensecret = $chat["links"][$link]["oauth_token_secret"];
		$cfg.="  $link:\n";
		$cfg.="    $apiKey:\n";
		$cfg.="      username: $link\n";
		$cfg.="      consumer_key: $apiKey\n";
		$cfg.="      consumer_secret: $apiSecretKey\n";
		$cfg.="      token: $oauthtoken\n";
		$cfg.="      secret: $oauthtokensecret\n";
		$defaultuser = $link;
		file_put_contents("/home/ttusr".$argv[1]."/ttenv/users/twitter/$link.json",file_get_contents("/path/to/twitter/users/folder/$link.json"));
	}
}
if($defaultuser) {
	$cfg.="configuration:\n";
	$cfg.="  default_profile:\n";
	$cfg.="  - $defaultuser\n";
	$cfg.="  - $apiKey";
	file_put_contents("/home/ttusr".$argv[1]."/.twurlrc.new",$cfg);
}
?>
