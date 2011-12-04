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

if(isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'],array('localhost','a.io'))) {
	$conf = json_decode(file_get_contents(getcwd() . '/../conf/wia.conf'));
} else {
	$conf = json_decode(file_get_contents('/home/atomaka/conf/wia.conf'));
}

define('CACHE',$conf->site->path . '/data/cache.txt');
define('DATA',$conf->site->path . '/data/index.txt');
define('LOCK',$conf->site->path . '/data/whoisandrew.lock');

// All the sources we intend on pulling data from with a corresponding
// cache lifetime.
$dataSources = array(
	'twitter'		=> 300,
	'github'		=> 300,
	'hulu'			=> 600,
	'lastfm'		=> 60,
	// 'sc2ranks'		=> 43200,
	'steam'			=> 3600,
	'wow'			=> 43200,
);

// Make sure that the script does not begin execution if it is already.
if(!file_exists(LOCK)) {
	touch(LOCK);
} else {
	$interruptedExecution = true;
	exit();
}

// In case our data files are not present
if(file_exists(CACHE)) {
	$cacheData = json_decode(file_get_contents(CACHE),true);
} else {
	$cacheData = array();
}
if(file_exists(DATA)) {
	$sourceData = json_decode(file_get_contents(DATA),true);
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
		
		$data = call_user_func($dataSource);
        
        if($data != false) { 
            echo 'updating ' . $dataSource . '<br/>';
            $sourceData[$dataSource] = $data;
        } else {
            echo 'failed ' . $dataSource . '<br/>';
            $cacheData[$dataSource] = 0;
        }
	}
}

file_put_contents(CACHE,json_encode($cacheData));
file_put_contents(DATA,json_encode($sourceData));


//***************************************************************************//
//	Data sources
//***************************************************************************//
function twitter() {
	$url = 'https://api.twitter.com/1/statuses/user_timeline.json?include_entities=true&include_rts=true&screen_name=atomaka&count=1';
    $tweetInfo = json_decode(curl_request($url));
    
	// An empty result set currently (always?) means that the last post was
	// retweeted.
	if(empty($tweetInfo)) {
		$data = array(
			'text'		=> 'Last post was a retweet and cannot be listed.',
			'time'		=> 0,
		);
	} else {
		$tweet = urlify($tweetInfo[0]->text);
		
		$data = array(
			'text'		=> $tweet,
			'time'		=> strtotime($tweetInfo[0]->created_at),
		);
	}
    
    if(isset($data['text']) && isset($data['time'])) {
        return $data;
    } else {
        return false;
    }
}

function github() {
	// get the last repo we worked on
	$url = 'https://api.github.com/users/atomaka/repos';
	$repos = json_decode(curl_request($url));
	
	// & notation for a variable to be passed by reference is actually
	// deprecated and will cause a warning in 5.3.  However, it is
	// required to work in 5.2
	usort($repos,'github_sort');
	
	// and then get the last commit to that repo
	$url = sprintf('https://api.github.com/repos/atomaka/%s/commits',
		$repos[0]->name);
	$commits = json_decode(curl_request($url));
	
	$data = array(
		'commit'	=> $commits[0]->commit->message,
		'repo'		=> $repos[0]->name,
		'url'		=> $repos[0]->html_url,
	);
    
    if(isset($data['commit']) && isset($data['repo']) && isset($data['url'])) {
        return $data;
    } else {
        return false;
    }
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
	
	$data = array(
		'song'			=> (string)$latestSong->name,
		'artist'		=> (string)$latestSong->artist,
		'time'			=> $time,
		'url'			=> (string)$latestSong->url,
		'cover'			=> $cover,
	);
    
    if(isset($data['song']) && isset($data['artist']) && isset($data['time']) && isset($data['url']) && isset($data['cover'])) {
        return $data;
    } else {
        return false;
    }    
}

