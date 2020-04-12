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

require('tmhOAuth.php');
	
function get_connection($apikeysfilepath = "./keys.json") {
	$securefile = fopen($apikeysfilepath,"r");
	$securejson = fread($securefile, filesize($apikeysfilepath));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);
	$consumer_key = $securekeys["apiKey"];
	$consumer_secret = $securekeys["apiSecretKey"];
	$connection = new tmhOAuth(array(
		  'consumer_key'    => $consumer_key,
		  'consumer_secret' => $consumer_secret,
		  'user_token'      => "",
		  'user_secret'     => ""
	));		
	return $connection;
}

function get_auth_connection($user_token, $user_secret, $apikeysfilepath = "./keys.json") {
	$securefile = fopen($apikeysfilepath,"r");
	$securejson = fread($securefile, filesize($apikeysfilepath));
	fclose($securefile);
	$secureobj = json_decode($securejson);
	$securekeys = get_object_vars($secureobj);
	$consumer_key = $securekeys["apiKey"];
	$consumer_secret = $securekeys["apiSecretKey"];
	$connection = new tmhOAuth(array(
		  'consumer_key'    => $consumer_key,
		  'consumer_secret' => $consumer_secret,
		  'user_token'      => $user_token,
		  'user_secret'     => $user_secret
	));
	return $connection;
}
?>