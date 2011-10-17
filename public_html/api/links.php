<?php
$conf = json_decode(file_get_contents('../../conf/wia.conf'));

include_once('../lib/database.php');
$db = new Database($conf->db->hostname,$conf->db->username,$conf->db->password,$conf->db->database);

$key = $_GET['link_key'];
if($key != $db->site->key) die('{"message":"Access Denied."}');
	
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