<?php
# Program: Topology-Spanningtree.php
# Programmer: Remo Rickli

$printable = 1;

error_reporting(1);
snmp_set_quick_print(1);
snmp_set_oid_numeric_print(1);
snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libsnmp.php");

$_GET = sanitize($_GET);
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$shg = isset($_GET['shg']) ? "checked" : "";
$vln = isset($_GET['vln']) ? $_GET['vln'] : "";
?>
<h1>Spanningtree Tool</h1>
<form method="get" action="<?= $self ?>.php" name="stree">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
Device
<select size="1" name="dev" onchange="document.stree.vln.value=''">
<option value=""><?= $sellbl ?> ->
<?php
$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','device,devip,services,snmpversion,readcomm,location,contact,cliport,icon','device','',array('services & 2','snmpversion'),array('=','!='),array('2','0'),array('AND') );
$res	= DbQuery($query,$link);
if($res){
	while( ($d = DbFetchRow($res)) ){
		echo "<option value=\"$d[0]\" ";
		if($dev == $d[0]){
			echo "selected";
			$ud	= urlencode($d[0]);
			$ip	= long2ip($d[1]);
			$sv	= Syssrv($d[2]);
			$rv	= $d[3] & 3;
			$rc	= $d[4];
			$loc	= $d[5];
			$con	= $d[6];
			$cli	= $d[7];
			$img	= $d[8];
		}
		echo " >$d[0]\n";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}
echo "</select>";
if ($dev) {
	$query	= GenQuery('vlans','s','*','vlanid','',array('device'),array('='),array($dev) );
	$res	= DbQuery($query,$link);
	$nvln	= DbNumRows($res);

	if($res and $nvln){
?>
 Vlan
<select size="1" name="vln">
<option value="">---
<?php

		while( ($v = DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$v[1]\" ";
			if($vln == $v[1]){echo "selected";}
			echo " >$v[1] $v[2]\n";
		}
		DbFreeResult($res);
		echo "</select>";
	}
}
?>

</th>
<th>
	
<img src="img/16/grph.png" title="IF <?= $gralbl ?>">
<input type="checkbox" name="shg" <?= $shg ?>>


</th>
<th width="80">

<input type="submit" value="<?= $sholbl ?>">
</th>
</tr></table></form>
<p>
<?php
if ($dev) {
	$query	= GenQuery('interfaces','s','ifidx,ifname,iftype,speed,alias,comment,ifdesc,ifstat','','',array('device'),array('='),array($dev) );
	$res	= DbQuery($query,$link);
	while( ($i = DbFetchRow($res)) ){
		$ifn[$i[0]] = $i[1];
		$ift[$i[0]] = $i[2];
		$ifs[$i[0]] = $i[3];
		$ifa[$i[0]] = $i[7];
		if( strstr($i[5],"DP:") ){
			$uneb = urlencode( preg_replace('/.+DP:(.+),.+/','$1',$i[5]) );
			$neb  = ($uneb)?"<a href=\"Topology-Spanningtree.php?dev=$uneb\"><img src=\"img/16/traf.png\"></a>":"";
			$ifi[$i[0]] = "$i[6] - <i>$i[4]</i> - $i[5] $neb";
		}else{
			$ifi[$i[0]] = "$i[6] - <i>$i[4]</i>";
		}
	}
	DbFreeResult($res);
if('0.0.0.0' == $ip){
	echo "<h4>$nonlbl IP!</h4>";
	die;
}

?>

<table class="full fixed"><tr><td class="helper">

<h2>Device <?= $sumlbl ?></h2>
<table class="content">
<tr><th class="imga" width="80">
<a href="Devices-Status.php?dev=<?= $ud ?>"><img src="img/dev/<?= $img ?>.png" title="<?= $stalbl ?>"></a>
<br><?= $dev ?></th><td class="txta">

<div style="float:right">
<a href="telnet://<?= $ip ?>"><img src="img/16/loko.png" title="Telnet"></a>
<a href="ssh://<?= $ip ?>"><img src="img/16/lokc.png" title="SSH"></a>
<a href="http://<?= $ip ?>" target="window"><img src="img/16/glob.png" title="HTTP"></a>
<a href="https://<?= $ip ?>" target="window"><img src="img/16/glok.png" title="HTTPS"></a>
</div>

<?= (Devcli($ip,$cli)) ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $srvlbl ?></th><td class="txtb"><?= ($sv)?$sv:"&nbsp;" ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $loclbl ?></th><td class="txta"><?= $loc ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $conlbl ?></th><td class="txtb"><?= $con ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">SNMP</th><td class="txta">v<?= $rv ?> <?= $rc ?></td></tr>
</table>

</td><td class="helper">

<h2>Spanningtree <?= $sumlbl ?><?= ($vln)?" Vlan $vln":"" ?></h2>
<table class="content"><tr>
<th class="<?= $modgroup[$self] ?>2">Bridge <?= $adrlbl ?></th><td  class="txta">
<?php
	$braddr	= str_replace('"','', Get($ip, $rv, $rc, "1.3.6.1.2.1.17.1.1.0") );
	if ($braddr){
		$m = explode(":",$braddr);
		$brmac = sprintf("%02s%02s%02s%02s%02s%02s", $m[0], $m[1], $m[2], $m[3], $m[4], $m[5] );
		echo "$braddr ($brmac)</td></tr>\n";
	}else{
		echo "<h4>$toumsg</h4></td></tr></table></th></tr></table>\n";
		if($_SESSION['vol']){echo "<embed src=\"inc/enter2.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
		include_once ("inc/footer.php");
		die;
	}
?>
<tr><th class="<?= $modgroup[$self] ?>2">STP <?= $prilbl ?></th><td class="txtb">
<?php
	if($vln){$rc = "$rc@$vln";}
	$stppri	= str_replace('"','', Get($ip, $rv, $rc, "1.3.6.1.2.1.17.2.2.0") );
	if( strstr($stppri,"No Such ") ){
		echo "$toumsg</td></tr></table></th></tr></table>\n";
		include_once ("inc/footer.php");
		die;
	}else{
		echo "$stppri</td></tr>\n";
	}
	$laschg	= str_replace('"','', Get($ip, $rv, $rc, "1.3.6.1.2.1.17.2.3.0") );
	sscanf($laschg, "%d:%d:%0d:%0d.%d",$tcd,$tch,$tcm,$tcs,$ticks);
	$tcstr  = sprintf("%dD-%d:%02d:%02d",$tcd,$tch,$tcm,$tcs);
	$numchg	= str_replace('"','', Get($ip, $rv, $rc, "1.3.6.1.2.1.17.2.4.0") );

	$droot	= str_replace('"','', Get($ip, $rv, $rc, "1.3.6.1.2.1.17.2.5.0") );
	$rport	= str_replace('"','', Get($ip, $rv, $rc, "1.3.6.1.2.1.17.2.7.0") );

	$rootif = strtolower( substr( str_replace(' ','', $droot) ,4) );
?>
<tr><th class="<?= $modgroup[$self] ?>2">Topology <?= $chglbl ?></th><td class="txta"><?= $numchg ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= ($verb1)?"$laslbl $chglbl":"$chglbl $laslbl" ?></th><td class="txtb"><?= $tcstr ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">Designated Root</th><td class="txta"><?= $droot ?>
<?php if($brmac != $rootif){
?>
<a href="Devices-Interfaces.php?in[]=ifmac&op[]=%3D&st[]=<?= $rootif ?>"><img src="img/16/port.png" title="IF <?= $lstlbl ?>"></a>
<?php
}else{
?>
<img src="img/16/home.png" title="Root">
<?php
}
?>
</td></tr>
</table>

</td></tr>
</table>

<h2>Interfaces <?= $lstlbl ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="3" valign="bottom"><img src="img/16/swit.png"><br>IF <?= $stalbl ?></th>
<th valign="bottom"><img src="img/16/dcal.png"><br><?= $coslbl ?></th>
<th valign="bottom"><img src="img/spd.png" title="<?= $spdlbl ?>"><br><?= substr($spdlbl,0,5) ?></th>
<th valign="bottom"><img src="img/16/find.png"><br><?= $deslbl ?></th>
<?php if($shg) { ?><th><img src="img/16/grph.png"><br>IF <?= $gralbl ?></th><?}?>
<?php
	if( !is_array($ifn) ){
		echo "</table>\n";
		echo "$lstlbl $emplbl";
		echo "<div align=center>$query</dev>";
		include_once ("inc/footer.php");
		die;
	}
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.17.1.4.1.2") as $ix => $val){
		$pidx[substr(strrchr($ix, "."), 1 )] = $val;
	}
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.17.2.15.1.3") as $ix => $val){
		$pstate[substr(strrchr($ix, "."), 1 )] = $val;
	}
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.17.2.15.1.5") as $ix => $val){
		$pcost[substr(strrchr($ix, "."), 1 )] = $val;
	}
	foreach( Walk($ip, $rv, $rc,"1.3.6.1.2.1.2.2.1.8") as $ix => $val){
		$ifost[substr(strrchr($ix, "."), 1 )] = $val;
	}
	asort($pidx);

	$row = 0;
	foreach($pidx as $po => $ix){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$rpimg = "";
		if($ifost[$ix] == "1" or $ifost[$ix] == "up"){
			$ifstat = "good";
		}elseif($ifost[$ix] == "2" or $ifost[$ix] == "down"){
			if($ifa[$ix]){
				$ifstat = "warn";
			}else{
				$ifstat = "alrm";
			}
		}else{
			$ifstat = "imga";
		}
		if($rport == $po){$rpimg = "<img src=\"img/16/home.png\" title=Rootport>";}
		if($pstate[$po] == 1 or $pstate[$po] == "disabled"){$pst = "<img src=\"img/16/bcls.png\" title=\"STP disabled\">";}
		elseif($pstate[$po] == 2 or $pstate[$po] == "blocking"){$pst = "<img src=\"img/16/bstp.png\" title=\"STP blocking\">";}
		elseif($pstate[$po] == 3 or $pstate[$po] == "listening"){$pst = "<img src=\"img/16/bup.png\" title=\"STP listening\">";}
		elseif($pstate[$po] == 4 or $pstate[$po] == "learning"){$pst = "<img src=\"img/16/brld.png\" title=\"STP learning\">";}
		elseif($pstate[$po] == 5 or $pstate[$po] == "forwarding"){$pst = "<img src=\"img/16/brgt.png\" title=\"STP forwarding\">";}
		else{$pst = "<img src=\"img/16/bcls.png\" title=\"broken\">";}

		$ui = urlencode($ifn[$ix]);
		list($ifimg,$iftit) = Iftype($ift[$ix]);
		TblRow($bg);
		echo "<th class=\"$ifstat\" width=\"20\"><img src=\"img/$ifimg\" title=\"$ix - $iftit\"></th>\n";
		echo "<th class=\"$bi\"  width=\"20\">$pst $sten $rpimg</th><td>\n";
		if($ifstat == "good" and $guiauth != 'none' and !isset($_GET['print'])){
			echo "<div style=\"font-weight: bold\" class=\"blu\" title=\"$rltlbl $trflbl\" onclick=\"window.open('inc/rt-popup.php?d=$debug&ip=$ip&v=$rv&c=$rc&i=$ix&t=$ud&in=$ui','$ip_$ix','scrollbars=0,menubar=0,resizable=1,width=600,height=400')\">$ifn[$ix]</div></td>\n";
		}else{
			echo "<b>$ifn[$ix]</b></td>\n";
		}
		echo "<td align=\"right\">$pcost[$po]</td><td align=\"right\">".DecFix($ifs[$ix])."</td><td>$ifi[$ix]</td>\n";
		if($shg){
			echo "<td nowrap align=\"center\">\n";
			IfGraphs($ud, $ui, $ifs[$i], ($_SESSION['gsiz'] == 4)?2:1 );
			echo "</td>";
		}
		echo "</tr>\n";
	}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Interfaces</td></tr>
</table>
<?php
}

include_once ("inc/footer.php");
?>
