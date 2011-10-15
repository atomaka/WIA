<?php
//***************************************************************************//
//	index_data.php - schedule on the system:
//	* * * * * php cronjobs/index_data.php >/dev/null 2>&1
//	Collect data from various services and store locally for later display.
//***************************************************************************//
$startTime = time();
$interruptedExecution = false;
register_shutdown_function('shutdown');
// make sure our file_exist returns fresh data.  Likely not necessary.
clearstatcache();

if(in_array($_SERVER['SERVER_NAME'],array('localhost','a.io'))) {
	$DATABASE_FILE = getcwd() . '/../conf/database.conf';
	$CACHE_FILE = '../data/cache.txt';
	$DATA_FILE = '../data/index.txt';
	$LOCK_FILE = getcwd() . '/../data/whoisandrew.lock';
} else {
	chdir('/home/atomaka/data');
	
	$DATABASE_FILE = '/home/atomaka/conf/database.conf';
	$CACHE_FILE = 'cache.txt';
	$DATA_FILE = 'index.txt';
	// path changes in the shutdown() function so we need the full path
	$LOCK_FILE = '/home/atomaka/data/whoisandrew.lock';
}

// All the sources we intend on pulling data from with a corresponding
// cache lifetime.
$dataSources = array(
	'twitter'		=> 300,
	'github'		=> 300,
	'hulu'			=> 600,
	'lastfm'		=> 60,
	'sc2ranks'		=> 43200,
	'steam'			=> 3600,
	'wow'			=> 43200,
);

// Make sure that the script does not begin execution if it is already.
if(!file_exists($LOCK_FILE)) {
	touch($LOCK_FILE);
} else {
	$interruptedExecution = true;
	exit();
}

// In case our data files are not present
if(file_exists($CACHE_FILE)) {
	$cacheData = json_decode(file_get_contents($CACHE_FILE),true);
} else {
	$cacheData = array();
}
if(file_exists($DATA_FILE)) {
	$sourceData = json_decode(file_get_contents($DATA_FILE),true);
} else {
	$sourceData = array();
}

foreach($dataSources as $dataSource=>$refreshTime) {
	// check last time the data was updated
	$lastModified = (array_key_exists($dataSource, $cacheData)) ?
		$cacheData[$dataSource] : 0;
	
	// and see if we need to retrieve new information
	if(time() - $lastModified > $refreshTime) {
		$cacheData[$dataSource] = time();
		
		$sourceData[$dataSource] = call_user_func($dataSource);
	}
}

file_put_contents($CACHE_FILE,json_encode($cacheData));
file_put_contents($DATA_FILE,json_encode($sourceData));


//***************************************************************************//
//	Data sources
//***************************************************************************//
function twitter() {
	$url = 'http://www.twitter.com/statuses/user_timeline/atomaka.json?count=1';
	$tweetInfo = json_decode(file_get_contents($url));
	
	// An empty result set currently (always?) means that the last post was
	// retweeted.
	if(empty($tweetInfo)) {
		return array(
			'text'		=> 'Last post was a retweet and cannot be listed.',
			'time'		=> 0,
		);
	} else {
		$tweet = urlify($tweetInfo[0]->text);
		
		return array(
			'text'		=> $tweet,
			'time'		=> strtotime($tweetInfo[0]->created_at),
		);
	}
}

function github() {
	// get the last repo we worked on
	$url = 'https://api.github.com/users/atomaka/repos';
	$repos = json_decode(curl_request($url));
	
	// & notation for a variable to be passed by reference is actually
	// deprecated and will cause a warning in 5.3.  However, it is
	// required to work in 5.2
	usort(&$repos,'github_sort');
	
	// and then get the last commit to that repo
	$url = sprintf('https://api.github.com/repos/atomaka/%s/commits',
		$repos[0]->name);
	$commits = json_decode(curl_request($url));
	
	return array(
		'commit'	=> $commits[0]->commit->message,
		'repo'		=> $repos[0]->name,
		'url'		=> $repos[0]->html_url,
	);
}

