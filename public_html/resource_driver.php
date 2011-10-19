<?php


include 'lib/resource.php';
$configs = array();

$twt_config = array(
    'name'      => 'Twitter',
    'type'      => 'json',
    'url'       => 'https://api.twitter.com/1/statuses/user_timeline.json?include_entities={include_entities}&include_rts={include_rts}&screen_name={screen_name}&count={count}',
    'required'  => array('screen_name'),
    'arguments' => array('atomaka',1)
);
// $configs[] = json_encode($twt_config);


$sc2_config = array(
    'name'      => 'Sc2Ranks',
    'type'      => 'json',
    'url'       => 'http://sc2ranks.com/api/base/teams/us/{character}${code}.json?appKey={key}',
    'required'  => array('character','code','key'),
    'arguments' => array('Gaffer','888','whoisandrew.com')
);
// $configs[] = json_encode($sc2_config);

$data = array();
foreach($configs as $config) {
    $json = json_decode($config);
    include 'lib/' . strtolower($json->name) . '.php';

    // prepare arugments
    $arguments = array();
    foreach($json->arguments as $argument) {
        $arguments[] = $argument;
    }

    $res = new $json->name($config);
    call_user_func_array(array($res,'setOptions'), $arguments);
    $data[$json->name] = $res->getParsedData();
    //$res->debugInformation();
}

echo '<hr/><h1>Return Results</h1><hr/><pre>';
print_r($data);
echo '</pre>';
  
?>