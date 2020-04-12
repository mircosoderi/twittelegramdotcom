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
if(!isset($_SESSION["antihijacking"])) $_SESSION["antihijacking"] = $_SERVER;
else $_SESSION["antihijacking"] = array_intersect_assoc($_SESSION["antihijacking"], $_SERVER);
if(isset($_SESSION["unsubscribed"])) {
	$unsubscribed = true;
	session_destroy();
}
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
		<script>			
			window.onload = function() { 
				if(window.location.hash.substr(1) == "telegram_subscribe") {
					document.getElementById("telegram_arrow").style.display = 'inline';
				}
			};
		</script>
			
	</head>
	<body>
		<div id="container">
			<h1><span style="color:#00aced;">twit</span><span style="color: #0088cc; ">telegram</span></h1>
			<p style="font-size:larger; font-style:italic; font-weight:bold;">Have tweets sent to your Telegram chat, group, or channel. Discuss them privately. Send tweets and replies directly from the chat.</p>			
			<p style="background: rgba(0,172,237,0.5); padding: 1em;"><span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); margin-bottom:1em; ">Are you active on Twitter?</span><span style="padding-left:0.1em;"><strong>Subscribe to Twittelegram</strong> to deliver your tweets and those of your favorite profiles to Telegram users and groups.</span></p>
			<p style="background: rgba(0,172,237,0.5); padding: 1em;"><span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); margin-bottom:1em; ">Do you lead a community?</span><span style="padding-left:0.1em;">Add <strong>@twittelegramdotcom_bot</strong> to the <a href="https://www.modernghana.com/news/870520/10-reasons-why-telegram-is-better-than-whatsapp.html" title="Telegram">Telegram</a> group of your community and start reading from and writing to Twitter <strong>as a whole</strong>!</span></p>			
			<p style="background: rgba(0,172,237,0.5); padding: 1em;"><span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); margin-bottom:1em; ">None of the above?</span><span style="padding-left:0.1em;">Browse to the <a href="https://t.me/twittelegramdotcom_bot" title="twittelegramdotcom_bot">bot Web page</a> from your mobile device to use the Twittelegram service as a single user.</span></p>			
			<h2>Get started</h2>
			<p style="background: rgba(0,172,237,0.5); padding: 1em;">
				<span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); margin-bottom:1em; ">Are you active on Twitter?</span>
				<span style="padding-left:0.1em; display:block; margin-bottom:1em;">Login to Twittelegram using your Twitter credentials. At your first access, you will be asked to subscribe. Once subscribed, you will be presented a list of your Twitter lists. By default, no list is available for Telegram users. Pick those lists whose timelines you wish to share, and relax. Feel free to change your mind any time.</span>
				<span style="padding: 0.5em; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><span onclick="document.location.href='twitter/login.php';" style="display:block; background-color: #00aced; color:white; cursor:pointer; border-radius:1.5em; height:1.8em; width: 14em; margin:auto; padding-top:0.5em;"><img src="img/twitter_white.png" style="position:relative; top:-0.2em; height:1.7em;"><span style="position: relative; top:-0.7em;">Log in with Twitter</span></span></span>
			</p>
			<p style="background: rgba(0,172,237,0.5); padding: 1em;">
				<span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); margin-bottom:1em; ">Do you lead a community?</span>
				<span style="padding-left:0.1em; display:block; margin-bottom:1em;">Login to Twittelegram through the button below here and subscribe, then add <strong>@twittelegramdotcom_bot</strong> to your Telegram group, put a look at commands it supports, and start interacting with it. <strong>Make it easy, the bot is your friend.</strong> It will be glad to help you doing things the right way, but <strong>it will never violate your privacy</strong>.</span>
				<span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); margin-bottom:1em; ">Do you wish to use Twittelegram as a single user?</span>
				<span style="padding-left:0.1em; display:block; margin-bottom:1em;">Login to Twittelegram through the button below here and subscribe, then browse to <a href="https://telegram.me/twittelegramdotcom_bot" title="twittelegramdotcom_bot">the bot Web page</a> to start a chat with it. <strong>Make it easy, the bot is your friend.</strong> It will be glad to help you doing things the right way, but <strong>it will never violate your privacy</strong>.</span>
				<span style="padding: 0.5em; font-weight: bold; color: #0088cc; display: block; background: rgba(255,255,255,0.5); text-align:center;"><a id="telegram_subscribe" name="telegram_subscribe"></a><span style="font-size:xx-large; position: relative; top:-0.3em; padding-right:1em; font-weight:bold; display:none;" id="telegram_arrow">&rarr;</span><script async src="https://telegram.org/js/telegram-widget.js?7" data-telegram-login="twittelegramdotcom_bot" data-size="large" data-auth-url="https://www.twittelegram.com/telegram/manage.php" data-request-access="read"></script></span>			
			</p>	
				
			<h2>Learn more</h2>
			<p><a href="about.php" title="About">Learn more</a>, check the <a href="privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>
		</div>
		
	</body>
</html>
<?php
if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
?>
		