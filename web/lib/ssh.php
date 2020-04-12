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
 */

function wexe( $conn, $command )
{
    $stream = ssh2_exec( $conn, $command );
    $error_stream = ssh2_fetch_stream( $stream, SSH2_STREAM_STDERR );
    stream_set_blocking( $stream, TRUE );
    stream_set_blocking( $error_stream, TRUE );
    $output = stream_get_contents( $stream );
    $error_output = stream_get_contents( $error_stream );
    fclose( $stream );
    fclose( $error_stream );
    return array( $output, $error_output );
}

function addlnxusr($usr, $pwd, $newusr, $newpwd, $forbiddenUsernames = [], $host = "127.0.0.1", $usersgroup = "mylnxusersgrp") {
	if(!in_array($newusr,$forbiddenUsernames)) {
		$conn = ssh2_connect($host);
		$auth = ssh2_auth_password($conn, $usr, $pwd);	
		$exec = ssh2_exec($conn, "echo '$newusr:$newpwd::$usersgroup::/home/$newusr:/bin/bash' >> addusers.todo");						
		$exit = ssh2_exec($conn, "exit");
		unset($conn);
	}
}

function dellnxusr($usr, $pwd, $delusr, $forbiddenUsernames = [], $host = "127.0.0.1") {
	if(!in_array($delusr,$forbiddenUsernames)) {
		$conn = ssh2_connect($host);
		$auth = ssh2_auth_password($conn, $usr, $pwd);	
		$exec = ssh2_exec($conn, "echo $delusr >> delusers.todo");			
		$exit = ssh2_exec($conn, 'exit');
		unset($conn);
	}
}

function lnxchk($usr, $pwd, $host = "127.0.0.1") {
	$conn = ssh2_connect($host);
	$auth = ssh2_auth_password($conn, $usr, $pwd);	
	if($auth) {
		$ready = strpos(wexe($conn, 'ls')[0],"ttenv") !== false;		
		$exit = ssh2_exec($conn, 'exit');
		unset($conn);
		return $ready;
	}
	else {
		unset($conn);
		return false;
	}	
}

function to24h($t,$f) {
	if(strtoupper($f) == "AM") {
		if(strpos($t, "12:") === 0) return str_replace("12:","00:",$t);
		else return $t;
	}
	else if(strtoupper($f) == "PM") {
		if(strpos($t, "12:") === 0) return $t;
		else return (substr($t,0,2)+12).substr($t,2);
	}
	else {
		return $t;
	}
}

