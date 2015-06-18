<?php
# Program: Reports-Wlan.php
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libnod.php");

$_GET = sanitize($_GET);
$ord = isset($_GET['ord']) ? $_GET['ord'] : "name";
$opt = isset($_GET['opt']) ? "checked" : "";
?>
<h1>Wlan Access Points</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a>

</th>
<th>

<img src="img/16/abc.png" title="<?= $srtlbl ?>">
<select name="ord" size="1">
<option value="name" <?= ($ord == "name")?" selected":"" ?> >Name
<option value="firstseen" <?= ($ord == "firstseen")?" selected":"" ?> ><?= $fislbl ?>
<option value="lastseen" <?= ($ord == "lastseen")?" selected":"" ?> ><?= $laslbl ?>
<option value="ip" <?= ($ord == "ip")?" selected":"" ?> >IP address
<option value="ipupdate" <?= ($ord == "ipupdate")?" selected":"" ?> >IP <?= $updlbl ?>
<option value="ip" <?= ($ord == "mac")?" selected":"" ?> >MAC address
<option value="device" <?= ($ord == "device")?" selected":"" ?> >Device
<option value="ifupdate" <?= ($ord == "ifupdate")?" selected":"" ?> >IF <?= $updlbl ?>

</select>

</th>
<th>

<img src="img/16/hat2.png" title="<?= $optlbl ?>"><input type="checkbox" name="opt" <?= $opt ?> >

</th>
<th width="80">
	
<input type="submit" value="<?= $sholbl ?>">

</th></tr></table></form><p>
<?php
}
$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('wlan');
$res	= DbQuery($query,$link);
if($res){
	$nwmac = 0;
	while( ($w = DbFetchRow($res)) ){
		$nwmac++;
		$wlap[] = "$w[0]";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
	die;
}

?>

<h2>AP <?= $lstlbl ?></h2>

<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="4"><img src="img/16/node.png"><br><?= $namlbl ?> - IP - MAC <?= $fltlbl ?></th>
<th colspan="3"><img src="img/16/dev.png"><br>Device - IF - Nodes</th>
<th colspan="2"><img src="img/16/clock.png"><br><?= $fislbl ?> / <?= $laslbl ?></th>

<?php

$query	= GenQuery('nodes');
$res	= DbQuery($query,$link);
while( ($n = DbFetchRow($res)) ){
	$macs["$n[6];;$n[7]"]++;
}
	$row = 0;
	$nno = 0;
	$query	= GenQuery('nodes','s','*',$ord);
	$res	= DbQuery($query,$link);
	while( ($n = DbFetchRow($res)) ){
		if($macs["$n[6];;$n[7]"] > 1 or !$opt){
			$m = substr($n[2],0,8);
			if(in_array("$m", $wlap,1) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$name	= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
				$ip	= long2ip($n[1]);
				$img	= Nimg("$n[2];$n[3]");
				$fs	= date($datfmt,$n[4]);
				$ls	= date($datfmt,$n[5]);
				$pbar	= Bar($macs[$n[6]][$n[7]],5);
				$ud	= rawurlencode($n[6]);
				list($fc,$lc)	= Agecol($n[4],$n[5],$row % 2);
				echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
				echo "<a href=Nodes-Status.php?mac=$n[2]><img src=\"img/oui/$img.png\" title=\"$n[3] ($n[2])\"></a></th>\n";
				echo "<td>$name</td><td>$ip</td><td>$m</td><td>$n[6]</td><td><a href=Nodes-List.php?in[]=device&op[]==&st[]=$ud&co[]=AND&in[]=ifname&op[]==&st[]=$n[7]&>$n[7]</a></td><td>$pbar".$macs["$n[6];;$n[7]"]."</td>\n";
				echo "<td bgcolor=#$fc>$fs</td><td bgcolor=#$lc>$ls</td>";
				echo "</tr>\n";
			}
		}
		$nno++;
	}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> out of <?= $nno ?> Nodes <?= $fltlbl ?> <?= $nwmac ?> MACs</td></tr>
</table>
<?php
include_once ("inc/footer.php");
?>
