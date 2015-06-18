<?php
# Program: Reports-Monitoring.php
# Programmer: Remo Rickli

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");
include_once ("inc/librep.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$rep = isset($_GET['rep']) ? $_GET['rep'] : array();

$lim = isset($_GET['lir']) ? preg_replace('/\D+/','',$_GET['lir']) : 10;

$map = isset($_GET['map']) ? "checked" : "";
$ord = isset($_GET['ord']) ? "checked" : "";
$opt = isset($_GET['opt']) ? "checked" : "";

$cols = array(	"device"=>"Device",
		"devip"=>"IP $adrlbl",
		"type"=>"Device $typlbl",
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl",
		"services"=>$srvlbl,
		"description"=>$deslbl,
		"devos"=>"Device OS",
		"bootimage"=>"Bootimage",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"devgroup"=>$grplbl,
		"devmode"=>$modlbl,
		"snmpversion"=>"SNMP $verlbl",
		"name"=>"$tgtlbl/$srclbl",
		"lastok"=>"$laslbl OK",
		"class"=>$clalbl,
		"test"=>$tstlbl
		);

?>
<h1>Monitoring Reports</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" name="report" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top">

<?PHP Filters(1); ?>

</td>
<th>

<a href="?in[]=snmpversion&op[]=>&st[]=0"><img src="img/16/dev.png" title="SNMP Devices"></a>
<a href="?in[]=devmode&op[]==&st[]=8"><img src="img/16/wlan.png" title="Controlled APs"></a>
<a href="?in[]=lastdis&op[]=<&st[]=<?= time()-2*$rrdstep ?>&co[]=&in[]=lastdis&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/date.png" title="<?= $undlbl ?> Devices"></a>
<a href="?in[]=lastdis&op[]=>&st[]=<?= time()-86400 ?>&co[]=&in[]=lastdis&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/clock.png" title="<?= $dsclbl ?> <?= $tim['t'] ?>"></a>

</th>
<th>

<select multiple name="rep[]" size="4">
<option value="mav" <?= (in_array("mav",$rep))?" selected":"" ?>><?= $avalbl ?> <?= $dislbl ?>
<option value="lat" <?= (in_array("lat",$rep))?" selected":"" ?>><?= $latlbl ?> <?= $stslbl ?>
<option value="upt" <?= (in_array("upt",$rep))?" selected":"" ?>>Devices <?= $uptlbl ?>
<option value="evt" <?= (in_array("evt",$rep))?" selected":"" ?>><?= $msglbl ?> <?= $stslbl ?>
<option value="igr" <?php if(in_array("igr",$rep)){echo "selected";} ?> ><?= $inclbl ?> <?= $grplbl ?>
<option value="idi" <?php if(in_array("idi",$rep)){echo "selected";} ?> ><?= $inclbl ?> <?= $dislbl ?>
<option value="ack" <?php if(in_array("ack",$rep)){echo "selected";} ?> ><?= $inclbl ?> <?= $acklbl ?>
<option value="his" <?php if(in_array("his",$rep)){echo "selected";} ?> ><?= $inclbl ?> <?= $hislbl ?> 
</select>

</th>
<th>

<img src="img/16/form.png" title="<?= $limlbl ?>">  
<select size="1" name="lir">
<?php selectbox("limit",$lim) ?>
</select>
</th>
<th align="left">

<img src="img/16/paint.png" title="<?= (($verb1)?"$sholbl $laslbl Map":"Map $laslbl $sholbl") ?>"> 
<input type="checkbox" name="map" <?= $map ?>><br>
<img src="img/16/abc.png" title="<?= $altlbl ?> <?= $srtlbl ?>"> 
<input type="checkbox" name="ord" <?= $ord ?>><br>
<img src="img/16/hat2.png" title="<?= $optlbl ?>"> 
<input type="checkbox" name="opt" <?= $opt ?>>

</th>
<th width="80">

<input type="submit" name="do" value="<?= $sholbl ?>">

</th></tr></table></form><p>
<?php
}
if ($map and !isset($_GET['xls']) and file_exists("map/map_$_SESSION[user].php")) {
	echo "<center><h2>$netlbl Map</h2>\n";
	echo "<img src=\"map/map_$_SESSION[user].php\" style=\"border:1px solid black\"></center><p>\n";
}

if($rep){
	Condition($in,$op,$st,$co);

	$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	if ( in_array("mav",$rep) ){
		MonAvail($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("lat",$rep) ){
		MonLatency($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("upt",$rep) ){
		MonUptime($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("evt",$rep) ){
		MonEvent($in[0],$op[0],$st[0],$lim,$ord,$opt);
	}

	if ( in_array("igr",$rep) ){
		IncGroup($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("idi",$rep) ){
		IncDist($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("ack",$rep) ){
		IncAck($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("his",$rep) ){
		IncHist($in[0],$op[0],$st[0],$lim,$ord,$opt);
	}
}

include_once ("inc/footer.php");
?>
