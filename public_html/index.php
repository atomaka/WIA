<?php
// retrieve cached data
$data = json_decode(file_get_contents('../data/index.txt'));
	
// retrieve links from database
$conf = json_decode(file_get_contents('../conf/wia.conf'));
include('lib/database.php');
$db = new Database($conf->db->hostname,$conf->db->username,$conf->db->password,$conf->db->database);

$links = $db->query("SELECT id,url,text,status,released_date FROM wia_links WHERE status = 2 OR status = 3 ORDER BY released_date DESC LIMIT 15");
?><!DOCTYPE HTML>
<html lang="en"> 
	<head>
		<meta charset="utf-8" />
		<title>Who is Andrew? Personal Site of Andrew Tomaka</title>
		<link rel="stylesheet" type="text/css" href="css/main.css?v=3" />
		<link rel="shortcut icon" href="favicon.ico" />
	</head>	
	<body>
		<div id="header"><h1>andrew tomaka</h1></div>
		<div id="content">
			<div id="column1">
				<h2>contact</h2>
				<div id="contacts" class="box center">
					<img src="img/badges/email.png" class="contact" alt="Email Andrew Tomaka" title="atomaka@gmail.com" />
					<img src="img/badges/aim.png" class="contact" alt="AIM Andrew Tomaka" title="atomaka" />
					<img src="img/badges/yahoo.png" class="contact" alt="Yahoo! Andrew Tomaka" title="atomaka" />
					<img src="img/badges/msn.png" class="contact" alt="MSN Andrew Tomaka" title="atomaka@hotmail.com" />
					<img src="img/badges/icq.png" class="contact" alt="ICQ Andrew Tomaka" title="12534325" />
					<img src="img/badges/google.png" class="contact" alt="Google Messaging Andrew Tomaka" title="atomaka@gmail.com" />
					<img src="img/badges/skype.png" class="contact" alt="Skype Andrew Tomaka" title="andrewtomaka" />
					<div id="contact"></div>  
				</div>
				<br/>
				<h2>twitter</h2>
				<div id="twitter" class="box iconized">
					<span class="right"><a href="http://www.twitter.com/atomaka"><img src="img/badges/twitter.png" class="icon" alt="Follow Andrew Tomaka on Twitter"/></a></span>
					<span class="tweet"><?php echo $data->twitter->text ?></span><br/>
					<?php if($data->twitter->time != 0) { echo time_since($data->twitter->time) ?> ago <?php } ?>
				</div>
				<br/>
				<h2>github</h2>
				<div id="github" class="box iconized">
					<span class="right"><a href="http://www.github.com/atomaka"><img src="img/badges/github.png" class="icon" alt="Follow Andrew Tomaka on Github"/></a></span>
					Committed &quot;<?php echo $data->github->commit ?>&quot;
					on <a href="<?php echo $data->github->url ?>"><?php echo $data->github->repo ?></a>.
				</div>
				
				<br/>
				<!--<h2>projects</h2>
				<h3>project 1</h3>
				<div id="projects">
					<div id ="tomtvgrid" class="box">
						<table class="formatting">
							<tr>
								<td class="top">
									<a href="/projects/TomTVGrid">TomTVGrid</a><br/><br/>
									Takes your Hulu subscriptions and creates a grid displaying when your shows air live.
								</td>
								<td class="formatting">
									<img src="img/projects/tomtvgrid.jpg" width="64" alt="sc2mmr" />
								</td>
							</tr>
						</table>
					</div>
				</div>-->
			</div>
			<div id="column1">
				<h2>random</h2>
				<div id="random">
<?php
while($link = $links->fetch_object()) {
	$link_text = $link->text;
	
	$link_text = preg_replace('/\[/','<a href="' . htmlentities($link->url) . '">',$link_text);
	$link_text = preg_replace('/\]/','</a>',$link_text);
	
	echo '					<p class="box">' . $link_text . '</p>' . "\n";
}
?>				
				</div>
			</div>
			<div id="column2">
				<h2>media</h2>
				<div id="lastfm" class="box">
					<table class="formatting">
						<tr>
							<td class="formatting">
								<img src="<?php echo (isset($data->lastfm->cover) && !empty($data->lastfm->cover)) ? $data->lastfm->cover : '/img/album.png'; ?>" class="cover" alt="<?php echo $data->lastfm->artist ?> - <?php echo $data->lastfm->song ?>"/>
							</td>
							<td class="top">
								<span class="right"><a href="http://last.fm/user/atomaka"><img src="img/badges/lastfm.png" class="icon" alt="Andrew Tomaka's Last.fm" /></a></span>
								<a href="<?php echo $data->lastfm->url ?>"><?php echo $data->lastfm->song ?></a><br/>
								by <?php echo $data->lastfm->artist ?><br/><br/>
								<?php echo ($data->lastfm->time == 0) ? 'currently playing' : time_since($data->lastfm->time) . ' ago' ?><br/>
							</td>
						</tr>
					</table>
				</div>
				<!--<div id="hulu" class="box">
					<table class="formatting">
						<tr>
							<td class="formatting">
								<img src="<?php echo $data->hulu->thumb ?>" class="cover" alt="<?php echo $data->hulu->title ?> - <?php echo $data->hulu->series ?>" />
							</td>
							<td class="top">
								<span class="right"><a href="http://www.hulu.com/profiles/atomaka"><img src="img/badges/hulu.png" class="icon" alt="Andrew Tomaka's Hulu" /></a></span>
								<a href="<?php echo $data->hulu->url ?>"><?php echo $data->hulu->title ?></a><br/>
								from <?php echo $data->hulu->series ?><br/><br/>
								<?php echo time_since($data->hulu->time) ?> ago<br/>
							</td>
						</tr>
					</table>
				</div>-->
				<br/>
				<h2>games</h2>
				<h3>World of Warcraft</h3>
				<div id="wow" class="box">
					<table class="formatting">
						<tr>
							<td class="gamesformatting"><a href="http://battle.net/wow"><img src="img/badges/wow.jpg" alt="World of Warcraft" /></a></td>
							<td class="top">
								<table class="classformatting">
									<tr>
