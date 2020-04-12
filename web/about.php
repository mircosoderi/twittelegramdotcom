<?php 
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
			<h2>Something more about Twittelegram</h2>
			<p style="border: thick solid #0088cc; background-color:#00aced; font-weight:bold; padding:1em;">If you have not read throughly the homepage yet, start from there. In this page, you will find some details and motivations, but you will have the sensation of never arriving to the point if you read this without having read that. That is because basic ideas and usage are described there, and they are not repeated here.</p>
			<p>Formally speaking, Twittelegram is a <strong>research</strong> project that stands at the intersection of <strong>communication strategies</strong> and <strong>information engineering</strong>.</p>				
			<p>Twittelegram allows people to use Twitter, and Telegram, in a way that is <strong>truly</strong> innovative. Also, it is expected to be an <strong>extremely challenging terrain</strong> for information engineers focused on distributed systems. That is why it fully adheres to the definition of a research project, for the present, and for the future.</p>
			<p>As for the communication strategies, Twittelegram enables the sharing of Twitter homepages (timelines), it emphasizes the push side of Twitter, but most of all, it enables, more, <span style="font-weight: bold; font-size:large;">it makes it seamless, the usage of Twitter as a community</span>.</p>
			<p>As for the <strong>information engineering</strong>, Twittelegram spreads across the server side of the Web, the client side of the Web, a multi-user Linux Centos with a multitude of scheduled jobs, complex shell scripts, standalone PHP scripts, authenticated interactions with Twitter and Telegram APIs, a Telegram Bot with its PHP webhook, Linux commands executed in the satisfying of Web requests (online creation of Linux users, one per Telegram user, and much more), exploitation and implementation of data security mechanisms, some degree of separation of the Web interface from the background Linux tasks that perform the reading, the delivery and the writing of contents, and much more.</p>
			<p>Twittelegram stands, at today, on a single host. Introduce some redundancy now. Introduce some load balancing. Do it distributing all of the above across a set of hosts owned by different organizations. Preserve data security, integrity, coherence. Do all of this having in mind a production-ready service, that <strong>must</strong> be 101% reliable, 101% efficient, 101% safe. Do you see how long the road is? Do you see how much room is left for research? Do you start understanding why it can be considered a research project?</p>
			<p>Twittelegram is <strong>free of charge</strong>. Obviously, it does not mean that it has no costs. Every single activity that one perform on it is resource intensive, <strong>and</strong> it leads to the consumption of finite resources. Resources are finite. Economic resources of the promoter and responsible of the project are finite also. The economic contribution of all stakeholders, users in first, is foundational, for that the project could stay alive, and grow under all points of view. That is why all registered users are warmly encouraged to make voluntary offers based on their usage of the service. The service itself tells each user which is the <strong>recommended</strong> offer that she should make.</p>			
			<p>Twittelegram is <strong>NOT</strong> a production-ready commercial service. It is <strong>NOT</strong> something on which one could base in whole or in part its reputation at today. It is <strong>NOT</strong> granted to be efficient, reliable, suitable, for any specific purpose. It is scientific research. It is a service that <strong><i>appears</i> to work quite well</strong>, but it is very likely that it could need improvements under a set of aspects that will be evident only once that the community of users will be grown, and the usage will be more intensive. The service can be discontinued at any time for any reason, as a whole, or for specific user(s), with or without a prior alert.</p> 			
			<p><strong>Professional services</strong> can be developed that match specific and more stringent requirements, based in part or in whole on the design and development work carried out in the context of the Twittelegram project. <strong>Research activities</strong> can be made that are aimed at improving the architecture, the implementation, the documentation, or any other aspect of the project. Contact the responsible of the project and/or navigate the <a href="https://github.com/mircosoderi/twittelegramcom" title="Twittelegramcom GitHub Repository">GitHub repository</a> if you are interested in a professional service as a user, or if you are interested in building something commercial or not that borrows in part or in whole the architecture and/or the code from the Twittelegram project, or if you are interested in making some research on the Twittelegram project.</p>
			<p>Put a look at the <a href="privacy.php" title="Privacy policy">Twittelegram Privacy Policy</a>, that also is a work in progress, and it is a further aspect of the research activity, that goes to touch the field of law. The responsible of the project is <strong>Mirco Soderi</strong>, that is the guy that has thought, designed, developed, and that is maintaining all of this at today. Contact him at <a href="mailto:mirco.soderi@gmail.com">mirco.soderi@gmail.com</a>. Learn more about him on <a href="https://www.linkedin.com/in/mirco-soderi-3b470525/?originalSubdomain=it">Linkedin</a>, <a href="https://www.facebook.com/mirco.soderi">Facebook</a>, <a href="https://www.instagram.com/mircosoderi/">Instagram</a>, and <a href="https://twitter.com/mircosoderi">Twitter</a>. Follow <a href="https://twitter.com/twittelegramcom" title="Twittelegram Twitter">Twittelegram</a> on Twitter.</p>
		</div>
	</body>
</html>
<?php
if(!$_SESSION["generic_browsing_cost"]) $_SESSION["generic_browsing_cost"] = 0;
$_SESSION["generic_browsing_cost"] = $_SESSION["generic_browsing_cost"] + microtime(true) - $start;
?>