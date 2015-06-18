<?php
# Program: drawmap.php
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

session_start();
$nedipath = preg_replace( "/^(\/.+)\/html\/.+.php/","$1",$_SERVER['SCRIPT_FILENAME']);			# Guess NeDi path for nedi.conf

include_once ("libmisc.php");
ReadConf('nomenu');

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
	die;
}

include_once ("../languages/$_SESSION[lang]/gui.php");							# Don't require, GUI still works if missing
include_once ("libdb-" . strtolower($backend) . ".php");
include_once ("libdev.php");
include_once ("libmap.php");
include_once ("librrd.php");

date_default_timezone_set($_SESSION['tz']);

$dev  = array();
$reg  = array();
$nlnk = array();

$imgmap    = "";
$mapinfo   = "";
$mapframes = "";
$maplinks  = "";
$mapitems = "";

$_GET = sanitize($_GET);
$st = isset($_GET['st']) ? $_GET['st'] : array();
$in = isset($_GET['in']) ? $_GET['in'] : array('location');
$op = isset($_GET['op']) ? $_GET['op'] : array('~');
$co = isset($_GET['co']) ? $_GET['co'] : array();

$fmt = isset($_GET['fmt']) ? $_GET['fmt'] : "png";

$dim = isset($_GET['dim']) ? $_GET['dim'] : "800x600";
list($xm,$ym) = explode("x",$dim);

$fsz = isset($_GET['fsz']) ? $_GET['fsz'] : intval($xm)/8;
$len = isset($_GET['len']) ? $_GET['len'] : intval($xm)/4;

$tit = isset($_GET['tit']) ? $_GET['tit'] : "";
$mde = isset($_GET['mde']) ? $_GET['mde'] : "b";
$lev = isset($_GET['lev']) ? $_GET['lev'] : 1;

if ($mde == "f" and $lev < 4){$lev = 4;}
$xo  = isset($_GET['xo']) ? $_GET['xo'] : 0;
$yo  = isset($_GET['yo']) ? $_GET['yo'] : 0;
$rot = isset($_GET['rot']) ? $_GET['rot'] : 0;
$cro = isset($_GET['cro']) ? $_GET['cro'] : 0;
$bro = isset($_GET['bro']) ? $_GET['bro'] : 0;

$ifi = isset($_GET['ifi']) ? "checked" : "";
$ifa = isset($_GET['ifa']) ? "checked" : "";
$ipi = isset($_GET['ipi']) ? "checked" : "";
$ipd = isset($_GET['ipd']) ? "checked" : "";
$loo = isset($_GET['loo']) ? "checked" : "";
$loa = isset($_GET['loa']) ? "checked" : "";
$loi = (($loo)?1:0) + (($loa)?2:0);
$dco = isset($_GET['dco']) ? "checked" : "";
$dmo = isset($_GET['dmo']) ? "checked" : "";
$dvi = (($dco)?1:0) + (($dmo)?2:0);

$lis = isset($_GET['lis']) ? $_GET['lis'] : "";
$lit = isset($_GET['lit']) ? $_GET['lit'] : "";
$lil = isset($_GET['lil']) ? $_GET['lil'] : 0;
$lal = isset($_GET['lal']) ? $_GET['lal'] : 50;
$pos = isset($_GET['pos']) ? $_GET['pos'] : "";
$pwt = isset($_GET['pwt']) ? $_GET['pwt'] : 10;
$lsf = isset($_GET['lsf']) ? $_GET['lsf'] : 10;
$fco = isset($_GET['fco']) ? $_GET['fco'] : 6;

$imas= ($pos == "d")?4:18;

$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$sub  = 1;
Map();
if($fmt == 'json'){
	WriteJson(1);
}else{
	WritePNG( Condition($in,$op,$st,$co,1),1);
}

?>
