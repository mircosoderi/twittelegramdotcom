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
			
			#telegram-login-twittelegramdotcom_bot {
				display:none;
			}

		</style>
	</head>
	<body>
		<div id="container">
			<h1 style="cursor:pointer;" onclick="document.location.href='https://www.twittelegram.com/';"><span style="color:#00aced;">twit</span><span style="color: #0088cc;">telegram</span></h1>		
			<h2>Privacy policy</h2>
			<p style="font-weight:bold;">Effective date: Sunday, March 15, 2020.</p>
			<p>The responsible for the treatment of data is Mirco Soderi. Contact him for any question or integration to this policy that you find to be necessary.</p>
			<p>For each user that logins to Twittelegram through her Telegram credentials, the service stores:
				<ul>
					<li>her Telegram unique identifier;</li>
					<li>her name and surname;</li>
					<li>the Web address of her Twitter profile photo.</li>
				</ul>
			</p>
			<p>Once that the Telegram user subscribes to the Twittelegram service, the service stores:
				<ul>
					<li>dates and times of all operations of subscription and unsubscription;</li>
					<li>current subscription status;</li>
					<li>authentication data of the Linux user that is created on the Twittelegram server once that a Telegram user subscribes to the Twittelegram service;</li>
					<li>a detailed report of activities performed by the Telegram user within her Twittelegram private area;</li>
					<li>data related to Telegram chats from where the Telegram user interacts with the Twittelegram bot, and specifically for each chat:
						<ul>
							<li>the unique identifier assigned by Telegram to the chat;</li>
							<li>a detailed report of all messages and commands sent to and received from the Twittelegram bot;</li>
							<li>the title of the chat, if any;</li>
							<li>the type of the chat (private, group, ...)</li>
							<li>whether the Twittelegram service is currently enabled for the chat or not;</li>
							<li>the Twitter accounts that are linked to the chat, that is those Twitter accounts on behalf of which it is possible to post contents on Twitter directly from within the chat, and specifically for each Twitter profile:
								<ul>
									<li>the unique identifier assigned by Twitter to the profile;</li>
									<li>the authentication tokens;</li>
									<li>the date and time when the profile has been linked to the Telegram chat;</li>									
								</ul>							
							</li>
							<li>temporary data about ongoing conversations with the Telegram bot aimed at posting a Tweet, replying, and so on;</li>
							<li>data about the periodic readings that the chat performs, and for each reading:
								<ul>
									<li>the identifier of the Twitter profile from where the contents are read;</li>
									<li>the identifier of the list, owner by the above Twitter profile, from where contents are read in particular;</li>
									<li>the periodicity of reading operations;</li>
									<li>the date and time of the last reading operation;</li>									
								</ul>
							</li>							
						</ul>
					</li>
					<li>the monetary contributions that the Telegram user has declared that she has sent, and particularly for each contribution:
						<ul>
							<li>the date of the contribution;</li>
							<li>the amount of the contribution;</li>
							<li>the status of the record (unverified, verified OK, verified KO);</li>
						</ul>
					</li>
				</ul>
			</p>
			<p>Data about Telegram users and their activity on Twittelegram are stored automatically, as a part of procedures that lead to the satisfaction of user requests, both those that are performed through the Web, and those that are performed through the bot.</p>
			<p>For each user that logins to Twittelegram through her Twitter credentials, the service stores:
				<ul>
					<li>the screen name of the Twitter user;</li>
					<li>the name and description of the Twitter user;</li>
					<li>the read-only authentication tokens;</li>
					<li>the full listing of Twitter lists that the Twitter user owns, and for each list:
						<ul>
							<li>the Web address of the list, that also serves as a unique identifier;</li>
							<li>the name of the list;</li>
							<li>the description of the list.</li>
						</ul>
					</li>					
				</ul>
			</p>
			<p>Once that the Twitter user subscribes to the service, the following additional data are stored:
				<ul>
					<li>dates and times of all operations of subscription and unsubscription;</li>
					<li>current subscription status;</li>
					<li>a detailed report of activities performed by the Twitter user within her Twittelegram private area;</li>
					<li>the indication about the availability of the contents of the list for Twittelegram users, that corresponds to the possibility or not to receive contents of the list in Telegram chats, and it is where the Twitter user has a set of options, she can allow the access to everybody, or to non-blacklisted, or to whitelisted only, or to nobody;</li>
					<li>a detailed report of contents delivered from the list to Telegram chats through the Twittelegram service;</li>
					<li>for each Twitter list owner by the Twitter user, the full list of Telegram chats that read from the list, each identified through its identifier assigned by Telegram, and its name if any.
				</ul>
			</p>
			<p>Data about Twitter users and their activity on Twittelegram are stored automatically, as a part of procedures that lead to the satisfaction of user requests, both those that are performed through the Web, and those that are performed through the bot, both those that are delivered by Telegram users and that involve lists owned by the Twitter user, and those that are directly performed by the Twitter user. </p>
			<p>The collection of the above mentioned data is performed primarily because they are necessary for that the service could be provided, and because they are useful for identifying suspicious user behaviors and for granting the security of the service as a whole and avoiding any waste of resources. A side usage of the data, aggregated and anonymous, can be the production of articles and papers to be presented in conferences or delivered to journals. It is explicitly stated and granted that data are not used for performing profiling activities of any sort, or for performing direct or indirect marketing activities of any sort. Authenticating in Twittelegram with her Twitter or Telegram credentials, and subscribing to the service, the user express her consent to the treatment of data as specified in this document.</p>
			<p>As a result of the detailed list of data that Twittelegram stores, the following categories of data sources can be identified:
				<ul>
					<li>the most of the data are collected from parameters of requests directly delivered by the user, through the Web or through the bot;</li>
					<li>some other data are collected from the Telegram and the Twitter services, once that the user has logged in Twittelegram using her Telegram or Twitter credentials. These data are essentially a small set of basic metadata about the user, such as her name, unique identifier, and similar, in general those data that are immediately made available from Telegram or Twitter as a result of the successful completion of the login process;</li>
					<li>some other data are collected from the Telegram or Twitter services through the invocation of read-only APIs made available by the mentioned services, it is the case for example of the retrieval of the lists that the Twitter user owns, the retrieval of contents from the lists for that they could be delivered to Telegram chats, or the identification of the role that a Telegram user has in a given chat, and they are data that are strictly necessary for providing the service that Twittelegram offers.</li>
				</ul>
			</p>
			<p>Nobody will have access to data stored in Twittelegram apart from the responsible for the treatment of data. No people, no companies, nobody, for any purpose, in any form, apart from investigation authorities with appropriate authorizations received from jurisdictional authorities. Scientific reports, papers, articles can be produced that are <i>based on</i> data stored in Twittelegram, but in these cases, we are talking about fully anonymous, aggregated data, or about considerations based on data without any sort of exposure of data themselves.</p>
			<p>To be noted here, that the invocation of APIs made available by the Telegram and the Twitter services for example for posting a tweet, a reply, a retweet, for reading a content from Twitter and sending it to a Telegram chat, for sending a command or a message to the Twittelegram bot through the Telegram app and the Telegram servers, are all operations that expectedly lead to the storing of some sort of data in the Twitter and/or the Telegram servers. Refer to Twitter and Telegram privacy policies to learn more about this.</p> 
			<p>Each user can download at any time from within her private area the integral file where all the data locate that Twittelegram owns about her. Authentication data are obfuscated for obvious security reasons. Users are encouraged to make any sort of request about the data that Twittelegram stores about them. It could happen that a request is not compatible with the provisioning of the service. In this case, the user will be informed, and a clear and detailed motivation for that will be given. Users can use the contact details at the bottom of this page to make requests to the responsible of the project and data management.</p>
			<p>Any change or update of this privacy policy is advertised through the Twitter profile of the Twittelegram project. The document history at the end of this page is also of help for this purpose.</p>			
			<h2>Document history</h2>
			<p>Sunday, March 15, 2020 - The first version of the document is released. It is the currently effective version.</p>
			<h2>Learn more</h2>
			<p><a href="about.php" title="About">Learn more</a>, check the <a href="privacy.php" title="Data management">privacy policy</a>, follow on <a href="https://twitter.com/twittelegramcom" title="Twitter Twittelegram">Twitter</a> and <a href="https://t.me/twittelegramcom" title="Telegram Twittelegram">Telegram</a>. Contact <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>, visit his <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a> profiles.</p>
		</div>
	</body>
</html>
<?php
if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
?>