function mklnxjob($a, $chat, $tuser, $list, $tzn) {	
	$minute = ["*"];
	$hour = ["*"];
	$dayofmonth = ["*"];
	$month = ["*"];
	$dayofweek = ["*"];	
	$a[8] = to24h($a[8],$a[9]);	
	$a[11] = to24h($a[11],$a[12]);
	if(strpos(implode(" ",$a)," on working days") !== false) $dayofweek = range(1,5);
	else if(strpos(implode(" ",$a)," on weekends") !== false) $dayofweek = range(6,7);
	else {
		$dayofweek = [];
		if(stripos(implode(" ",$a),"monday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 1;
		if(stripos(implode(" ",$a),"tuesday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 2;
		if(stripos(implode(" ",$a),"wednesday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 3;
		if(stripos(implode(" ",$a),"thursday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 4;
		if(stripos(implode(" ",$a),"friday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 5;
		if(stripos(implode(" ",$a),"saturday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 6;
		if(stripos(implode(" ",$a),"sunday",strpos(implode(" ",$a)," on ")) !== false) $dayofweek[] = 7;
	}
	
	if($a[4] == "once" && $a[5] == "a" && $a[6] == "week") { $a[4] = "every"; $a[5] = "7"; $a[6] = "days"; }
	if($a[4] == "once" && $a[5] == "a" && $a[6] == "day") { $a[4] = "every"; $a[5] = "1"; $a[6] = "days"; }
	if($a[4] == "twice" && $a[5] == "a" && $a[6] == "day") { $a[4] = "every"; $a[5] = "12"; $a[6] = "hours"; }
	if($a[4] == "once" && $a[5] == "per" && $a[6] == "hour") { $a[4] = "every"; $a[5] = "1"; $a[6] = "hours"; }
	if($a[4] == "twice" && $a[5] == "per" && $a[6] == "hour") { $a[4] = "every"; $a[5] = "30"; $a[6] = "minutes"; }
	if($a[4] == "at" && $a[5] == "every" && $a[6] == "minute") { $a[4] = "every"; $a[5] = "1"; $a[6] = "minutes"; }
	
	if($a[6] == "days") { 
		$dayofmonth = range(1,31,$a[5]); 
		$hour = [explode(":",$a[8])[0]+0]; 
		foreach($hour as &$h) if($h == 24) $h = 0;
		$hour = array_unique($hour);
		$minute = [explode(":",$a[8])[1]+0]; 	
		return implode(",",$minute)." ".implode(",",$hour)." ".implode(",",$dayofmonth)." ".implode(",",$month)." ".implode(",",$dayofweek)." /usr/bin/php /home/ttusr$tuser/ttenv/doread.php $chat $tuser $list";
	}	
	if($a[6] == "hours") { 		
		$hour = range(explode(":",$a[8])[0],explode(":",$a[11])[0],$a[5]); 
		foreach($hour as &$h) if($h == 24) $h = 0;
		$hour = array_unique($hour);
		if(explode(":",$a[11])[0]+0 < explode(":",$a[8])[0]+0) {
			$hour = [];
			$h = explode(":",$a[8])[0]+0;
			while($h >= explode(":",$a[8])[0]+0 || $h <= explode(":",$a[11])[0]+0) {
				$hour[] = $h;
				$h = ( $h + $a[5] ) % 24;
			}
		}		
		$minute = [explode(":",$a[8])[1]+0]; 
		return implode(",",$minute)." ".implode(",",$hour)." ".implode(",",$dayofmonth)." ".implode(",",$month)." ".implode(",",$dayofweek)." /usr/bin/php /home/ttusr$tuser/ttenv/doread.php $chat $tuser $list";
	}	
	if($a[6] == "minutes") { 
		$jb = "";
		$hour = range(explode(":",$a[8])[0]+1,explode(":",$a[11])[0]-1); 
		foreach($hour as &$h) if($h == 24) $h = 0;
		$hour = array_unique($hour);		
		if(explode(":",$a[11])[0]+0 < explode(":",$a[8])[0]+0) {
			$hour = [];
			$h = explode(":",$a[8])[0]+0;
			while($h > explode(":",$a[8])[0]+0 || $h < explode(":",$a[11])[0]+0) {
				$hour[] = $h;
				$h = ( $h + 1 ) % 24;
			}
		}
		$minute = range(0,59,$a[5]);	
		$jb = "";
		if(abs(explode(":",$a[11])[0]-explode(":",$a[8])[0]) > 1) {
			$jb.=implode(",",$minute)." ".implode(",",$hour)." ".implode(",",$dayofmonth)." ".implode(",",$month)." ".implode(",",$dayofweek)." /usr/bin/php /home/ttusr$tuser/ttenv/doread.php $chat $tuser $list\n";
		}
		if(abs(explode(":",$a[11])[0]-explode(":",$a[8])[0]) > 0) {
			$hour = [explode(":",$a[8])[0]+0];
			foreach($hour as &$h) if($h == 24) $h = 0;
			$hour = array_unique($hour);
			$minute = range(explode(":",$a[8])[1],59,$a[5]);
			$jb.=implode(",",$minute)." ".implode(",",$hour)." ".implode(",",$dayofmonth)." ".implode(",",$month)." ".implode(",",$dayofweek)." /usr/bin/php /home/ttusr$tuser/ttenv/doread.php $chat $tuser $list\n";
		}
		$hour = [explode(":",$a[11])[0]+0];
		foreach($hour as &$h) if($h == 24) $h = 0;
		$hour = array_unique($hour);
		$minute = range(0,explode(":",$a[11])[1],$a[5]);
		$jb.=implode(",",$minute)." ".implode(",",$hour)." ".implode(",",$dayofmonth)." ".implode(",",$month)." ".implode(",",$dayofweek)." /usr/bin/php /home/ttusr$tuser/ttenv/doread.php $chat $tuser $list";
		return $jb;
		
	} 
	
}

function addlnxjob($usr, $pwd, $job, $host = "127.0.0.l") {
	$conn = ssh2_connect($host);
	$auth = ssh2_auth_password($conn, $usr, $pwd);	
	$src = str_replace("/","\\/",preg_quote(implode(array_unique(array_filter(explode(" ",$job), function($v) { return strpos($v,"https://twitter.com/") === 0; })) )));
	$del = ssh2_exec($conn, "sed -i '/$src$/d' ./ttctb ; echo \"$job\" >> ./ttctb ; crontab ./ttctb");		
	$exit = ssh2_exec($conn, "exit");
	unset($conn);
}

function dellnxjob($usr, $pwd, $job, $host = "127.0.0.l") {
	$conn = ssh2_connect($host);
	$auth = ssh2_auth_password($conn, $usr, $pwd);	
	$src = str_replace("/","\\/",preg_quote(implode(array_unique(array_filter(explode(" ",$job), function($v) { return strpos($v,"https://twitter.com/") === 0; })))));
	$del = ssh2_exec($conn, "sed -i '/$src/d' ./ttctb ; crontab ./ttctb");	
	$exit = ssh2_exec($conn, "exit");
	unset($conn);
}

?>
