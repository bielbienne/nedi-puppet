<?php
# Program: Reports-Combination.php
# Programmer: Remo Rickli (and contributors)

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/librep.php");
include_once ("inc/libmon.php");
include_once ("inc/libnod.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$rep = isset($_GET['rep']) ? $_GET['rep'] : "";
$gra = isset($_GET['gra']) ? $_GET['gra'] : array();

$lim = isset($_GET['lir']) ? preg_replace('/\D+/','',$_GET['lir']) : 10;
$gsz = isset($_GET['gsz']) ? $_GET['gsz'] : "";

$map = isset($_GET['map']) ? "checked" : "";
$ord = isset($_GET['ord']) ? "checked" : "";
$opt = isset($_GET['opt']) ? "checked" : "";

$cols = array(	"device"=>"Device",
		"devip"=>"IP $adrlbl",
		"type"=>"Device $typlbl",
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl",
		"devgroup"=>$grplbl,
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"devgroup"=>$grplbl,
		"devmode"=>$modlbl,
		"snmpversion"=>"SNMP $verlbl"
		);

$reps = array(	"ass"=>"Assets",
		"pop"=>$poplbl,
		"mon"=>$monlbl,
		"poe"=>"PoE",
		"err"=>$errlbl
		);
?>
<script src="inc/Chart.min.js"></script>

<h1><?= ($rep)?"$reps[$rep] Report":"Reports $cmblbl" ?></h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" name="report" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top">

<?PHP Filters(1); ?>

</td>
<th>

<a href="?in[]=snmpversion&op[]=>&st[]=0&lim=<?= $listlim ?>"><img src="img/16/dev.png" title="SNMP Devices"></a>
<a href="?in[]=devmode&op[]==&st[]=8"><img src="img/16/wlan.png" title="Controlled APs"></a>
<a href="?in[]=lastdis&op[]=<&st[]=<?= time()-2*$rrdstep ?>&co[]=&in[]=lastdis&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/date.png" title="<?= $undlbl ?> Devices"></a>
<a href="?in[]=lastdis&op[]=>&st[]=<?= time()-86400 ?>&co[]=&in[]=lastdis&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/clock.png" title="<?= $dsclbl ?> <?= $tim['t'] ?>"></a>

</th>
<th>

<select name="rep" size="4">
<?php
foreach ($reps as $k => $v){
	echo "<option value=\"$k\" ".(($rep == $k)?" selected":"").">$v\n";
}
?>
</select>

<select multiple size="4" name="gra[]">
<option value="" style="color: blue">- <?= (($verb1)?"$sholbl $gralbl":"$gralbl $sholbl") ?> -
<option value="msg"<?= (in_array("msg",$gra))?" selected":"" ?>> <?= $msglbl ?> <?= $sumlbl ?>
<option value="mon"<?= (in_array("mon",$gra))?" selected":"" ?>> <?= $tgtlbl ?> <?= $avalbl ?>
<option value="nod"<?= (in_array("nod",$gra))?" selected":"" ?>> <?= $totlbl ?> Nodes
<option value="tpw"<?= (in_array("tpw",$gra))?" selected":"" ?>> <?= $totlbl ?> PoE
<option value="ttr"<?= (in_array("ttr",$gra))?" selected":"" ?>> <?= $totlbl ?> non-link <?= $trflbl ?>
<option value="ter"<?= (in_array("ter",$gra))?" selected":"" ?>> <?= $totlbl ?> non-Wlan <?= $errlbl ?>
<option value="ifs"<?= (in_array("ifs",$gra))?" selected":"" ?>> IF <?= $stalbl ?>  <?= $sumlbl ?>
</select>

</th>
<td>

<img src="img/16/form.png" title="<?= $limlbl ?>"> 
<select size="1" name="lir">
<?php selectbox("limit",$lim) ?>
</select>
<p>
<img src="img/16/grph.png" title="<?= $gralbl ?> <?= $sizlbl ?>"> 
<select size="1" name="gsz">
<option value="5"><?= $siz['x'] ?>
<option value="4" <?= ($gsz == "4")?" selected":"" ?> ><?= $siz['l'] ?>
<option value="3" <?= ($gsz == "3")?" selected":"" ?> ><?= $siz['m'] ?>
<option value="2" <?= ($gsz == "2")?" selected":"" ?> ><?= $siz['s'] ?>
</select>

</td>
<td align="left">

<img src="img/16/paint.png" title="<?= (($verb1)?"$sholbl $laslbl Map":"Map $laslbl $sholbl") ?>"> 
<input type="checkbox" name="map" <?= $map ?>><br>
<img src="img/16/abc.png" title="<?= $altlbl ?> <?= $srtlbl ?>"> 
<input type="checkbox" name="ord" <?= $ord ?>><br>
<img src="img/16/hat2.png" title="<?= $optlbl ?>"> 
<input type="checkbox" name="opt" <?= $opt ?>>

</td>
<th width="80">

<input type="submit" name="do" value="<?= $sholbl ?>">

</th></tr></table></form><p>
<?php
}
echo "<center>\n";
if ($map and file_exists("map/map_$_SESSION[user].php")) {
	echo "<h2>$netlbl Map</h2>\n";
	echo "<img src=\"map/map_$_SESSION[user].php\" style=\"border:1px solid black\"><p>\n";
}

