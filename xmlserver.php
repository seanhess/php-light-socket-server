#!/usr/bin/php -q
<?php  

require_once('light/Server.php');
require_once('light/JsonEncoder.php');

error_reporting(E_ALL);
 
set_time_limit(0);
 
ob_implicit_flush();   
                   
$args = $_SERVER['argv'];
$flags = array(); 

foreach ($args as $arg)
{
	if (preg_match('/^-/',$arg))
		$flags[preg_replace('/^-/','', $arg)] = true;
}

if (empty($args[1]) || empty($args[2]))
{
	echo "\nUsage: xmlserver.php HOSTIP PORT (-log)\n";
	exit();
}

$ip = $args[1];
$port = $args[2];  

Message::$encoder = new JsonEncoder();  

$mySocketServer = new Server($ip, $port);  
$mySocketServer->start();
  
?>