function sc2ranks() {
	$url = 'http://sc2ranks.com/api/base/teams/us/Gaffer$888.json?appKey=whoisandrew.com';
	$profile = json_decode(file_get_contents($url));

	// find the 1v1 team
	foreach($profile->teams as $team) {
		if($team->bracket == 1) break;
	}
    
	$data = array(
		'league'		=> $team->league,
		'division'		=> $team->division,
		'rank'			=> $team->division_rank,
		'points'		=> $team->points,
		'wins'			=> $team->wins,
	);
    
    if(isset($data['league']) && isset($data['division']) && isset($data['rank']) && isset($data['points']) && isset($data['wins'])) {
        return $data;
    } else {
        return false;
    }
}

function hulu() {
	$url = 'http://www.hulu.com/feed/history/atomaka';
	$xml = simplexml_load_file($url);

	// data for last show
	$lastShow = $xml->channel->item[0];

	$title = explode(' - ', $lastShow->title);
	preg_match('/<img src="(.*)" align="right"/',(string)$lastShow->description,
		$thumb);

	$data = array(
		'series'		=> isset($title[2]) ? $title[0] : 'Not Available',
		'title'			=> isset($title[2]) ? $title[2] : $title[0],
		'time'			=> strtotime($lastShow->pubDate),
		'url'			=> (string)$lastShow->link,
		'thumb'			=> $thumb[1],
	);
    
    if(isset($data['series']) && isset($data['title']) && isset($data['time']) && isset($data['url']) && isset($data['thumb'])) {
        return $data;
    } else {
        return false;
    }
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
	
	$data = array(
		'hours'		=> (float)$xml->hoursPlayed2Wk,
		'recent'	=> $recentGames,
	);
    
    if(isset($data['hours']) && isset($data['recent'])) {
        return $data;
    } else {
        return false;
    }
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
		'Trinikwan' 	=> false,
		'Notgaffer'		=> false,
		'Loveglove'		=> false,
	);
	$currentInstance = 25;	// 25 = Firelands
	
	// build our mutli curl request
	$mh = curl_multi_init();
	foreach($characters as $character=>$data) {
		$url = sprintf('http://us.battle.net/api/wow/character/crushridge/%s?fields=items,talents',
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

		//find the average item level
		$ilvl = $json->items->averageItemLevel;

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
			'ilvl' 			=> $ilvl,
			'armory'		=> sprintf('http://us.battle.net/wow/en/character/crushridge/%s/advanced',$character),
			'spec_icon'		=> $talent->icon,
			'spec_name'		=> $talent->name,
		);
	}
	// & notation for a variable to be passed by reference is actually
	// deprecated and will cause a warning in 5.3.  However, it is
	// required to work in 5.2
	usort($characterData,'ilvl_sort');

    
	$data = $characterData;
    
    foreach($data as $character) {
        if(!isset($character['name'])) return false;
        if(!isset($character['level'])) return false;
        if(!isset($character['class'])) return false;
        if(!isset($character['ilvl'])) return false;
        if(!isset($character['armory'])) return false;
        if(!isset($character['spec_icon'])) return false;
        if(!isset($character['spec_name'])) return false;
    } 
    
    return $data;
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

function github_sort(&$a, &$b) {
	return strtotime($a->pushed_at) < strtotime($b->pushed_at);
}

function ilvl_sort(&$a, &$b) {
	return $a['ilvl'] < $b['ilvl'];
}

function shutdown() {
	// need to make the variables we need available
	global $interruptedExecution, $startTime, $conf;

	$db = mysqli_init();
	$db->real_connect($conf->db->hostname,$conf->db->username,$conf->db->password,
		$conf->db->database);
	
	// $interruptedExecution is true if our lock file still existed when the 
	// script began execution.  true also implies that the lock file does not
	// exist.
	if($interruptedExecution) {
		$query = "INSERT INTO wia_log (time,type,description) VALUES(NOW(),
			'warning',
			'The script attempted to run while another copy was already processing')";
		$db->query($query);
	} else {
		unlink(LOCK);
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