<?php
# Program: me.php (Locate me IP)
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

$refresh   = 60;
$printable = 0;

$_SESSION['gsiz'] = 6;
$_SESSION['lsiz'] = 8;
$_SESSION['view'] = "";
$_SESSION['date'] = 'j.M y G:i';
$_SESSION['tz'] = "GMT";

$self = "mh";
$modgroup[$self] = "mon";

require_once ("inc/libmisc.php");
ReadConf();
include_once ("./languages/english/gui.php");							# Don't require, GUI still works if missing
include_once ("inc/libdb-" . strtolower($backend) . ".php");
require_once ("inc/libnod.php");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>

<head>
<title>NeDi Find Me</title>
<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1">
<link href="inc/print.css" type="text/css" rel="stylesheet">
<link rel="shortcut icon" href="img/favicon.ico">
</head>

<body>

<?PHP
$link  = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query = GenQuery('nodes','s','nodes.*,location,speed,duplex,pvid,dinoct,doutoct,dinerr,douterr,dindis,doutdis,dinbrc','lastseen','1',array('nodip'),array('='),array( ip2long($_SERVER[REMOTE_ADDR]) ),array(),'LEFT JOIN devices USING (device) LEFT JOIN interfaces USING (device,ifname)');
$res   = DbQuery($query,$link);
if($res){
	$n   = DbFetchRow($res);
	if($n[2]){
		$img = Nimg($n[3]);
		$l   = explode($locsep,$n[23]);
		echo "<table class=\"mon2\">";
		echo "<tr class=\"txta\"><th class=\"imga\" width=\"20\"><img src=\"img/oui/$img.png\" title=\"$n[3]\"></th><td><b>$n[0]</b></td><td class=\"mrn code\">$n[2]</td></tr>\n";
		echo "<tr class=\"txtb\"><th class=\"imgb\" width=\"20\"><img src=\"img/16/net.png\" title=\"Network\"></th><td class=\"blu code\">".long2ip($n[1])."</td><td class=\"prp code\">".(($n[16])?inet_ntop($n[16]):'')."</td></tr>\n";
		echo "<tr class=\"txta\"><th class=\"imga\" width=\"20\"><img src=\"img/16/dev.png\" title=\"Device\"></th><td>$n[6]</td><td>$l[2] $l[3]</td></tr>\n";
		echo "<tr class=\"txtb\"><th class=\"imgb\" width=\"20\"><img src=\"img/16/port.png\" title=\"Interface\"></th><td>$n[7]</td><td>".DecFix($n[24])."-$n[25] vl$n[8]</td></tr>\n";
		echo "<tr class=\"txta\"><th class=\"imga\" width=\"20\"><img src=\"img/16/grph.png\" title=\"In/Out\"></th><td colspan=\"2\">Traffic: <b class=\"blu code\">".DecFix($n[27])."/".DecFix($n[28])."</b> Errors:<b class=\"drd code\"> ".DecFix($n[29])."/".DecFix($n[30])."</b> Discards:<b class=\"prp code\">".DecFix($n[31])."/".DecFix($n[32])." </b> Bcast:<b class=\"dgy code\"> ".DecFix($n[33])."</b></td></tr>\n";
		echo "</table>";
	}else{
		echo "<h4>$_SERVER[REMOTE_ADDR] was not found</h4>";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}
?>

</body>
