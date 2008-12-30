#!/usr/bin/php -q
<?php  

require_once('light/PolicyServer.php');

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();   
                   
$mySocketServer = new PolicyServer('localhost');  
$mySocketServer->start();
  
?>