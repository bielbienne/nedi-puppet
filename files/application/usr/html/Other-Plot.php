<?php
# Program: Other-Plot.php
# Programmer: Remo Rickli

$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : '';
$res = isset($_GET['res']) ? $_GET['res'] : 'vga';
$xf = isset($_GET['xf']) ? $_GET['xf'] : 4;
$yf = isset($_GET['yf']) ? $_GET['yf'] : 4;
$xt = isset($_GET['xt']) ? $_GET['xt'] : 4;
$yt = isset($_GET['yt']) ? $_GET['yt'] : 4;
$f = isset($_GET['function']) ? $_GET['function'] : 'sin(30 * $x) * 1 / cos($x) / $x';
#	$f='tan($x - $x * cos(pi() * $x))';

if ($cmd=="img"){
	include_once ("inc/graph.php");
	$graph = new FunctionGraph($xf,$yf);
	$graph->drawAxes();
	$graph->drawFunction($f, 0.01);
	$graph->writePNG();
	$graph->destroy();
	die;
}

include_once ("inc/header.php");

$_GET = sanitize($_GET);						# Can't sanitize before including header (which breakes png output)

?>
<h1>Other Plot</h1>
<form method="get" action="<?= $self ?>.php">
<table class="content" ><tr class="<?= $modgroup[$self] ?>1">
<th width=50><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th valign="top"><?= $sizlbl ?><p>
<select size=1 name="res">
<option value="vga" <?= ($res == "vga")?" selected":"" ?>>640x480
<option value="svga" <?= ($res == "svga")?" selected":"" ?>>800x600
<option value="xga" <?= ($res == "xga")?" selected":"" ?>>1024x768
<option value="sxga" <?= ($res == "sxga")?" selected":"" ?>>1280x1024
<option value="uxga" <?= ($res == "uxga")?" selected":"" ?>>1600x1200
</select><br>
</th>
<th valign="top">Range<p>
x <input type="text" name="xf" value="<?= $xf ?>" size=3> - <input type="text" name="xt" value="<?= $xt ?>" size=3><br>
y <input type="text" name="yf" value="<?= $yf ?>" size=3> - <input type="text" name="yt" value="<?= $yt ?>" size=3>
</th>
<th valign="top">
f($x)<p>
<input name="function" value="<?= $f ?>" size=60>
</th>
<th width=80><input type="submit" value="<?= $sholbl ?>"></th>
</tr></table></form><p>
<center>
<img src="<?= $self ?>.php?cmd=img&function=<?= rawurlencode($f) ?>&xf=<?= $xf ?>&yf=<?= $yf ?>" BORDER=2>
</center>
<?php
include_once ("inc/footer.php");
?>
