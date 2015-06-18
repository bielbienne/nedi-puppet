<?php
//===============================
// NeDi Header
//===============================

$listlim   = 200;

error_reporting(E_ALL ^ E_NOTICE);

ini_set("memory_limit","128M");										# Enterprise network support TODO move to modules an adapt?

$self      = preg_replace("/.*\/(.+).php/","$1",$_SERVER['SCRIPT_NAME']);
#$uripath  = preg_replace("/^(.*\/).+.php/","$1",$_SERVER['SCRIPT_NAME']);				# HTML path on the webserver
#$guipath  = preg_replace( "/^(\/.+)\/.+.php/","$1",$_SERVER['SCRIPT_FILENAME']);			# Path to PHP scripts
$nedipath  = preg_replace( "/^(\/.+)\/ht\w+\/.+.php/","$1",$_SERVER['SCRIPT_FILENAME']);		# Guess NeDi path for nedi.conf

session_start();
if( isset($_SESSION['group']) ){
	if($_SESSION['tz']){date_default_timezone_set($_SESSION['tz']);}
	require_once ("libmisc.php");
	ReadConf($_SESSION['group']);
	$mos     = explode("-", $self);
	$selfi   = $mod[$mos[0]][$mos[1]];
	$nipl    = $_SESSION['nip'];									# Disables telnet:// and ssh:// links to allow browser add-ons
	$datfmt  = $_SESSION['date'];
	$now     = date($_SESSION['date']);
	$isadmin = (preg_match("/adm/",$_SESSION['group']) )?1:0;
	$debug   = (isset($_GET['debug']) and $isadmin)?1:0;
}else{
	echo "<script>document.location.href='index.php?goto=".urlencode(preg_replace('/^.*\/(\w+-\w+\.php.*)/','$1',$_SERVER["REQUEST_URI"]))."';</script>\n";
	exit;
}
include_once ("languages/$_SESSION[lang]/gui.php");							# Don't require, GUI still works if missing
include_once ("libdb-" . strtolower($backend) . ".php");

