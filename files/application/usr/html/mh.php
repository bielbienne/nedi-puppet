<?php
# Program: mh.php (Mobile Health)
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

$refresh   = 60;
$printable = 0;
$firstmsg  = time() - 86400;

$_SESSION['lim']  = 3;
$_SESSION['col']  = 4;
$_SESSION['vol']  = 100;
$_SESSION['gsiz'] = 6;
$_SESSION['lsiz'] = 8;
$_SESSION['view'] = "";
$_SESSION['date'] = 'j.M y G:i';
$_SESSION['tz'] = "GMT";

$self = "mh";
$modgroup[$self] = "mon";

require_once ("inc/libmisc.php");
ReadConf('mon');
include_once ("./languages/english/gui.php");							# Don't require, GUI still works if missing
include_once ("inc/libdb-" . strtolower($backend) . ".php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");

$_GET = sanitize($_GET);
$reg = isset($_GET['reg']) ? $_GET['reg'] : "";
$cty = isset($_GET['cty']) ? $_GET['cty'] : "";
$bld = isset($_GET['bld']) ? $_GET['bld'] : "";
$loc = TopoLoc($reg,$cty,$bld);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>

<head>
<title>NeDi Mobile Health</title>
<meta http-equiv="refresh" content="60">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1">
<link href="inc/print.css" type="text/css" rel="stylesheet">
<link rel="shortcut icon" href="img/favicon.ico">
</head>

<body>

<table width="640"><tr class="mon1">
<td valign="top" align="center">
<p>
<?PHP

$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);

list($nmon,$lastok,$monal,$deval,$slow) = TopoMon($loc);

StatusMon($nmon,$lastok,$monal,$deval);

StatusIncidents($loc);

StatusSlow($slow);

?>
</td>
<td valign="top" align="center">

<?php

StatusIf($loc,'bbup');
StatusIf($loc,'bbdn');

$query	= GenQuery('interfaces','s','count(*),round(sum(poe)/1000)','','',array('poe','location'),array('>','like'),array('0',$loc),array('AND'),'JOIN devices USING (device)');
$res	= DbQuery($query,$link);
if($res){
	$m = DbFetchRow($res);
	if($m[0]){echo "<p><b><img src=\"img/32/batt.png\" title=\"$m[0] PoE IF\">$m[1] W</b>\n";}
	DbFreeResult($res);
}else{
	print DbError($link);
}
?>

</td>
<td valign="top" align="center">

<?php
StatusIf($loc,'brup');
StatusIf($loc,'brdn');
StatusIf($loc,'bdis');
?>

</td>
<td valign="top" align="center">

<?php
StatusCpu($loc);
StatusMem($loc);
StatusTmp($loc);
?>

</td></tr>
<tr><td colspan="4">

<h2><?= $mlvl[200] ?> & <?= $mlvl[250] ?> <?= $tim['t'] ?></h2>
<?php

Events($_SESSION['lim'],array('level','time','location'),array('>=','>','like'),array(200,$firstmsg,$loc),array('AND','AND'),3);

TopoTable($reg,$cty,$bld);

if( count($dreg) == 1 ){
	$reg = array_pop ( array_keys($dreg) );
	if( count($dcity[$reg]) == 1 ){
		$cty = array_pop ( array_keys($dcity[$reg]) );
	}
}

if(!$reg and count($dreg) > 1){
	TopoRegs(1);
}elseif(!$cty){
	TopoCities($reg,1);
}elseif(!$bld){
	TopoBuilds($reg,$cty,1);
}else{
	TopoFloors($reg,$cty,$bld,1);
}

?>

</tr></table>
</body>