function lastfm() {
	$url = 'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=atomaka&limit=1&api_key=27ea07733c17562cf1fe512586954825';
	$xml = simplexml_load_file($url);
	$latestSong = $xml->recenttracks->track[0];
	
	$cover = (is_array($latestSong->image)) ? 
		'img/lastfm/blank_album64.png' : (string)$latestSong->image[1];
	$time = (isset($latestSong->attributes()->nowplaying) && 
		(bool)$latestSong->attributes()->nowplaying) ?
		0 : strtotime($latestSong->date . ' UTC');
	
	return array(
		'song'			=> (string)$latestSong->name,
		'artist'		=> (string)$latestSong->artist,
		'time'			=> $time,
		'url'			=> (string)$latestSong->url,
		'cover'			=> $cover,
	);
}

function sc2ranks() {
	$url = 'http://sc2ranks.com/api/base/teams/us/Gaffer$888.json?appKey=whoisandrew.com';
	$profile = json_decode(file_get_contents($url));
	
	// find the 1v1 team
	foreach($profile->teams as $team) {
		if($team->bracket == 1) break;
	}

	return array(
		'league'		=> $team->league,
		'division'		=> $team->division,
		'rank'			=> $team->division_rank,
		'points'		=> $team->points,
		'wins'			=> $team->wins,
	);
}

function hulu() {
	$url = 'http://www.hulu.com/feed/history/atomaka';
	$xml = simplexml_load_file($url);

	// data for last show
	$lastShow = $xml->channel->item[0];

	$title = explode(' - ', $lastShow->title);
	preg_match('/<img src="(.*)" align="right"/',(string)$lastShow->description,
		$thumb);

	return array(
		'series'		=> isset($title[2]) ? $title[2] : 'Not Available',
		'title'			=> $title[0],
		'time'			=> strtotime($lastShow->pubDate),
		'url'			=> (string)$lastShow->link,
		'thumb'			=> $thumb[1],
	);
}

function steam() {
	$url = 'http://steamcommunity.com/profiles/76561197993725971/?xml=1';
	$xml = simplexml_load_file($url);

	// find the most recently played games
	$recentGames = array();
	if(isset($xml->mostPlayedGames)) {
		foreach($xml->mostPlayedGames->mostPlayedGame as $game) {
			$recentGames[] = array(
				'name'		=> (string)$game->gameName,
				'link'		=> (string)$game->gameLink,
				'hours'		=> (float)$game->hoursPlayed,
			);
		}
	}
	
	return array(
		'hours'		=> (float)$xml->hoursPlayed2Wk,
		'recent'	=> $recentGames,
	);
}