if( isset($_GET['xls']) ){
	$nipl = 1;											# Disable IP links in XLS exports...
	unset($refresh);										# Disable JavaScript in body tag
	header("Content-type: application/vnd.ms-excel; name='excel'");
	header("Content-Disposition: filename=$self.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
}elseif( isset($_GET['print']) ){
	$nipl = 1;											# ...and on printouts
	unset($refresh);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<link href="inc/print.css" type="text/css" rel="stylesheet">

<body>
<div id="header" class="<?= $modgroup[$self] ?>1">
<div style="float:right"><img src="<?= (( file_exists("themes/custom.png") )?"themes/custom":"img/nedi") ?>.png" height="32"></div>

<img src="img/32/<?= $selfi ?>.png" onClick="window.print();">
<?= $_SERVER['SERVER_NAME'] ?>
</div>
<?php
}else{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
<title>NeDi <?= $self ?></title>
<meta name="generator" content="NeDi <?= $_SESSION['ver'] ?>">
<meta http-equiv="Content-Type" content="text/html;charset=<?= $charset ?>">
<link href="themes/<?= $_SESSION['theme'] ?>.css" type="text/css" rel="stylesheet">
<link rel="shortcut icon" href="img/favicon.ico">

<script language="JavaScript" src="inc/JSCookMenu.js"></script>
<script language="JavaScript" src="inc/menutheme.js"></script>

<?= (isset($nocache))?"<meta http-equiv=\"cache-control\" content=\"no-cache\">\n":"" ?>
</head>

<body>
<?php
if( isset($refresh) ){
?>
<script language="javascript">
var interval = "";
var secs = <?= $refresh-1?>;

function countdown(){
	interval = window.setInterval("tick()",1000);
}

function tick(){
	var theElement = document.getElementById("counter");
	theElement.innerHTML = secs--;
	if (secs < 9){
		document.getElementById("counter").setAttribute("class", "drd");
	}
	if (secs == -1) {
		document.dynfrm.submit();
	}
}

// Idea richard.lajaunie
function stop_countdown(id){
	window.clearInterval(id);
	document.getElementById('counter').setAttribute('class', 'gry');
}

window.onload = countdown();

</script>
<?}?>
<table id="header">
<tr class="<?= $modgroup[$self] ?>1">
<?php if( isset($_SESSION['snap']) ){?>
<th width="50" class="warn"><a href="System-Snapshot.php"><img src="img/32/foto.png" title="Snapshot <?= $stco['100'] ?>: <?= $_SESSION['snap'] ?>"></a></th>
<?}else{?>
<th width="50"><a href="http://www.nedi.ch"><img src="img/n.png"></a></th>
<?}

if( preg_match('/Android|Mobile|Touch/',$_SERVER['HTTP_USER_AGENT']) ){
	echo "<td><table class=\"ThemeNSubMenuTable full\"><tr>";
	foreach( array_keys($mod) as $m){
		if($mos[0] == $m){
			echo "<th class=\"ThemeNMainItemActive\">$m</th>";
		}else{
			$s = current( array_keys($mod[$m]) );
			echo "<td><a href=\"$m-$s.php\">$m</a></td>";
		}
	}
	$col = 0;
	echo "</tr></table><table class=\"ThemeNSubMenuTable full\"><tr>";
	foreach($mod[$mos[0]] as $s => $i){
		$col++;
		if($mos[1] == $s){
			echo "<th class=\"ThemeNMainItemActive\">$s</th>";
		}else{
			echo "<td><a href=\"$mos[0]-$s.php\"><img src=\"img/16/$i.png\">$s</a></td>";
		}
		if($_SESSION['col'] == $col){
			echo "</tr>\n<tr>";
			$col = 0;
		}
	}
	echo "</tr></table></td>";
}else{
?>
<td ID="MainMenuID"></td>
<script language="JavaScript"><!--
var mainmenu = [
<?php
	foreach (array_keys($mod) as $m) {
		echo "	[null,'$m',null,null,null,\n";
		foreach ($mod[$m] as $s => $i) {
			echo "		['<img src=./img/16/$i.png>','$s','$m-$s.php',null,null],\n";
		}
		echo "	],\n";
	}
?>
];
cmDraw ('MainMenuID', mainmenu, 'hbr', cmThemeN, 'ThemeN');

function pop(URL,LBL){
	win = window.open('','Image','location=no,toolbar=no,titlebar=no,status=no,resizable=1,width=640,height=480');
	win.document.write('<head><title>'+LBL+'</title></head><body style="margin:0;"><img src="'+URL+'" title="Click to close" width="100%" height="100%" onClick="self.close();"></body>');
}
--></script>
<?php
}

if($_SESSION['opt']){

	if( isset($_GET['lim']) ){
		$_SESSION['listlim'] = preg_replace('/\D+/','',$_GET['lim']);
	}elseif( array_key_exists('listlim',$_SESSION) ){
		$listlim = $_SESSION['listlim'];
	}

	echo "<th>";
	$bc = 0;
	foreach ($_SESSION['bread'] as $prv) {
		$bc++;
		if($bc == $_SESSION['lim']){
			$bim = "";
		}else{
			$boc = intval(10 * $bc / $_SESSION['lim']);
			$bim = "style=\"opacity:0.$boc;filter:alpha(opacity=${boc}0);}\"";
		}
		preg_match("/(\w+)-(\w+).php/i",$prv,$mtitl);
		if($mod[$mtitl[1]][$mtitl[2]]){
			echo "<a href=\"$prv\"><img $bim src=\"img/16/".$mod[$mtitl[1]][$mtitl[2]].".png\" title=\"$prv\"></a>\n";
		}else{
			echo "<a href=\"$prv\"><img $bim src=\"img/16/bbox.png\" title=\"$prv\"></a>";
		}
	}
	echo "</th>";
	if( end($_SESSION['bread']) != $_SERVER['REQUEST_URI'] ){
		$_SESSION['bread'][] = $_SERVER['REQUEST_URI'];
		if(count($_SESSION['bread']) > $_SESSION['lim']){
			array_shift($_SESSION['bread']);
		}
	}
}
?>

<th width="120">
<img src="img/16/ring.png" title="Help" onclick="window.open('<?="languages/$_SESSION[lang]/$self.html" ?>','Help','width=640,height=480,scrollbars');">

<?php  if($printable) { ?>
<img src="img/dev/pgan.png" width="16" title="Print" onclick="window.open('?<?= $_SERVER['QUERY_STRING'] ?>&print=1','Print','width=1000,height=800,scrollbars');">
<?}?>

<?php  if($exportxls) { ?>
<img src="img/16/list.png" title="<?= $explbl ?> XLS" onclick="document.location.href='?<?= $_SERVER['QUERY_STRING'] ?>&xls=1';">
<?}?>

<?php  if($isadmin) { ?>
<a href="User-Profile.php?eam=<?= urlencode($_SERVER['REQUEST_URI']) ?>"><img src="img/16/note.png" title="Admin <?= $mlvl[100] ?> <?= $addlbl ?>"></a>
<?}?>

<th width="80">
<?php
if($isadmin and $_SESSION['user'] == 'admin'){
	echo "<img src=\"img/16/bug.png\" onclick=\"document.location.href='?$_SERVER[QUERY_STRING]&debug=1';\"  title=\"Debug\">";
}elseif($_SESSION['view']){
	echo "<img src=\"img/16/lokc.png\" title=\"$seclbl $stco[100]\">";
}?>
<?= $_SESSION['user'] ?></th></tr></table>
<p>
<?php

	if( strpos($_SESSION['group'],$modgroup[$self]) === false){
		echo $nokmsg;
		die;
	}

	if($debug){
		$start = microtime(1);
		echo "<div class=\"textpad code good\">Self:	$self\n";
		echo "Version:	$_SESSION[ver]\n";
		echo "NeDipath:	$nedipath\n";
		echo "DB:	$dbhost,$dbuser,$dbname\n";
		echo "User:	$_SESSION[lang], $_SESSION[theme], RRD=$_SESSION[gsiz]\n";
		echo "Now:	$now (Format:$_SESSION[date] TZ:$_SESSION[tz])\n";
		echo "</div>\n";

		echo "<div class=\"textpad code alrm\">SERVER:";
		print_r($_SERVER);
		echo "</div>\n";
	}
}
?>
