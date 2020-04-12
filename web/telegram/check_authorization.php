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
session_start();
session_regenerate_id();
if(!isset($_SESSION["antihijacking"])) {
	session_destroy();
	header("Location: https://www.twittelegram.com/");
	die();
}
$_SESSION["antihijacking"] = array_intersect_assoc($_SESSION["antihijacking"], $_SERVER);

$securefile = fopen($APIKEYSFILEPATH,"r");
$securejson = fread($securefile, filesize($APIKEYSFILEPATH));
fclose($securefile);
$secureobj = json_decode($securejson);
$securekeys = get_object_vars($secureobj);
$botToken = $securekeys["telegramBotToken"];
	
define('BOT_TOKEN', $botToken); // place bot token of your bot here

function checkTelegramAuthorization($auth_data) {
  $check_hash = $auth_data['hash'];
  unset($auth_data['hash']);
  $data_check_arr = [];
  foreach ($auth_data as $key => $value) {
    $data_check_arr[] = $key . '=' . $value;
  }
  sort($data_check_arr);
  $data_check_string = implode("\n", $data_check_arr);
  $secret_key = hash('sha256', BOT_TOKEN, true);
  $hash = hash_hmac('sha256', $data_check_string, $secret_key);
  if (strcmp($hash, $check_hash) !== 0) {
    throw new Exception('Data is NOT from Telegram');
  }
  if ((time() - $auth_data['auth_date']) > 86400) {
    throw new Exception('Data is outdated');
  }
  return $auth_data;
}

function saveTelegramUserData($auth_data) {
  $auth_data_json = json_encode($auth_data, JSON_PRETTY_PRINT);
  setcookie('tg_user', $auth_data_json);
}


try {
  $auth_data = checkTelegramAuthorization($_GET);
  saveTelegramUserData($auth_data);
} catch (Exception $e) {
  die ($e->getMessage());
}

?>