<?php 

session_start();
if( !preg_match("/net/",$_SESSION['group']) ){
	echo $nokmsg;
	die;
}
include_once ("libmisc.php");
include_once ("../languages/$_SESSION[lang]/gui.php");							# Don't require, GUI still works if missing

$_GET = sanitize($_GET);
$debug  = isset($_GET['debug']) ? $_GET['debug'] : "";
$ti     = isset($_GET['ti']) ? $_GET['ti'] : 5;

$graph = "rt-svg.php?ti=$ti&ip=$_GET[ip]&c=$_GET[c]&v=$_GET[v]&i=$_GET[i]&in=".urlencode($_GET['in']);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=<?= $charset ?>">
<link href="../themes/<?= $_SESSION['theme'] ?>.css" type="text/css" rel="stylesheet">
</head>
<body>

<div class="net2">
<h2>
<form method="get" name="iv">
<img src="../img/16/grph.png" hspace="10"> 
<?= $_GET['t'] ?> <?= $rltlbl ?> <?= $trflbl ?>&nbsp;
<select name="ti" size="1" title="<?= $rptlbl ?> [<?= $tim['s'] ?>]" onchange="this.form.submit();">
<option value="1" <?= ($ti == 1)?"selected":"" ?> >1
<option value="5" <?= ($ti == 5)?"selected":"" ?> >5
<option value="30" <?= ($ti == 30)?"selected":"" ?> >30
<option value="60" <?= ($ti == 60)?"selected":"" ?> >60
<option value="300" <?= ($ti == 300)?"selected":"" ?> >300
</select>
<input type="hidden" name="ip" value="<?= $_GET['ip'] ?>">
<input type="hidden" name="c" value="<?= $_GET['c'] ?>">
<input type="hidden" name="t" value="<?= $_GET['t'] ?>">
<input type="hidden" name="v" value="<?= $_GET['v'] ?>">
<input type="hidden" name="i" value="<?= $_GET['i'] ?>">
<input type="hidden" name="in" value="<?=  $_GET['in'] ?>">
</form>
</h2>
</div>

<object data="<?= $graph ?>" type="image/svg+xml" width="100%" height="80%">
<param name="src" value="<?= $graph ?>" />
Your browser does not support the type SVG! You need to either use Firefox or download the Adobe SVG plugin.
</object>

</body>
</html>
