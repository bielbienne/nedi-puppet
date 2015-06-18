<?php
# Program: Devices-Graph.php
# Programmer: Remo Rickli

$printable = 1;
$nocache   = 1;
$refresh   = 600;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$dv = isset($_GET['dv']) ? $_GET['dv'] : "";
$if = isset($_GET['if']) ? $_GET['if'] : array();
$it = isset($_GET['it']) ? $_GET['it'] : array();
$sho = isset($_GET['sho']) ? 1 : 0;
$cad = isset($_GET['cad']) ? 1 : 0;
$tem = isset($_GET['tem']) ? $_GET['tem'] : 2;
$sze = $_GET['sze'] ? $_GET['sze'] : 5;

$strsta = isset($_GET['sta']) ? $_GET['sta'] : date("m/d/Y H:i", time() - $rrdstep * 800);
$strend = isset($_GET['end']) ? $_GET['end'] : date("m/d/Y H:i");
if(!$sho){# Let graph follow autoupdate
	$strsta = date("m/d/Y H:i",strtotime($strsta) + $refresh);
	$strend = date("m/d/Y H:i");
}
$sta = strtotime($strsta);
$end = strtotime($strend);
if($sta > $end){
	$sta    = $end - 100 * $rrdstep;
	$strsta = date("m/d/Y H:i",$sta);
}
$qstr = strpos($_SERVER['QUERY_STRING'], "sta")?$_SERVER['QUERY_STRING']:$_SERVER['QUERY_STRING']."&sta=".urlencode($strsta)."&end=".urlencode($strend);

?>
<h1>Device <?= $gralbl ?></h1>