<?php
foreach($data->wow as $character) {
	echo sprintf('										<td class="classformatting"><a href="%s"><img src="http://wow.zamimg.com/images/wow/icons/small/class_%s.jpg" alt="%s" /></a></td>' . "\n",$character->armory,$character->class,$character->name);
}
?>
									</tr>
									<tr>
<?php
foreach($data->wow as $character) {
	echo sprintf('										<td class="classformatting"><img src="http://wow.zamimg.com/images/wow/icons/small/%s.jpg" alt="%s" /></td>' . "\n",$character->spec_icon,$character->class,$character->spec_name);
}
?>									
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
				<h3>Diablo 3</h3>
				<h3>Starcraft 2</h3>
				<div id="sc2" class="box">
					<table class="formatting">
						<tr>
							<td class="gamesformatting"><a href="http://battle.net/d3"><img src="img/badges/diablo3.png" alt="Diablo 3" /></a></td>
							<td class="top">
								<a href="http://us.battle.net/d3/en/profile/Tomaka-1761/">Tomaka#1761</a><br/>
								Paragon: <?php echo $data->diablo->highestParagon ?><br/>
								Elite Kills: <?php echo $data->diablo->elites ?>
							</td>
							<td class="right,top"><img src="img/d3/barbarian.jpg" alt="<?php echo ucfirst($data->sc2ranks->league) ?> League" /></td>
						</tr>
					</table>
				</div>
				<div id="sc2" class="box">
					<table class="formatting">
						<tr>
							<td class="gamesformatting"><a href="http://battle.net/sc2"><img src="img/badges/sc2.jpg" alt="Starcraft 2" /></a></td>
							<td class="top">
								<a href="http://us.battle.net/sc2/en/profile/1680730/1/Gaffer/">Gaffer.888</a><br/>
								<?php echo $data->sc2ranks->division ?><br/>
								<?php echo $data->sc2ranks->points ?> points, Rank <?php echo $data->sc2ranks->rank ?><br/>
								<?php echo $data->sc2ranks->wins ?> wins<br/>
							</td>
							<td class="right,top"><img src="img/sc2/<?php echo $data->sc2ranks->league ?>.png" alt="<?php echo ucfirst($data->sc2ranks->league) ?> League" /></td>
						</tr>
					</table>
				</div>
				<h3>Steam</h3>
				<div id="steam" class="box">
					<table class="formatting">
						<tr>
							<td class="gamesformatting"><a href="http://steamcommunity.com/"><img src="img/badges/steam.jpg" alt="Steam" /></a></td>
							<td class="top">
								<a href="http://steamcommunity.com/id/toppazz">Toppazz</a><br/>
								<?php echo $data->steam->hours ?> hours in the last two weeks.<br/>
<?php
	foreach($data->steam->recent as $game) {
?>
								<a href="<?php echo $game->link ?>"><?php echo $game->name ?></a>: <?php echo $game->hours ?> hours.<br/>
<?
	}
?>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="clear">&nbsp;</div>
		</div>
		<br/>

		<div id="copyright">&copy; Andrew Tomaka 2010-2011. [ <a href="admin">admin</a> ]</div>
		<br/><br/>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script> 
		<script type="text/javascript" src="js/jquery.qtip-1.0.0-rc3.min.js"></script>
		<script type="text/javascript" src="js/jquery.corner.js"></script>
		<script type="text/javascript">	
$(document).ready(function() {
	$('img[title]').qtip({ 
		style: {  
			tip: true,
			border: {
				width: 0,
				radius: 5,
				color: '#74aa81'
			},
			color: '#216332'
		} 
	});
	
	$('#content').corner();
	$('.box').corner('5px');
});


		</script>
	</body>
</html><?php
function time_since($time) {
    $periods = array(
		'minute'		=> 60,
		'hour'			=> 60 * 60,
		'day'			=> 60 * 60 * 24,
		'week'			=> 60 * 60 * 24 * 7,
		'month'			=> 60 * 60 * 24 * 30,
		'year'			=> 60 * 60 * 24 * 365,
	);
    
    $now = time();
    $since = $now - $time;

	$formatted_since = array($since,'seconds');
	foreach($periods as $period => $seconds) {
		$quotient = floor($since / $seconds);
		
		if($quotient >= 1)	$formatted_since = array($quotient,$period);
		else break;
	}
	
	if($formatted_since[0] > 1) $formatted_since[1] .= 's';
	return implode(' ',$formatted_since);
}
?>