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

require_once("../lib/ssh.php");
require_once("../lib/Mobile_Detect.php");
require_once("../lib/const.php");
$detect = new Mobile_Detect;
$_SESSION["tkn"] = uniqid();

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
				if(array_key_exists("readTmpPwd",$candidate["chats"][$chat_id]) && $tkn == md5($candidate["chats"][$chat_id]["readTmpPwd"])) {
					unset($candidate["chats"][$chat_id]["readTmpPwd"]);
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

function isTimezone($v) {
	$isTimezone = false;
	$tzgs = json_decode(file_get_contents("timezones.json"),true); $tzs = []; foreach($tzgs as $tzg) $tzs = array_merge($tzs,$tzg["zones"]);
	foreach($tzs as $tz) if($v == $tz["value"]) $isTimezone = true;
	return $isTimezone;	
}

$authorized = false;
$tkn = filter_input(INPUT_GET, 'tkn', FILTER_SANITIZE_URL);
if($tkn) { $authorized = getData($tkn); } 
else { $authorized = $_SESSION["user"] && $_SESSION["chat"]; }

if($authorized) {

$_SESSION["user"] = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);

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
			
			span.step {
			  background: white;
			  border-radius: 0.6em;
			  -moz-border-radius: 0.6em;
			  -webkit-border-radius: 0.6em;
			  border-color: #0088cc;
			  color: #0088cc;
			  display: inline-block;
			  font-weight: bold;
			  line-height: 1.4em;
			  margin-right: 5px;
			  text-align: center;
			  width: 1.4em; 
			  float:left;
			}
			
			span.currStep {
			  background: #0088cc;
			  border-radius: 0.6em;
			  -moz-border-radius: 0.6em;
			  -webkit-border-radius: 0.6em;
			  color: white;
			  display: inline-block;
			  font-weight: bold;
			  line-height: 1.4em;
			  margin-right: 5px;
			  text-align: center;
			  width: 1.4em; 
			  float:left;
			}
			
			div.currStep {
				font-weight:bold;
				color: #0088cc;
			}
			
			#timeline { 
				color:#0088cc
				font-size:large;
				padding:1em;		
				line-height: 1.6em;		
				height: 10em;
			}
			
			#main {
				position:relative;
				left:9em;
				top:-12.1em;
				width:34em;
			}

			button.next, button.send {
				padding:1em;
				color: white;
				font-weight:bold;
				background-color:#0088cc;
				border: medium solid #0088cc;
				margin-top:2em;			
				cursor:pointer;
			}
			
			button.back {
				padding:1em;
				color: #0088cc;
				border: medium solid #0088cc;
				margin-top:2em;
				margin-right:23em;
				background-color:white;
				cursor:pointer;
				font-weight:bold;
			}
			
			div.aTwitterProfile {
				padding:1em;
				border-radius: 1em;
				-moz-border-radius: 1em;
				-webkit-border-radius: 1em;
				color: black;
				border: medium solid #0088cc;
				margin-top:1em;
				background-color:white;
				width:34em;
				cursor:pointer;
			}
			
			div.aTwitterProfile > strong {
				color: #0088cc;
				
			}

			input#searchProfile {
				padding:0.5em 1em 0.5em; 
				width:100%; 
				margin-top:2em; 
				border: medium solid #0088cc;
				background-image: linear-gradient(to right, #00aced, white);
				font-family:monospace;
				font-weight:bold;
			}
			
			input#searchProfile::placeholder { 
				color:white;				
			}
			
			div#currentTwitterProfile {
				padding:1em;
				border-radius: 1em;
				-moz-border-radius: 1em;
				-webkit-border-radius: 1em;
				color: black;
				border: medium solid #0088cc;
				width:100%;
				background-image: linear-gradient(to right, #00aced, white);
			}
			
			div#currentTwitterProfileContainer {
				display:none;
				margin-bottom:2em;
			}
			
			div.aTwitterProfileList {
				padding:1em;
				border-radius: 1em;
				-moz-border-radius: 1em;
				-webkit-border-radius: 1em;
				color: black;
				border: medium solid #0088cc;
				margin-top:1em;
				background-color:white;
				width: 34em;
				cursor:pointer;
			}
			
			div.everybody {
				background-image: linear-gradient(to right, green, white);
			}
			
			div.nonblacklisted {
				background-image: linear-gradient(to right, yellow, white);
			}
			
			div.whitelisted {
				background-image: linear-gradient(to right, orange, white);
			}
			
			div.denied {
				background-image: linear-gradient(to right, red, white);
			}
			
			span.green {
				background-color: green;
				border-color: green;
				padding: 0.2em;
				border-radius: 0.2em;
			    -moz-border-radius: 0.2em;
			    -webkit-border-radius: 0.2em;
			}
			
			span.yellow {
				background-color: yellow;
				border-color: yellow;
				padding: 0.2em;
				border-radius: 0.2em;
			    -moz-border-radius: 0.2em;
			    -webkit-border-radius: 0.2em;
			}
			
			span.orange {
				background-color: orange;
				border-color: orange;
				padding: 0.2em;
				border-radius: 0.2em;
			    -moz-border-radius: 0.2em;
			    -webkit-border-radius: 0.2em;
			}
			
			span.red {
				background-color: red;
				border-color: red;
				padding: 0.2em;
				border-radius: 0.2em;
			    -moz-border-radius: 0.2em;
			    -webkit-border-radius: 0.2em;
			}
			
			div#twitterProfileLists {
				line-height: 1.5em;
			}
			
			div#currentTwitterProfileList div.aTwitterProfileList {
				cursor: default;
			}
			
			div#currentTwitterProfile div.aTwitterProfile {
				cursor: default;
			}
			
			p.confirm {
				border: thick solid #0088cc;
				background-image: linear-gradient(to right, #00aced, white);
				font-size: large;
				font-weight: bold;
				font-family: monospace;
			}
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script>
			
			function toRomeTime(h) {
				if($("#timezone").val != "local") return [h,0];
				var d = new Date();
				d.setHours(parseInt(h));
				var utc = d.getTime() + (d.getTimezoneOffset() * 60000);
				var nd = new Date(utc + 3600000);			
				var dd = nd.getDay()-d.getDay();
				if(dd == -6) dd = 1;
				if(dd == 6) dd = -1;
				return [nd.getHours(),dd];
			}

			function loadCandidateProfiles(offset = "0") {
				console.log('readapi.php?tkn=<?=$_SESSION["tkn"]?>&op=p&search='+encodeURI($("#searchProfile").val()+"&offset="+offset));
				jQuery.ajax({
					url: 'readapi.php?tkn=<?=$_SESSION["tkn"]?>&op=p&search='+encodeURI($("#searchProfile").val()+"&offset="+offset),
					success: function (result) {
						$("div#twitterProfiles").find("div").remove();						
						result.forEach(function(o){
							$("div#twitterProfiles").append($("<div data-username=\""+o.screenName+"\" class=\"aTwitterProfile\"><strong>"+o.screenName+"</strong> - "+(o.name != null?o.name+" - ":"")+(o.description != null?o.description:"<span style=\"font-style:italic;\">No description available for this profile</span>")+"</div>"));																		
						});
						$("div#twitterProfiles div.aTwitterProfile").off("click");
						$("div#twitterProfiles div.aTwitterProfile").click(function(){
							$("div.aTwitterProfile").show();
							$("div#currentTwitterProfile").find("div").remove();
							$("#twitterProfile").val($(this).data("username"));		
							$("#mainstep1 button.next").show();
							jQuery.ajax({
								url: 'readapi.php?tkn=<?=$_SESSION["tkn"]?>&op=p&screen_name='+encodeURI($("#twitterProfile").val()),
								success: function (c) { $("div#currentTwitterProfile").append($("<div data-username=\""+c.screenName+"\" class=\"currentTwitterProfile\"><strong>"+c.screenName+"</strong> - "+(c.name != null?c.name+" - ":"")+(c.description != null?c.description:"<span style=\"font-style:italic;\">No description available for this profile</span>")+"</div>")); $("div#currentTwitterProfileContainer").show(); $("div#timeline > div#step1").css("font-weight","bold"); $("div#currentTwitterProfile").click(function(){step(2);}); $("div#twitterProfileLists").empty(); }               
							});
						});
					},							
					  error: function (xhr, ajaxOptions, thrownError) {
						console.log(xhr.status);
						console.log(thrownError);
					  }
				});
			}
			
			function step(n) {
				
				$("#timeline div").removeClass("currStep");
				$("#timeline div").addClass("step");
				$("#timeline div span").removeClass("currStep");				
				$("#timeline div span").addClass("step");
				
				$("#timeline div#step"+n.toString()).removeClass("step");
				$("#timeline div#step"+n.toString()).addClass("currStep");
				$("#timeline div#step"+n.toString()+" span").removeClass("step");
				$("#timeline div#step"+n.toString()+" span").addClass("currStep");
				
				$("#innerContainer #main > div").hide();
				$("#innerContainer #main div#mainstep"+n.toString()).show();
				
				switch(n) {
					case 1:
						if($("div.aTwitterProfile").length == 0) loadCandidateProfiles();
						break;
					case 2:
						if($("div.aTwitterProfileList").length == 0) {							
							jQuery.ajax({
								url: 'readapi.php?tkn=<?=$_SESSION["tkn"]?>&op=l&screen_name='+encodeURI($("#twitterProfile").val()),
								success: function (l) { 
									$("div#twitterProfileLists").append($("<div>Below here are the lists of "+$("#twitterProfile").val()+". Click or tap the one of your interest. You can select one list per request. Those in <span class=\"green\">green</span> are immediately available for everybody. Those in <span class=\"yellow\">yellow</span> are immediately available but the owner could decide to blacklist you at some time in the future and you could therefore stop receiving contents from these lists. Those in <span class=\"orange\">orange</span> are not immediately available and you will not receive any content until the owner of the list explicitly approve your request. Those in <span class=\"red\">red</span> are not available at all for Twittelegram users at now, so you can send a request, but you will not receive anything until the owner changes her mind and make the list available.</div>"));
									$("div#twitterProfileLists").append($("<div id=\"currentTwitterProfileList\" style=\"display:none;\"><p>Currently selected list is:</p></div>"));
									$("div#twitterProfileLists").append($("<p>Available lists are:</p>"))
									for(var id in l) {
											$("div#twitterProfileLists").append($("<div data-id=\""+id+"\" data-name=\""+l[id]["name"]+"\" class=\"aTwitterProfileList "+(l[id]["access"] != "denied"?l[id]["whocanread"]:"denied")+"\"><strong>"+l[id]["name"]+"</strong> - "+(l[id]["description"].trim() != ""?l[id]["description"]:"<span style=\"font-style:italic;\">No description available for this list</span>")+"</div>"));
									}
									$("div.aTwitterProfileList").click(function(){
										$("div#timeline > div#step2").css("font-weight","bold");
										$("#listName").val($(this).data("name"));
										$("#listID").val($(this).data("id"));
										$("div#currentTwitterProfileList").find("div").remove();
										$("div#currentTwitterProfileList").append($(this).clone());
										$("div#currentTwitterProfileList").show();
										$("div#mainstep2 button.next").show();
										
									});
								}               
							});
						}
						break;
				}
				
			}
			
			$(function() {
								
				$("#timeline div").click(function(){
					step(parseInt($("span.currStep").text()));
				});
				
				$("button.next").click(function(){
					step(1+parseInt($('span.currStep').text())); 
					return false;
				});
				
				$("button.back").click(function(){
					step(-1+parseInt($('span.currStep').text())); 
					return false;
				});
				
				$("input#searchProfile").keyup(function(){step(1);});
				
				$("#mainstep3 input").change(function(){
					if($("#mainstep3 input:checked").length > 0) {
						$("div#mainstep3 button.next").show();
					}
					else {
						$("div#mainstep3 button.next").hide();
					}
				});
				$("#mainstep3 button.next").click(function(){					
					$("div#timeline > div#step3").css("font-weight","bold");
				});
				$("#mainstep4 button.next").click(function(){					
					$("div#timeline > div#step4").css("font-weight","bold");
				});
				$("#mainstep4 input").keyup(function(){					
					$("#mainstep4 button.next").show();					
					try { if(isNaN(parseInt($("#startFromHour").val())) || parseInt($("#startFromHour").val()) < 0 || parseInt($("#startFromHour").val()) > 24) $("#mainstep4 button.next").hide(); } catch(e) { $("#mainstep4 button.next").hide();  }
					try { if(isNaN(parseInt($("#startFromMinute").val())) || parseInt($("#startFromMinute").val()) < 0 || parseInt($("#startFromMinute").val()) > 59) $("#mainstep4 button.next").hide();  } catch(e) { $("#mainstep4 button.next").hide();  }
					try { if(isNaN(parseInt($("#stopAtHour").val())) || parseInt($("#stopAtHour").val()) < 0 || parseInt($("#stopAtHour").val()) > 24) $("#mainstep4 button.next").hide(); } catch(e) { $("#mainstep4 button.next").hide();  }
					try { if(isNaN(parseInt($("#stopAtMinute").val())) || parseInt($("#stopAtMinute").val()) < 0 || parseInt($("#stopAtMinute").val()) > 59) $("#mainstep4 button.next").hide();  } catch(e) { $("#mainstep4 button.next").hide();  }					
				});
				$("#mainstep4 button.next").click(function(){
					var days = [];
					if($("#awakeOnMonday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Monday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Tuesday"); else days.push("Sunday"); }
					if($("#awakeOnTuesday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Tuesday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Wednesday"); else days.push("Monday"); }
					if($("#awakeOnWednesday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Wednesday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Thursday"); else days.push("Tuesday"); }
					if($("#awakeOnThursday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Thursday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Friday"); else days.push("Wednesday"); }
					if($("#awakeOnFriday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Friday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Saturday"); else days.push("Thursday"); }
					if($("#awakeOnSaturday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Saturday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Sunday"); else days.push("Friday"); }
					if($("#awakeOnSunday").prop("checked")) { if(toRomeTime($("#startFromHour").val())[1] == 0) days.push("Sunday"); else if(toRomeTime($("#startFromHour").val())[1] == 1) days.push("Monday"); else days.push("Saturday"); }					
					$("p.confirm").text("Read from "+($("#listID").val() == "0"?"timeline at https://twitter.com/"+$("#twitterProfile").val():"list at https://twitter.com/"+$("#twitterProfile").val()+"/lists/"+$("#listName").val()) + " " + $("#periodicity").val()+" from "+toRomeTime($("#startFromHour").val())[0]+":"+$("#startFromMinute").val()+" to "+toRomeTime($("#stopAtHour").val())[0]+":"+$("#stopAtMinute").val()+" Europe/Rome time on "+days.join().replace(/,/g,", "));
				});
				$("button.send").click(function(){
					window.location.href='manage_chat.php?action=read&stmt='+encodeURI($("p.confirm").text());
				});
				$("#searchProfile").keyup(function() { loadCandidateProfiles(); });
				step(1);
				
			});
		</script>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced;">twit</span><span style="color: #0088cc; border: medium solid #0088cc; margin:0.1em; padding:0.1em;">telegram</span></h1>
			<h2>Create New Reading Rule</h2>
			<form method="post" action="read.php">
				<input type="hidden" id="twitterProfile" name="twitterProfile" value="<?=htmlentities($_POST["twitterProfile"])?>"/>
				<input type="hidden" id="listName" name="listName" value="<?=htmlentities($_POST["listName"])?>"/>
				<input type="hidden" id="listID" name="listID" value="<?=htmlentities($_POST["listID"])?>"/>
				<div id="innerContainer">
					<div id="timeline">
						<div id="step1" class="currStep"><span class="currStep">1</span>Profile</div>
						<div id="step2"><span class="step">2</span>List</div>
						<div id="step3"><span class="step">3</span>Days</div>
						<div id="step4"><span class="step">4</span>Times</div>
						<div id="step5"><span class="step">5</span>Confirm</div>
					</div>
					<div id="main">
						<div id="mainstep1">
							<h3>Profile</h3>
							<div id="currentTwitterProfileContainer">
								<p>Currently selected profile is:</p>
								<div id="currentTwitterProfile"></div>
							</div>
							Click or tap a Twitter profile below here to select it. Those that you find at the top are those that are the most active on Twittelegram in this period. Use the search box to find profiles of your interest. If you can't find a profile, it is because its owner has not an active subscription to the Twittelegram service, or she has not yet made any list available for Twittelegram users.<br>														
							<div id="twitterProfiles" style="max-height:20em;">								
								<input type="text" name="searchProfile" id="searchProfile" placeholder="Search...">										
							</div>
							<button class="next" type="button" style="display:none; float:right;">List&nbsp;&rarr;</button>
						</div>
						<div id="mainstep2" style="display:none;">
							<h3>List</h3>
							<div id="twitterProfileLists"></div>
							<p style="text-align:center; width:36em;"><button class="back" type="button">&larr;&nbsp;Profile</button>
							<button class="next" type="button" style="display:none;">Days&nbsp;&rarr;</button></p>							
						</div>
						<div id="mainstep3" style="display:none;">
							<h3>Days</h3>
							Below here are the days of the week. Select those in which you wish to receive contents from this Twitter profile and list.
							<table style="width:100%; margin-top:1em; margin-left:2em;">
							<tr><td>
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnMonday" name="awakeOnMonday" value="<?=htmlentities($_POST["awakeOnMonday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Monday</span></p>
							</td><td>
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnTuesday" name="awakeOnTuesday" value="<?=htmlentities($_POST["awakeOnTuesday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Tuesday</span></p>
							</td></tr>
							<tr><td>
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnWednesday" name="awakeOnWednesday" value="<?=htmlentities($_POST["awakeOnWednesday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Wednesday</span></p>
							</td><td>
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnThursday" name="awakeOnThursday" value="<?=htmlentities($_POST["awakeOnThursday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Thursday</span></p>
							</td><tr>
							<tr><td>
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnFriday" name="awakeOnFriday" value="<?=htmlentities($_POST["awakeOnFriday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Friday</span></p>
							</td><td>
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnSaturday" name="awakeOnSaturday" value="<?=htmlentities($_POST["awakeOnSaturday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Saturday</span></p>
							</td></tr>
							<tr><td colspan="2">
							<p style="font-size:larger; margin:1em;"><input type="checkbox" id="awakeOnSunday" name="awakeOnSunday" value="<?=htmlentities($_POST["awakeOnSunday"])?>"> <span style="cursor:pointer;" onclick="$(this).parent().find('input').click();">Sunday</span></p>
							</td></tr>
							</table>
							<p style="text-align:center; width:34em;"><button class="back" type="button">&larr;&nbsp;List</button>	
							<button class="next" type="button" style="display:none;">Times&nbsp&rarr;</button></p>						
						</div>
						<div id="mainstep4" style="display:none;">
							<h3>Times</h3>
							Below here, you can indicate the periodicity, and the interval of hours in which you wish to receive contents from this Twitter list. Local times will be translated to Europe/Rome times.
							<p style="font-size:larger; line-height:2em;">Read 
							<select style="font-size:larger;" id="periodicity" name="periodicity">
								<option value="once a week">once a week</option>
								<option value="every 5 days">every 5 days</option>
								<option value="every 3 days">every 3 days</option>
								<option value="every 2 days">every 2 days</option>
								<option value="once a day">once a day</option>
								<option value="twice a day">twice a day</option>
								<option value="every 8 hours">every 8 hours</option>
								<option value="every 6 hours">every 6 hours</option>
								<option value="every 4 hours">every 4 hours</option>
								<option value="every 3 hours">every 3 hours</option>
								<option value="every 2 hours">every 2 hours</option>
								<option value="once per hour">once per hour</option>
								<option value="twice per hour">twice per hour</option>
								<option value="every 20 minutes">every 20 minutes</option>
								<option value="every 15 minutes">every 15 minutes</option>
								<option value="every 10 minutes">every 10 minutes</option>
								<option value="every 5 minutes">every 5 minutes</option>
								<option value="every 3 minutes">every 3 minutes</option>
								<option value="every 2 minutes">every 2 minutes</option>
								<option value="at every minute">at every minute</option>
							</select>
							<br>from <input type="text" name="startFromHour" id="startFromHour" value="<?=htmlentities($_POST["startFromHour"]?$_POST["startFromHour"]:"00")?>" maxlength="2" size="2" style="font-size:larger;"> : <input type="text" name="startFromMinute" id="startFromMinute" value="<?=htmlentities($_POST["startFromMinute"]?$_POST["startFromMinute"]:"00")?>" maxlength="2" size="2" style="font-size:larger;">
							<br>until <input type="text" name="stopAtHour" id="stopAtHour" value="<?=htmlentities($_POST["stopAtHour"]?$_POST["stopAtHour"]:"24")?>" maxlength="2" size="2" style="font-size:larger;"> : <input type="text" name="stopAtMinute" id="stopAtMinute" value="<?=htmlentities($_POST["stopAtMinute"]?$_POST["stopAtMinute"]:"00")?>" maxlength="2" size="2" style="font-size:larger;">
							<br><select name="timezone" id="timezone" style="font-size:larger;"><option value="local">local</option><option value="Rome">Europe/Rome (UTC+1)</option></select> time.</p>
							<p style="text-align:center; width:34em;"><button class="back" type="button">&larr;&nbsp;List</button>	
							<button class="next" type="button">Confirm&nbsp;&rarr;</button></p>								
						</div>
						<div id="mainstep5" style="display:none;">
							<h3>Confirm</h3>
							Below here is the request that will be stored in the server.
							<p class="confirm" style="padding:1em;"></p>
							<p style="text-align:center; width:34em;"><button class="back" type="button">&larr;&nbsp;Times</button>	
							 <button class="send" type="button">SEND!</button></p>					
						</div>
					</div>				
				</div>
			</form>
			
		</div>
		
	</body>
</html>

<?php
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Access to the configuration of readings from Twitter lists.";
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
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
			<h2>Session Expired</h2>
			<p>The provided access token is no longer valid.</p>
			<p>Try issuing the <strong>/settings</strong> command again from within your Telegram chat, or browse to the <a href="manage.php" title="manage.php">settings home</a>.</p>
			<p>If the problem does not solve, contact Mirco Soderi.</p>
			<h2>Learn more</h2>
			<p><a href="../about.php" title="About">Learn more</a>, check the <a href="../privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>

		</div>
		
	</body>
</html>

<?php 
	if($_SESSION["user"]["id"] && $_SESSION["chat"]) {
		$j = json_decode(file_get_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json"),true);
		$microtime = "".microtime(true);
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["text"] = "Access to the configuration of readings from Twitter lists failed due to invalid token.";
		$j["chats"][$_SESSION["chat"]]["log"][$microtime]["cost"] = abs(microtime(true) - $start);
		if($_SESSION["generic_browsing_cost"]) {
			$microtime = "".microtime(true);
			$j["subscription"]["log"][$microtime]["text"] = "Imputation of generic browsing activities.";	
			$j["subscription"]["log"][$microtime]["cost"] = $_SESSION["generic_browsing_cost"];
			unset($_SESSION["generic_browsing_cost"]);
		}
		file_put_contents($TELEGRAMUSERSPATH.$_SESSION["user"]["id"].".json",json_encode($j, JSON_PRETTY_PRINT));	
	}
	else {
		if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
		$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
	}
} ?>