<?php if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php" name="dynfrm">
<table class="content">
<tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
<select size="6" name="dv" onchange="this.form.submit();">
<?php
$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','device,devip,snmpversion,readcomm,memcpu,temp,cuslabel,cusvalue,devopts','device','',array('snmpversion'),array('!='),array('0') );
$res	= DbQuery($query,$link);
if($res){
	echo "<option value=\"Totals\"".(($dv == "Totals")?" selected":"")."> Network Totals";
	echo "<option value=\"\" style=\"color: blue\">- Devices -";
	while( ($d = DbFetchRow($res)) ){
		echo "<option value=\"$d[0]\"";
		if($dv == $d[0]){
			echo " selected";
			$ip = long2ip($d[1]);
			$sp = ($d[2] & 3);
			$hc = ($d[2] & 128);
			$co = $d[3];
			$mem = $d[4];
			$tmp = $d[5];
			$cg =$d[6];
			$cv = $d[7];
			$dop = $d[8];
			if($cg){
				list($ct,$cy,$cu) = explode(";", $cg);
			}else{# TODO should only be necessary until all .defs are discovered with 1.0.6
				$ct = "Mem IO";
				$cu = "Bytes free";
			}
		}
		echo " >$d[0]\n";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}
?>
</select>
<?php
if ($dv == "Totals") {
?>
<select multiple size="6" name="if[]">
<option value="msg"<?= (in_array("msg",$if))?" selected":"" ?>> <?= $msglbl ?> <?= $sumlbl ?>
<option value="mon"<?= (in_array("mon",$if))?" selected":"" ?>> <?= $tgtlbl ?> <?= $avalbl ?>
<option value="nod"<?= (in_array("nod",$if))?" selected":"" ?>> <?= $totlbl ?> Nodes
<option value="tpw"<?= (in_array("tpw",$if))?" selected":"" ?>> <?= $totlbl ?> PoE
<option value="ttr"<?= (in_array("ttr",$if))?" selected":"" ?>> <?= $totlbl ?> <?= $acslbl ?> <?= $trflbl ?>
<option value="ter"<?= (in_array("ter",$if))?" selected":"" ?>> <?= $totlbl ?> non-Wlan <?= $errlbl ?>
<option value="tdi"<?= (in_array("tdi",$if))?" selected":"" ?>> <?= $totlbl ?> non-Wlan Discards
<option value="ifs"<?= (in_array("ifs",$if))?" selected":"" ?>> IF <?= $stalbl ?>  <?= $sumlbl ?>
<?php
}elseif($dv){
?>
<select multiple size="6" name="if[]">
<?php
if( substr($dop,1,1) == "C" ){
?>
<option value="cpu"<?= (in_array("cpu",$if))?" selected":"" ?>> CPU
<?php
}
if($mem){
?>
<option value="mem"<?= (in_array("mem",$if))?" selected":"" ?>> Mem
<?php
}
if($tmp){
?>
<option value="tmp"<?= (in_array("tmp",$if))?" selected":"" ?>> <?= $tmplbl ?>
<?php
}
if($ct != "-"){
?>
<option value="cuv"<?= (in_array("cuv",$if))?" selected":"" ?>> <?= $ct ?>
<?php
}
?>
<option value="" style="color: blue">- Interfaces -
<?php
	$query	= GenQuery('interfaces','s','ifname,alias,comment','ifidx','',array('device'),array('='),array($dv) );
	$res	= DbQuery($query,$link);
	if($res){
		while( ($i = DbFetchRow($res)) ){
			echo "<option value=\"$i[0]\" ";
			if(in_array($i[0],$if)){echo "selected";}
			echo " >$i[0] " . substr("$i[1] $i[2]\n",0,30);
		}
		DbFreeResult($res);
	}
?>
</select>

<select multiple size="6" name="it[]">
<option value="t"<?= (in_array("t",$it))?" selected":"" ?>> <?= $trflbl ?>
<option value="e"<?= (in_array("e",$it))?" selected":"" ?>> <?= $errlbl ?>
<option value="d"<?= (in_array("d",$it))?" selected":"" ?>> Discards
<option value="b"<?= (in_array("b",$it))?" selected":"" ?>> Broadcast
<option value="s"<?= (in_array("s",$it))?" selected":"" ?>> <?= $stalbl ?>
</select>

<?php
}
?>

</th>
<td align="center">

<table style="border-spacing: 0px">
<tr class="<?= $modgroup[$self] ?>2"><td>
<a href="?<?=SkewTime($qstr,"sta", -7) ?>"><img src="img/16/bbl2.png" title="<?= $sttlbl ?> -<?= $tim['w'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"sta", -1) ?>"><img src="img/16/bblf.png" title="<?= $sttlbl ?> -<?= $tim['d'] ?>"></a>
</td><td>
<input  name="sta" id="start" type="text" value="<?= $strsta ?>" onfocus="select();" size="15" title="<?= $sttlbl ?>">
</td><td>
<a href="?<?=SkewTime($qstr,"sta", 1) ?>"><img src="img/16/bbrt.png" title="<?= $sttlbl ?> +<?= $tim['d'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"sta", 7) ?>"><img src="img/16/bbr2.png" title="<?= $sttlbl ?> +<?= $tim['w'] ?>"></a>
</td></tr>
<tr class="<?= $modgroup[$self] ?>2"><td>
<a href="?<?=SkewTime($qstr,"all", -7) ?>"><img src="img/16/bbl2.png" title="<?= $gralbl ?> -<?= $tim['w'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"all", -1) ?>"><img src="img/16/bblf.png" title="<?= $gralbl ?> -<?= $tim['d'] ?>"></a>
</td><th>
<img src="img/16/date.png" title="<?= $sttlbl ?> & <?= $endlbl ?>">
</th><td>
<a href="?<?=SkewTime($qstr,"all", 1) ?>"><img src="img/16/bbrt.png" title="<?= $gralbl ?> +<?= $tim['d'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"all", 7) ?>"><img src="img/16/bbr2.png" title="<?= $gralbl ?> +<?= $tim['w'] ?>"></a>
</td></tr>
<tr class="<?= $modgroup[$self] ?>2"><td>
<a href="?<?=SkewTime($qstr,"end", -7) ?>"><img src="img/16/bbl2.png" title="<?= $endlbl ?> -<?= $tim['w'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"end", -1) ?>"><img src="img/16/bblf.png" title="<?= $endlbl ?> -<?= $tim['d'] ?>"></a>
</td><td>
<input  name="end" id="end" type="text" value="<?= $strend ?>" onfocus="select();" size="15" title="<?= $endlbl ?>">
</td><td>
<a href="?<?=SkewTime($qstr,"end", 1) ?>"><img src="img/16/bbrt.png" title="<?= $endlbl ?> +<?= $tim['d'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"end", 7) ?>"><img src="img/16/bbr2.png" title="<?= $endlbl ?> +<?= $tim['w'] ?>"></a>
</table>

<script type="text/javascript" src="inc/datepickr.js"></script>
<link rel="stylesheet" type="text/css" href="inc/datepickr.css" />
<script>

new datepickr('start', {'dateFormat': 'm/d/y'});
new datepickr('end', {'dateFormat': 'm/d/y'});
</script>

</td>
<?php  if($cacticli) { ?>
<td align="center"><h3>Cacti</h3>
<select size="1" name="tem">
<option value="2"><?= $trflbl ?>
<option value="22"><?= $errlbl ?>
<option value="24">Broadcast
</select><p>
<input type="submit" name="cad" value="<?= $addlbl ?>">
</td>
<?}?>
<th width="80">
<span id="counter"><?= $refresh ?></span>
<img src="img/16/exit.png" title="Stop" onClick="stop_countdown(interval);">
<p>
<select size="1" name="sze">
<option value=""><?= $siz['x'] ?>
<option value="4" <?= ($sze == "4")?" selected":"" ?> ><?= $siz['l'] ?>
<option value="3" <?= ($sze == "3")?" selected":"" ?> ><?= $siz['m'] ?>
<option value="2" <?= ($sze == "2")?" selected":"" ?> ><?= $siz['s'] ?>
</select>
<p>
<input type="submit" name="sho" value="<?= $sholbl ?>">
</th>
</tr></table></form>

<?php
}
if($dv){
	$ud = rawurlencode($dv);
	if($dv != "Totals" and !isset($_GET['print']) and strpos($_SESSION['group'],$modgroup['Devices-Status']) !== false ){
		echo "<h2><a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/16/sys.png\"></a> $dv</h2>\n";
	}else{
		echo "<h2>$dv</h2>";
	}
	echo "<h3>".date($_SESSION['date'], $sta)." - ".date($_SESSION['date'],$end)."</h3>";
}
?>
<div align="center"><p>
<?php

