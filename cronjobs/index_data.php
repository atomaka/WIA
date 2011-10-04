<?php
//***************************************************************************//
//	index_data.php - schedule on the system:
//	* * * * * php cronjobs/index_data.php >/dev/null 2>&1
//	Collect data from various services and store locally for later display.
//***************************************************************************//
if($_SERVER['SERVER_NAME'] == 'localhost') {
	$CACHE_FILE = '../data/cache.txt';
	$DATA_FILE = '../data/index.txt';
} else {
	chdir('/home/atomaka/data');
	
	$CACHE_FILE = 'cache.txt';
	$DATA_FILE = 'index.txt';
}

$dataSources = array(
	//function,		cache_duration
	'twitter'		=> 300,
	'github'		=> 300,
	'hulu'			=> 600,
	'lastfm'		=> 60,
	'sc2ranks'		=> 43200,
	'steam'			=> 3600,
);

$cacheData = json_decode(file_get_contents($CACHE_FILE),true);
$sourceData = json_decode(file_get_contents($DATA_FILE),true);
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
	
	if(empty($tweetInfo)) {
		return array(
			'text'		=> 'Last post was a retweet and cannot be listed.',
			'time'		=> 0,
		);
	} else {
		$tweet = urlify($tweetInfo->text);
		
		return array(
			'text'		=> $tweet,
			'time'		=> strtotime($tweetInfo->created_at),
		);
	}
}

function github() {
	// get the last repo we worked on
	$url = 'https://api.github.com/users/atomaka/repos';
	$repos = json_decode(curl_request($url));
	
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
	
	return array(
		'song'			=> (string)$latestSong->name,
		'artist'		=> (string)$latestSong->artist,
		'time'			=> strtotime($latestSong->date . ' UTC'),
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
	preg_match('/<img src="(.*)" align="right"/',(string)$lastShow->description,$thumb);

	return array(
		'title'			=> $title[2],
		'series'		=> $title[0],
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

function urlify($string) {
	$pattern ="{\\b((https?|telnet|gopher|file|wais|ftp) : [\\w/\\#~:.?+=&%@!\\-]+?) (?= [.:?\\-]* (?:[^\\w/\\#~:.?+=&%@!\\-] |$) ) }x"; 
	return preg_replace($pattern,"<a href=\"$1\">$1</a>", $string); 
}

function github_sort($a, $b) {
	return strtotime($a->pushed_at) < strtotime($b->pushed_at);
}

?>