<?php
# Program: Reports-Modules.php
# Programmer: Remo Rickli (and contributors) 

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
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
		"model"=>"Module $typlbl",
		"moddesc"=>"Module $deslbl",
		"status"=>"Module $stalbl"
		);

?>
<h1>Module Reports</h1>
<?php
if( !isset($_GET['print']) ){
?>

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
<option value="sum" <?= (in_array("sum",$rep))?" selected":"" ?> ><?= $dislbl ?>
<option value="inv" <?= (in_array("inv",$rep))?" selected":"" ?> ><?= $invlbl ?>
<option value="prt" <?php if(in_array("prt",$rep)){echo "selected";} ?> >Print Supplies
<option value="vms" <?php if(in_array("vms",$rep)){echo "selected";} ?> >Virtual Machines
<?php
$tquery = GenQuery("cisco_contracts", "t");					# Not printable yet :-(
$res    = DbQuery($tquery, $link);
if( DbFetchRow($res) ){								# Show item only, if cisco_contracts table exists
?>
<OPTION VALUE="ves" <?= (in_array("ves",$rep))?" selected":"" ?> ><?= $wtylbl ?> <?= $stalbl ?>
<?}?>
</SELECT>

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
<th width="80"><input type="submit" name="do" value="<?= $sholbl ?>"></th>
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
		ModDist($in[0],$op[0],$st[0],$lim,$ord);
	}

	if ( in_array("inv",$rep) ){
		ModInventory($in[0],$op[0],$st[0],$lim,$ord);
	}

	if ( in_array("prt",$rep) ){
		ModPrint($in[0],$op[0],$st[0],$lim,$ord);
	}

	if ( in_array("vms",$rep) ){
		ModVM($in[0],$op[0],$st[0],$lim,$ord);
	}
}

// added for Cisco contract check by Andreas Wassatsch
if ( in_array("ves",$rep) ){
?>
<h2><?= $wtylbl ?> <?= $stalbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16//dev.png"><br>Device / Slot</th>
<th><img src="img/16//find.png"><br>Info</th>
<th><img src="img/16//form.png"><br><?= $serlbl ?></th>
<th><img src="img/16//idea.png"><br><?= $stalbl ?></th>
</tr>
<?php
	if($ord){
		$sort = "type";
	}else{
		$sort = "name";
	}
	$query	= GenQuery('devices','s','name,type,serial,devos,bootimage',$sort,'',array($ina),array($opa),array($sta) );
	$res	= DbQuery($query,$link);

	$link_ccc = DbConnect($dbhost,$dbuser,$dbpass,$dbname);

	if($res){
		$dev = 0;
		$row = 0;
		while( $d = DbFetchRow($res) ){
			$dev++;
			$ud = rawurlencode($d[0]);
			echo "<tr class=\"imgb\" class=\"blu\"><th>\n";
			echo "<a href=\"Devices-Status.php?dev=$ud\"><b>$d[0]</b></a></th>\n";

	//$query_ccc = GenQuery('cisco_contracts','s','service_level,contract_number,end_date,DATEDIFF(STR_TO_DATE(end_date, '%d-%b-%Y'),CURDATE())','','',array('serial'),array('='),array($d[2]));
	$query_ccc = "SELECT service_level,contract_number,end_date,DATEDIFF(STR_TO_DATE(end_date, '%d-%b-%Y'),CURDATE()) FROM cisco_contracts WHERE serial_number=\"$d[2]\"";
	$res_ccc = DbQuery($query_ccc,$link_ccc);
	$ccc = DbFetchRow($res_ccc);
	if ($ccc[3] > 30) {
		$color = "green";
		$ccc_message = "$ccc[0]<br>Contract #$ccc[1] valid till $ccc[2]";
	} elseif ($ccc[3] > 1) {
		$color = "darkyellow";
		$ccc_message = "$ccc[0]<br>Contract #$ccc[1] valid till $ccc[2]";
	} else {
		$color = "red";
		$ccc_message = "$ccc[0]<br>Contract #$ccc[1] expired since $ccc[2] !";
	}
	if ($ccc[1] == "") {
		$color = "blue";
		$ccc_message = "Unknown";
	}
			echo "<td align=right>-</td><td><b>$d[1]</b></td><td>$d[2]</td><td><font color=$color>$ccc_message</font></td></tr>\n";

			$mquery	= GenQuery('modules','s','*','slot','',array('device'),array('='),array($d[0]));
			$mres	= DbQuery($mquery,$link);
			if($mres){
				while( ($m = DbFetchRow($mres)) ){
					if ($row % 2){$bg = "txta";}else{$bg = "txtb";}
					$row++;
					echo "<tr class=\"$bg\"><th>\n";
					$query_ccc = "SELECT service_level,contract_number,end_date,DATEDIFF(STR_TO_DATE(end_date, '%d-%b-%Y'),CURDATE()) FROM cisco_contracts WHERE serial_number=\"$m[4]\"";
					$res_ccc = DbQuery($query_ccc,$link_ccc);
					$ccc = DbFetchRow($res_ccc);
					if ($ccc[3] > 30) {
						$color = "green";
						$ccc_message = "$ccc[0]<br>Contract #$ccc[1] valid till $ccc[2]";
					} elseif ($ccc[3] > 1) {
						$color = "darkyellow";
						$ccc_message = "$ccc[0]<br>Contract #$ccc[1] valid till $ccc[2]";
					} else {
						$color = "red";
						$ccc_message = "$ccc[0]<br>Contract #$ccc[1] expired since $ccc[2]";
					}
					if ($ccc[1] == "") {
						$color = "blue";
						$ccc_message = "Unknown";
					}
					echo "<td align=right>$m[1]</td><td><b>$m[2]</b> $m[3]</td><td>$m[4]</td><td><font color=$color>$ccc_message</font></td></tr>\n";
				}
				DbFreeResult($mres);
			}else{
				print DbError($link);
				die;
			}
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
		die;
	}
	?>
</table>
<table class="content" >
<tr class="<?= $modgroup[$self] ?>2"><td><?= $dev ?> devices, <?= $row ?> modules</td></tr>
</table>
	<?php
}

include_once ("inc/footer.php");
?>