if($cad){
	if($debug){echo "$cacticli/add_device.php --description=\"$dv\" --ip=\"$ip\" --template=1 --version=\"$sp\" --community=\"$co\"";}
	$adev = exec("$cacticli/add_device.php --description=\"$dv\" --ip=\"$ip\" --template=1 --version=\"$sp\" --community=\"$co\"");
	echo "<div class=\"textpad code txta\">$adev</div>";
	flush();
	$devid = preg_replace("/.*device-id: \((\d+)\).*/","$1",$adev);
	if($devid){
		if($tem == 22){
			$qtyp = 2;
		}elseif($tem == 24){
			$qtyp = 3;
		}elseif($hc){
			$qtyp = 14;
		}else{
			$qtyp = 13;
		}
		foreach ($if as $i){
			if($debug){echo "$cacticli/add_graphs.php --graph-type=ds --graph-template-id=$tem --host-id=$devid --snmp-query-id=1 --snmp-query-type-id=$qtyp --snmp-field=ifName --snmp-value=\"$i\"";}
			$agrf = exec("$cacticli/add_graphs.php --graph-type=ds --graph-template-id=$tem --host-id=$devid --snmp-query-id=1 --snmp-query-type-id=$qtyp --snmp-field=ifName --snmp-value=\"$i\"");
			echo "<div class=\"textpad code txtb\">$agrf</div>";
			flush();
		}
	}
}elseif ($dv == "Totals") {
	if( in_array("msg",$if) ){
		echo "<a href=\"Monitoring-Timeline.php?sta=".urlencode($strsta)."&end=".urlencode($strend)."&det=level\">\n";
		echo "<img src=\"inc/drawrrd.php?&s=$sze&t=msg&a=$sta&e=$end\" title=\"$sholbl Timeline\"></a>\n";
	}
	if( in_array("mon",$if) ){
		echo "<a href=\"Monitoring-Timeline.php?in[]=class&op[]==&st[]=moni&sta=".urlencode($strsta)."&end=".urlencode($strend)."&det=source\">\n";
		echo "<img src=\"inc/drawrrd.php?&s=$sze&t=mon&a=$sta&e=$end\" title=\"$tgtlbl $avalbl\"></a>\n";
	}
	if( in_array("nod",$if) ){echo "<img src=\"inc/drawrrd.php?&s=$sze&t=nod&a=$sta&e=$end\" title=\"$totlbl Nodes\">\n";}
	if( in_array("tpw",$if) ){echo "<img src=\"inc/drawrrd.php?&s=$sze&t=tpw&a=$sta&e=$end\" title=\"$totlbl PoE\">\n";}
	if( in_array("ttr",$if) ){echo "<img src=\"inc/drawrrd.php?&s=$sze&t=ttr&a=$sta&e=$end\" title=\"$totlbl $trflbl\">\n";}
	if( in_array("ter",$if) ){echo "<img src=\"inc/drawrrd.php?&s=$sze&t=ter&a=$sta&e=$end\" title=\"$totlbl $errlbl\">\n";}
	if( in_array("tdi",$if) ){echo "<img src=\"inc/drawrrd.php?&s=$sze&t=tdi&a=$sta&e=$end\" title=\"$totlbl Discards\">\n";}
	if( in_array("ifs",$if) ){echo "<img src=\"inc/drawrrd.php?&s=$sze&t=ifs&a=$sta&e=$end\" title=\"IF $stalbl $sumlbl\">\n";}
}else{
	if( in_array("cpu",$if) ){echo "<img src=\"inc/drawrrd.php?dv=$ud&s=$sze&t=cpu&a=$sta&e=$end\" title=\"% CPU\">\n";}
	if( in_array("mem",$if) ){echo "<img src=\"inc/drawrrd.php?dv=$ud&s=$sze&t=mem&a=$sta&e=$end\" title=\"Mem $frelbl\">\n";}
	if( in_array("tmp",$if) ){echo "<img src=\"inc/drawrrd.php?dv=$ud&s=$sze&t=tmp&a=$sta&e=$end\" title=\"$tmplbl\">\n";}
	if( in_array("cuv",$if) ){echo "<img src=\"inc/drawrrd.php?dv=$ud&&if[]=".urlencode($ct)."&if[]=".urlencode($cu)."&s=$sze&t=cuv&a=$sta&e=$end\" title=\"$ct [$cu]\">\n";}
	if( isset($if[0]) ){
		$uif = "";
		foreach ( $if as $i){
			if( !preg_match('/cpu|mem|tmp|cuv/',$i) ){
				$uif .= '&if[]='.rawurlencode($i);
			}
		}
		if($uif){
			if(in_array("t",$it)){echo "<img src=\"inc/drawrrd.php?dv=$ud$uif&s=$sze&t=trf&a=$sta&e=$end\" title=\"$trflbl\">\n";}
			if(in_array("e",$it)){echo "<img src=\"inc/drawrrd.php?dv=$ud$uif&s=$sze&t=err&a=$sta&e=$end\" title=\"$errlbl\">\n";}
			if(in_array("d",$it)){echo "<img src=\"inc/drawrrd.php?dv=$ud$uif&s=$sze&t=dsc&a=$sta&e=$end\" title=\"Discards\">\n";}
			if(in_array("b",$it)){echo "<img src=\"inc/drawrrd.php?dv=$ud$uif&s=$sze&t=brc&a=$sta&e=$end\" title=\"Broadcasts\">\n";}
			if(in_array("s",$it)){echo "<img src=\"inc/drawrrd.php?dv=$ud$uif&s=$sze&t=sta&a=$sta&e=$end\" title=\"IF $stalbl (no stack!)\">\n";}
		}
	}
}
?>
</div>
<?php
include_once ("inc/footer.php");
?>
