<?php
$db_config = json_decode(file_get_contents('../../conf/database.conf'));
$api_config = json_decode(file_get_contents('../../conf/api.conf'));

include_once('../lib/database.php');
$db = new Database($db_config->hostname,$db_config->username,$db_config->password,$db_config->database);

$key = $_GET['link_key'];
if($key != $api_config->key) die('{"message":"Access Denied."}');
	
$url = $_GET['url'];
$title = $_GET['title'];

if(filter_var($url,FILTER_VALIDATE_URL) == false || !preg_match('{http://}',$url))
	die('{"message":"Malformed URL."}');

if($db->connect_error) die('{"message":"No Database Connection."}');
	
$db->query("INSERT INTO wia_links (url,text,status) VALUES ('$url','$title',0)");
if($db->error) die('{"message":"Could Not Add."}');
	
$db->close();
?>
{}