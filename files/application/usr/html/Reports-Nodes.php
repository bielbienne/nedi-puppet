<?php
# Program: Reports-Nodes.php
# Programmer: Remo Rickli (and contributors) 

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libnod.php");
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

$cols = array(	"device"=>"Device $namlbl",
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
		"name"=>"Node $namlbl",
		"nodip"=>"Node IP",
		"oui"=>$venlbl,
		"firstseen"=>$fislbl,
		"lastseen"=>$laslbl,
		"vlanid"=>"Vlan ID",
		"ifmetric"=>"IF $metlbl",
		"ifupdate"=>"IF $updlbl",
		"ifchanges"=>"IF $chglbl",
		"ipupdate"=>"IP $updlbl",
		"ipchanges"=>"IP $chglbl",
		"tcpports"=>"TCP $porlbl",
		"udpports"=>"UDP $porlbl",
		"nodtype"=>"Node $typlbl",
		"nodos"=>"Node OS",
		"osupdate"=>"OS $updlbl"
		);
?>
<h1>Node Reports</h1>

<script src="inc/Chart.min.js"></script>

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
<option value="sum" <?php if(in_array("sum",$rep)){echo "selected";} ?> ><?= $sumlbl ?>
<option value="dis" <?php if(in_array("dis",$rep)){echo "selected";} ?> >Node <?= $dislbl ?>
<option value="dup" <?php if(in_array("dup",$rep)){echo "selected";} ?> ><?= $duplbl ?> Nodes
<option value="nos" <?php if(in_array("nos",$rep)){echo "selected";} ?> >OS <?= $stslbl ?>
<option value="nom" <?php if(in_array("nom",$rep)){echo "selected";} ?> ><?= $nomlbl ?>
<option value="vem" <?php if(in_array("vem",$rep)){echo "selected";} ?> ><?= (($verb1)?"$emplbl Vlans":"Vlans $emplbl") ?>
<option value="nhs" <?php if(in_array("nhs",$rep)){echo "selected";} ?> ><?= $dsclbl ?> <?= $hislbl ?>
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

</th>
<th width="80">
	
<input type="submit" name="gen" value="<?= $sholbl ?>"></th>

</tr></table></form><p>
<?php
}
if ($map and !isset($_GET['xls']) and file_exists("map/map_$_SESSION[user].php")) {
	echo "<center><h2>$netlbl Map</h2>\n";
	echo "<img src=\"map/map_$_SESSION[user].php\" style=\"border:1px solid black\"></center><p>\n";
}

if($rep){
	Condition($in,$op,$st,$co);
	$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);

	if ( in_array("sum",$rep) ){
		NodSum($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("dup",$rep) ){
		NodDup($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("dis",$rep) ){
		NodDist($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("nos",$rep) ){
		NodOS($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("nom",$rep) ){
		NodNomad($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("nhs",$rep) ){
		NodHistory($in[0],$op[0],$st[0],$lim,$ord);
	}
	if ( in_array("vem",$rep) ){
		VlanEmpty($in[0],$op[0],$st[0],$lim,$ord);
	}
}

include_once ("inc/footer.php");
?>