if($gra[0]){
	echo "<h2>$totlbl $gralbl</h2>\n";
	echo( in_array("msg",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=msg&a=$sta&e=$en\" title=\"$sholbl Timeline\"></a>\n":"";
	echo( in_array("mon",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=mon&a=$sta&e=$en\" title=\"$tgtlbl $avalbl\"></a>\n":"";
	echo( in_array("nod",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=nod&a=$sta&e=$en\" title=\"$totlbl Nodes\">\n":"";
	echo( in_array("tpw",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=tpw&a=$sta&e=$en\" title=\"$totlbl PoE\">\n":"";
	echo( in_array("ifs",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=ifs&a=$sta&e=$en\" title=\"IF $stslbl\">\n":"";
	echo( in_array("ttr",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=ttr&a=$sta&e=$en\" title=\"$totlbl $trflbl\">\n":"";
	echo( in_array("ter",$gra) )?"<img src=\"inc/drawrrd.php?&s=$gsz&t=ter&a=$sta&e=$en\" title=\"$totlbl $errlbl\"></a>\n":"";
	echo "<p>\n";
}
echo "</center>\n";

if($st[0] and !array_key_exists($in[0], $cols) ){echo "<h4>($fltlbl $limlbl)</h4>";$st[0] ="";$in[0] ="";}

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if($rep){
	Condition($in,$op,$st,$co);
	if($rep == "ass"){
		DevType($in[0],$op[0],$st[0],$lim,$ord);
		DevSW($in[0],$op[0],$st[0],$lim,$ord);
		ModDist($in[0],$op[0],$st[0],$lim,$ord);
		ModInventory($in[0],$op[0],$st[0],$lim,$ord);
	}
	
	if($rep == "pop"){
		NodSum($in[0],$op[0],$st[0],$lim,$ord);
		IntActiv($in[0],$op[0],$st[0],$lim,$ord,$opt);
		NodDist($in[0],$op[0],$st[0],$lim,$ord);
		NetDist($in[0],$op[0],$st[0],$lim,$ord);
		NetPop($in[0],$op[0],$st[0],$lim,$ord);
	}

	if($rep == "mon"){
		MonAvail($in[0],$op[0],$st[0],$lim,$ord);
		IncDist($in[0],$op[0],$st[0],$lim,$ord);
		IncGroup($in[0],$op[0],$st[0],$lim,$ord);
		IncHist($in[0],$op[0],$st[0],$lim,$ord,$opt);
	}

	if($rep == "poe"){
		IntPoE($in[0],$op[0],$st[0],$lim,$ord);
		DevPoE($in[0],$op[0],$st[0],$lim,$ord);
	}

	if($rep == "err"){
		DevDupIP($in[0],$op[0],$st[0],$lim,$ord);
		NodDup($in[0],$op[0],$st[0],$lim,$ord);
		IntErr($in[0],$op[0],$st[0],$lim,$ord,$opt);
		IntDsc($in[0],$op[0],$st[0],$lim,$ord,$opt);
		LnkErr($in[0],$op[0],$st[0],$lim,$ord,$opt);
	}
}

include_once ("inc/footer.php");
?>
