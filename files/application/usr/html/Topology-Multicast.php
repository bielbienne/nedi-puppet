<?php
# Program: Topology-Multicast.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libsnmp.php");

$_GET = sanitize($_GET);
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','*','device','',array('services','snmpversion'),array('>','!='),array('3','0'),array('AND') );
$res	= DbQuery($query,$link);
if($res){
	while( ($d = DbFetchRow($res)) ){
		$devtyp[$d[0]] = $d[3];
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}

?>
<h1><?= $mtitl[0] ?> <?= $mtitl[1] ?></h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php" name="mrout">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>

<SELECT size="1" name="dev">
<option value="">Device ->
<?php
foreach (array_keys($devtyp) as $r ){
	echo "<OPTION VALUE=\"$r\" ";
	if($dev == $r){echo "selected";}
	echo " >$r\n";
}
echo "</select>";
?>
</th><th width="80">
<input type="submit" value="<?= $sholbl ?>">
</th>
</tr></table></form>
<?php
}
if ($dev) {
	$query	= GenQuery('devices','s','*','','',array('device'),array('='),array($dev) );
	$res	= DbQuery($query,$link);
	$ndev	= DbNumRows($res);
	if ($ndev != 1) {
		echo "<h4>$dev $mullbl $vallbl</h4>";
		DbFreeResult($res);
		die;
	}else{
		$dev	= DbFetchRow($res);
		$ip	= long2ip($dev[1]);
		$sv	= Syssrv($dev[6]);
		$ud = rawurlencode($dev[0]);
		DbFreeResult($res);

		$query	= GenQuery('interfaces','s','ifidx,ifname,iftype,alias,comment','','',array('device'),array('='),array($rtr) );
		$res	= DbQuery($query,$link);
		while( ($i = DbFetchRow($res)) ){
			$ifn[$i[0]] = $i[1];
			$ift[$i[0]] = $i[2];
			$ifi[$i[0]] = "$i[3] $i[4]";
		}
		DbFreeResult($res);

?>
<h2><?= $sumlbl ?></h2>
<table class="content">
<tr><th class="imga" width="80">
<a href="Devices-Status.php?dev=<?= $ud ?>"><img src="img/dev/<?= $dev[18] ?>.png" title="<?= $stalbl ?>"></a>
<br><?= $dev[0] ?></th><td class="txta"><?= (Devcli($ip,$dev[16])) ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $srvlbl ?></th><td class="txtb"><?= ($sv)?$sv:"&nbsp;" ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $loclbl ?></th><td class="txta"><?= $dev[10] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $conlbl ?></th><td class="txtb"><?= $dev[11] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">SNMP</th><td class="txta"><?= $dev[15] ?> (Version <?= $dev[14] & 7?>)</td></tr>
</table>
<h2>IGMP  <?= $grplbl ?> <?= $lstlbl ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<?php
		if ($dev[8] == "ProCurve"){
?>
<th width="20%"><img src="img/16/home.png"><br><?= $dstlbl ?></th>
<th><img src="img/16/note.png"><br># Reports</th>
<th><img src="img/16/node.png"><br>Queries</th>
<th><img src="img/16/vlan.png"><br>Vlan</th>
<?php
			error_reporting(1);
			snmp_set_quick_print(1);

			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.11.2.14.11.5.1.9.10.1.1.1') as $ix => $val){
				$vlan[substr(strstr($ix,'14.11.5.1.9.10'),23)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
			}
			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.11.2.14.11.5.1.9.10.1.1.3') as $ix => $val){
				$rep[substr(strstr($ix,'14.11.5.1.9.10'),23)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
			}
			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.11.2.14.11.5.1.9.10.1.1.4') as $ix => $val){
				$qer[substr(strstr($ix,'14.11.5.1.9.10'),23)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
			}
			ksort($vlan);
			$row = 0;
			foreach($vlan as $grp => $vl){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				list($ntimg,$ntit) = Nettype($grp);
				echo "<tr class=\"$bg\">\n";
				echo "<td><img src=\"img/$ntimg\" title=\"$ntit\"> $grp</td>\n";
				echo "<td>$rep[$grp]</td><td>$qer[$grp]</td><td>$vl</td>\n";
			}	
?>
</tr></table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>
<h2>IGMP Querier <?= $lstlbl ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/port.png"><br>Interface</th>
<th><img src="img/16/home.png"><br><?= $dstlbl ?></th>
<th><img src="img/16/date.png"><br>Age <?= $timlbl ?></th>
<th><img src="img/16/clock.png"><br>Leave <?= $timlbl ?></th>
<?php
			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.11.2.14.11.5.1.9.10.3.1.4') as $ix => $val){
				$age[substr(strstr($ix,'14.11.5.1.9.10'),23)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
			}
			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.11.2.14.11.5.1.9.10.3.1.4') as $ix => $val){
				$lve[substr(strstr($ix,'14.11.5.1.9.10'),23)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
			}
			ksort($age);
			$row = 0;
			foreach($age as $grp => $a){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$ix = explode(".", $grp);
				list($ifimg,$iftit) = Iftype($ift[$ix[4]]);
				list($ntimg,$ntit)  = Nettype($grp);
				echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
				echo "<img src=\"img/$ifimg\" title=\"$iftit\"></th><td><b>".$ifn[$ix[4]]."</b> ".$ifi[$ix[4]]."</th>\n";
				echo "<td><img src=\"img/$ntimg\" title=\"$ntit\"> $ix[0].$ix[1].$ix[2].$ix[3]</td>\n";
				echo "<td>$a</td><td>$lve[$grp]</td>\n";
			}	
?>
</tr></table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>
<?php
		}else{
?>
<th width="20%"><img src="img/16/cam.png"><br><?= $srclbl ?></th>
<th width="20%"><img src="img/16/node.png"><br><?= $dstlbl ?></th>
<th><img src="img/16/tap.png"><br><?= $bwdlbl ?></th>
<th><img src="img/16/clock.png"><br><?= $laslbl ?></th>
<?php
			error_reporting(1);
			snmp_set_quick_print(1);
			snmp_set_oid_numeric_print(1);

			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.9.10.2.1.1.2.1.12') as $ix => $val){
				$prun[substr(strstr($ix,'9.10.2.1.1.2.1.'),18)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
			}
			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.9.10.2.1.1.2.1.19') as $ix => $val){
				$bps[substr(strstr($ix,'9.10.2.1.1.2.1.'),18)] = $val;
			}
			foreach (Walk($ip, $dev[14] & 3, $dev[15],'1.3.6.1.4.1.9.10.2.1.1.2.1.23') as $ix => $val){
				$last[substr(strstr($ix,'9.10.2.1.1.2.1.'),18)] = $val;
			}
			ksort($prun);
			$row = 0;
			foreach($prun as $mr => $pr){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$i  = explode(".", $mr);
				$ip = "$i[4].$i[5].$i[6].$i[7]";
				if($pr == 1){
					$ntimg = "16/bstp.png";
				}else{
					list($ntimg,$ntit)  = Nettype($ip);
				}
				sscanf($last[$mr], "%d:%d:%0d:%0d.%d",$lud,$luh,$lum,$lus,$ticks);
				$bpsbar = Bar( intval($bps[$mr]/1000),0);
				echo "<tr class=\"$bg\">\n";
				echo "<td><a href=Nodes-List.php?in[]=nodip&op[]==&st[]=$ip>$ip</a></td>\n";
				echo "<td><img src=\"img/$ntimg\" title=\"$ntit\">$i[0].$i[1].$i[2].$i[3]</td>\n";
				echo "<td>$bpsbar".$bps[$mr]."</td>\n";
				printf("<td>%d D %d:%02d:%02d</td>",$lud,$luh,$lum,$lus);
			}
?>
</tr></table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>
<?php
		}
	}
}
include_once ("inc/footer.php");
?>
