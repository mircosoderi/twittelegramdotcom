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
 
$tsf = $argv[1];
$tdf = $argv[2];
$tsa = json_decode(file_get_contents($tsf),true);
$tda = json_decode(file_get_contents($tdf),true);
if(array_key_exists("chats",$tsa)) {
	$chats = array_keys($tsa["chats"]);
	foreach($chats as $chat) {
		$logs = array_keys($tsa["chats"][$chat]["log"]);
		foreach($logs as $log) {
			if(!array_key_exists($log, $tda["chats"][$chat]["log"])) {
				$tda["chats"][$chat]["log"][$log] = $tsa["chats"][$chat]["log"][$log];
			}
		}	
		if(
			array_key_exists("wizard",$tsa["chats"][$chat]) && 
			array_key_exists("wizard",$tda["chats"][$chat]) && 
			$tsa["chats"][$chat]["wizard"]["id"] == $tda["chats"][$chat]["wizard"]["id"] &&
			$tsa["chats"][$chat]["wizard"]["status"] == "published" && 
			$tsa["chats"][$chat]["wizard"]["status"] != $tda["chats"][$chat]["wizard"]["status"] 
		){ 
			$tda["chats"][$chat]["wizard"]["status"] = $tsa["chats"][$chat]["wizard"]["status"]; 
		}
		foreach(array_keys($tsa["chats"][$chat]["readings"]) as $reading) {
			if($tda["chats"][$chat]["readings"][$reading] && $tsa["chats"][$chat]["readings"][$reading]["last"] != $tda["chats"][$chat]["readings"][$reading]["last"]) {
				$tda["chats"][$chat]["readings"][$reading]["last"] = $tsa["chats"][$chat]["readings"][$reading]["last"];
			}
		}
	}
}
if(array_key_exists("lists",$tsa)) {
	$lists = array_keys($tsa["lists"]);
	foreach($lists as $list) {
        	$logs = array_keys($tsa["lists"][$list]["log"]);
	        foreach($logs as $log) {
        	        if(!array_key_exists($log,$tda["lists"][$list]["log"])) {
                	        $tda["lists"][$list]["log"][$log] = $tsa["lists"][$list]["log"][$log];
                	}
        	}
	}
}
file_put_contents($tdf,json_encode($tda, JSON_PRETTY_PRINT));
?>

