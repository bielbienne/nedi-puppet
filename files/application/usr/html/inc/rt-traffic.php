<?php

session_start();
if( !preg_match("/net/",$_SESSION['group']) ){
	echo ":-P";
	die;
}

require_once ("libsnmp.php");
include_once ("libmisc.php");
$_GET = sanitize($_GET);
$ver  = $_GET['v'] & 3;

if($ver == 3){												# Need to load credentials for SNMPv3
	$nedipath  = preg_replace( "/^(\/.+)\/ht\w+\/.+.php/","$1",$_SERVER['SCRIPT_FILENAME']);	# Guess NeDi path for nedi.conf
	ReadConf();
}

$debug  = isset($_GET['debug']) ? $_GET['debug'] : "";

if($_GET['ip'] and $ver and $_GET['c'] and $_GET['i']){
	$ioctO = ($_GET['v'] & 192 == 128)?'1.3.6.1.2.1.31.1.1.1.6':'1.3.6.1.2.1.2.2.1.10';	# 128=HC, 64=Merge with 32bit thus resort to 32bit as a view seconds intervall won't be a problem, even on 10G!
	$ooctO = ($_GET['v'] & 192 == 128)?'1.3.6.1.2.1.31.1.1.1.10':'1.3.6.1.2.1.2.2.1.16';
# I don't understand why PHP sometimes returns the types as well....only sometimes?!??!?
	$io = preg_replace("/Counter[0-9]{2}: /","",Get($_GET['ip'], $ver, $_GET['c'], "$ioctO.$_GET[i]",3000000));
	$oo = preg_replace("/Counter[0-9]{2}: /","",Get($_GET['ip'], $ver, $_GET['c'], "$ooctO.$_GET[i]",3000000));
	echo microtime(true)."|$io|$oo";
}
?>