function wow() {
	$CLASSES = array(
		6		=> 'deathknight',
		5		=> 'priest',
		11		=> 'druid',
		4		=> 'rogue',
		8		=> 'mage',
		7		=> 'shaman',
		1		=> 'warrior',
		9		=> 'warlock',
		3		=> 'hunter',
	);

	$characters = array(
		'Gaffer'		=> false,
		'Getburnt'		=> false,
		'Veincane'		=> false,
		'Toppazz'		=> false,
		'Toopro'		=> false,
		'Levita'		=> false,
		'Ttg'			=> false,
		'Notgaffer'		=> false,
		'Loveglove'		=> false,
	);
	$currentInstance = 25;	// 25 = Firelands
	
	// build our mutli curl request
	$mh = curl_multi_init();
	foreach($characters as $character=>$data) {
		$url = sprintf('http://us.battle.net/api/wow/character/crushridge/%s?fields=progression,talents',
			$character);
			
		$characters[$character] = curl_prep($url);
		curl_multi_add_handle($mh, $characters[$character]);
	}
	
	// execute the multi curl request
	$running = 0;
	do {
		curl_multi_exec($mh, $running);
	} while($running > 0);
	
	// and process the results
	$characterData = array();
	foreach($characters as $character=>$data) {
		$json = json_decode(
			curl_multi_getcontent($characters[$character])
		);

		// merge heroic and normal ragnaros
		$bosses = $json->progression->raids[$currentInstance]->bosses;
		$bosses[6]->heroicKills = $bosses[7]->heroicKills;
		unset($bosses[7]);
		
		// find the boss with the lowest kills
		$leastN = 1000;
		$leastH = 1000;
		foreach($bosses as $boss) {
			// -1 means that the boss has never but killed on normal, but
			// has been on heroic so it's safe to reset to 0 for our purposes.
			if($boss->normalKills == -1) $boss->normalKills = 0;
			if(($boss->normalKills + $boss->heroicKills) < $leastN) {
				$leastN = $boss->normalKills + $boss->heroicKills;
			}
			if($boss->heroicKills < $leastH) $leastH = $boss->heroicKills;
		}

		//find our active talent tree
		foreach($json->talents as $talent) {
			if(isset($talent->selected)) {
				break;
			}
		}
		
		$characterData[$character] = array(
			'name'			=> $character,
			'level'			=> $json->level,
			'class'			=> $CLASSES[$json->class],
			'progression'	=> $leastH > 0 ? $leastH * 100 : $leastN,
			'armory'		=> sprintf('http://us.battle.net/wow/en/character/crushridge/%s/advanced',$character),
			'spec_icon'		=> $talent->icon,
			'spec_name'		=> $talent->name,
		);
	}
	
	// & notation for a variable to be passed by reference is actually
	// deprecated and will cause a warning in 5.3.  However, it is
	// required to work in 5.2
	usort(&$characterData,'progression_sort');

	return $characterData;
}


//***************************************************************************//
//	Helper functions
//***************************************************************************//
function curl_request($url) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	
	$contents = curl_exec($curl);
	curl_close($curl);
	
	return $contents;
}

function curl_prep($url) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	
	return $curl;
}

function urlify($string) {
	$pattern ="{\\b((https?|telnet|gopher|file|wais|ftp) : [\\w/\\#~:.?+=&%@!\\-]+?) (?= [.:?\\-]* (?:[^\\w/\\#~:.?+=&%@!\\-] |$) ) }x"; 
	
	return preg_replace($pattern,"<a href=\"$1\">$1</a>", $string); 
}

function github_sort($a, $b) {
	return strtotime($a->pushed_at) < strtotime($b->pushed_at);
}

function progression_sort($a, $b) {
	return $a['progression'] < $b['progression'];
}

function shutdown() {
	// need to make the variables we need available
	global $interruptedExecution, $startTime, $LOCK_FILE, $DATABASE_FILE;
	
	$db_conf = json_decode(file_get_contents($DATABASE_FILE));
	$db = mysqli_init();
	$db->real_connect($db_conf->hostname,$db_conf->username,$db_conf->password,
		$db_conf->database);
	
	// $interruptedExecution is true if our lock file still existed when the 
	// script began execution.  true also implies that the lock file does not
	// exist.
	if($interruptedExecution) {
		$query = "INSERT INTO wia_log (time,type,description) VALUES(NOW(),
			'warning',
			'The script attempted to run while another copy was already processing')";
		$db->query($query);
	} else {
		unlink($LOCK_FILE);
	}

	$completionTime = time() - $startTime;

	// If the script took longer to execute than the server allows and the server
	// does not have an unlimited execution time
	if($completionTime >= ini_get('max_execution_time') &&
		ini_get('max_execution_time') != 0) {
		$message = 'The script reached the maximum execution time: ' . 
			$completionTime;
		$query = "INSERT INTO wia_log (time,type,description) VALUES(NOW(),
			'warning','$message')";
		$db->query($query);
	}
}

?>