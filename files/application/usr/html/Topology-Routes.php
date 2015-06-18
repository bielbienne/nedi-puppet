<?php
# Program: Topology-Routes.php
# Programmer: Remo Rickli

error_reporting(1);
snmp_set_quick_print(1);
snmp_set_oid_numeric_print(1);

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libsnmp.php");

$_GET = sanitize($_GET);
$rtr = isset($_GET['rtr']) ? $_GET['rtr'] : "";
$dst = isset($_GET['dst']) ? $_GET['dst'] : "";
$src = isset($_GET['src']) ? $_GET['src'] : "";
$trc = isset($_GET['trc']) ? $_GET['trc'] : "";
$vrf = isset($_GET['vrf']) ? $_GET['vrf'] : "";

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);

$query	= GenQuery('networks','s','*','ifip','',array('ifip'),array('!='),array('2130706433'),array(),'LEFT JOIN devices USING (device)');	# exclude 127.0.0.1
$res	= DbQuery($query,$link);
if($res){
	while( ($r = DbFetchRow($res)) ){
		$netif[long2ip($r[2])] = $r[0];
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}

$query	= GenQuery('devices','s','device,devip,type,services,readcomm,snmpversion,location,contact,cliport,icon','device','',array('services','snmpversion'),array('>','!='),array('3','0'),array('AND') );
$res	= DbQuery($query,$link);
if($res){
	while( ($d = DbFetchRow($res)) ){
		$devip[$d[0]]  = long2ip($d[1]);
		$devtyp[$d[0]] = $d[2];
		$devsrv[$d[0]] = Syssrv($d[3]);
		$devcom[$d[0]] = $d[4];
		$devver[$d[0]] = $d[5] & 3;	# TODO force ver1 for broken devices (my c2610)?
		$devloc[$d[0]] = $d[6];
		$devcon[$d[0]] = $d[7];
		$devcli[$d[0]] = $d[8];
		$devimg[$d[0]] = $d[9];
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}

?>
<h1><?= $mtitl[0] ?> <?= $mtitl[1] ?></h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php" name="rout">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
VRF <input type="text" name="vrf" value="<?= $vrf ?>" size="12">
</th>
<th>
<?= $srclbl ?> <select size=1 name="src">
<option value=""><?= $sellbl ?> ->
<?php
foreach (array_keys($netif) as $n ){
		echo "<OPTION VALUE=$n";
		if($src == $n){echo " selected";}
		echo ">$n\n";
}
?>
</select>

<?= $dstlbl ?> <select size=1 name="dst">
<?php
foreach (array_keys($netif) as $n ){
		echo "<OPTION VALUE=$n";
		if($dst == $n){echo " selected";}
		echo ">$n\n";
}
?>
</select>
<input type="submit" value="Trace" name="trc">
</th>
<th>
Router
<select size=1 name="rtr">
<option value=""><?= $sellbl ?> ->
<?php
foreach (array_keys($devtyp) as $r ){
	echo "<OPTION VALUE=\"$r\" ";
	if($rtr == $r){echo "selected";}
	echo " >$r\n";
}
?>
</SELECT>
<input type="submit" value="<?= $sholbl ?>">
</th>
</tr></table></form>
<?php
}
if($trc){
?>
<h2>Route Trace <?= $src ?> - <?= $dst ?><?= (($vrf)?" VRF $vrf":"") ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/dev.png"><br>Device</th>
<th><img src="img/16/net.png"><br><?= $netlbl ?></th>
<th><img src="img/16/home.png"><br><?= $dstlbl ?></th>
<th><img src="img/16/ncon.png" ><br>Next Hop</th>
<th><img src="img/16/dcal.png"><br>Metric 1</th>
<th><img src="img/16/find.png"><br>Protocol</th>

<?php
	$lnet	= $src;
	$currtr	= $netif[$lnet];
	$path	= "";
	$row = 0;
	while($row < 255 and $currtr){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$ur = rawurlencode($currtr);
?>
<tr class="<?= $bg ?>"><th class="<?= $bi ?>" width="80">
<a href="?rtr=<?= $ur ?>"><img src="img/dev/<?= $devimg[$currtr] ?>.png" title="<?= $devtyp[$currtr] ?>"></a>
<br><?= $currtr ?></th><td><?= $lnet ?></td>
<?php

		unset($r);
		$r = DevRoutes($devip[$currtr],$devver[$currtr],$devcom[$currtr],$vrf);
		$newabsmsk = 0;
		$pfix      = 0;
		$nho       = "not found!";
		$rpimg     = "warn";
		foreach (array_keys($r) as $rte){
			$ddst	= ip2long($rte);
			$dmsk	= ip2long($r[$rte]['msk']);
			$drte	= ip2long($dst) & $dmsk;
			$absmsk	= sprintf("%u",$dmsk);

			if($ddst  == $drte and $absmsk >= $newabsmsk){
				$newabsmsk = $absmsk;
				$ndst = $rte;
				list($pfix,$msk,$bmsk)	= Masker($r[$rte]['msk']);
				$nho			= $r[$rte]['nho'];
				$me1			= $r[$rte]['me1'];
				$rp			= RteProtoNumToText($r[$rte]['pro']);
				$rpimg			= RteProto($rp);
			}
		}
?>
<td><?= $ndst ?>/<?= $pfix ?></td><td><?= $nho ?></td>
<td align="center"><?= $me1?></td><td><img src="img/16/<?= $rpimg ?>.png"> <?= $rp ?></td></tr>
<?php
		flush();
		if ( strpos($path, $currtr) ){
			echo "<h4>$currtr $lopmsg<h4>\n";
			break;
		}
		$path .= $currtr;
		if ($nho == $ndst or $currtr == $netif[$nho]){			#We either reached the destination or an unkown device
			break;
		}else{
			$path .= " > ";
		}
		$lnet	= $nho;
		$currtr	= $netif[$nho];
	}
?>
</table>

<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Hops: <?= $path ?></td></tr>
</table>
	<?php
}elseif($rtr){
	$ud = urlencode($rtr);
?>
<h2><?= $rtr ?> <?= $sumlbl ?></h2>
<table class="content">
<tr><th class="imga" width="80">
<a href=Devices-Status.php?dev=<?= $ud ?> ><img src="img/dev/<?= $devimg[$rtr] ?>.png" title="<?= $stalbl ?>"></a>
<br><?= $rtr ?></th><td class="txta"><?= (Devcli($devip[$rtr],$devcli[$rtr])) ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $srvlbl ?></th><td class="txtb"><?= ($devsrv[$rtr])?$devsrv[$rtr]:"&nbsp;" ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $loclbl ?></th><td class="txta"><?= $devloc[$rtr] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $conlbl ?></th><td class="txtb"><?= $devcon[$rtr] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">SNMP</th><td class="txta"><?= $devcom[$rtr] ?> (Version <?= $devver[$rtr] & 7?>)</td></tr>
</table>
<?php
	#snmp_set_oid_numeric_print(1);
	foreach( Walk($devip[$rtr], $devver[$rtr], $devcom[$rtr],"1.3.6.1.3.118.1.2.2.1") as $ix => $val){
		$key = preg_replace('/(SNMPv2-SMI::experimental|[.]?1.3.6.1.3).118.1.2.2.1./','',$ix);
		$arr = explode (".", $key);
		$cat = array_shift($arr);
		$dummy = array_shift($arr);
		$vrfna = "";
		foreach ( $arr as $char ){						# VRF Name is OID...
			$vrfna .= chr($char);
		}
		$vrfs[$vrfna][$cat] = preg_replace('/\\\|"/','',$val);
	}
	if($val){
?>
<h2><?= $rtr ?> VRF <?= $lstlbl ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/abc.png"><br><?= $namlbl ?></th>
<th><img src="img/16/find.png"><br><?= $deslbl ?></th>
<th><img src="img/16/ncon.png" ><br>RD</th>
<th><img src="img/16/port.png"><br># IF</th>
<th><img src="img/16/swit.png" title"Status and router uptime at last change"><br><?= $stalbl ?></th>
<?php
		foreach (array_keys($vrfs) as $vna){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			$uvn = rawurlencode($vna);
			TblRow($bg);
			echo "<th class=\"$bi\"><a href=\"?rtr=$ud&vrf=$uvn\">$vna</a></th><td>".$vrfs[$vna]['2']."</td>\n";
			echo "<td>".$vrfs[$vna]['3']."</td><td align=\"center\">".$vrfs[$vna]['6']."/".$vrfs[$vna]['7']."</td>\n";
			echo "<td>".(($vrfs[$vna]['5'])?"<img src=\"img/16/bchk.png\"> @":"<img src=\"img/16/bdis.png\"> @").$vrfs[$vna]['11']."</td></tr>\n";
		}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>
<?php
	}else{
		echo "<h4>(VRFs: $nonlbl)</h4>";
	}
?>

<h2><?= $rtr ?> Routes <?= $lstlbl ?><?= (($vrf)?" VRF $vrf":"") ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan=2><img src="img/16/home.png"><br><?= $dstlbl ?></th>
<th><img src="img/16/ncon.png" ><br>Next Hop</th>
<th><img src="img/16/port.png"><br>Interface</th>
<th><img src="img/16/tap.png" ><br><?= $bwdlbl ?></th>
<th><img src="img/16/dcal.png"><br>Metric 1</th>
<th><img src="img/16/find.png"><br>Protocol</th>
<th><img src="img/16/clock.png"><br><?= $agelbl ?></th>
<?php
	$query	= GenQuery('interfaces','s','*','','',array('device'),array('='),array($rtr) );
	$res	= DbQuery($query,$link);
	while( ($i = DbFetchRow($res)) ){
		$ina[$i[2]] = $i[1];
		$ity[$i[2]] = $i[4];
		$ial[$i[2]] = $i[7];
		$icm[$i[2]] = $i[20];
		$isp[$i[2]] = $i[9];
	}
	DbFreeResult($res);
	$r = DevRoutes($devip[$rtr],$devver[$rtr],$devcom[$rtr],$vrf);
	$row = 0;
	foreach (array_keys($r) as $rd){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$spd	= DecFix($isp[$r[$rd]['ifx']]);
		$rp	= RteProtoNumToText($r[$rd]['pro']);
		$rpimg	= RteProto($rp);
		$unh    = rawurlencode($netif[$r[$rd]['nho']]);

		list($pfix,$msk,$bmsk)	= Masker($r[$rd]['msk']);
		list($ifimg,$iftit)	= Iftype($ity[$r[$rd]['ifx']]);
		list($ntimg,$ntit)	= Nettype($rd);
	
		TblRow($bg);
		echo "<th class=\"$bi\" width=\"20\"><img src=\"img/$ntimg\" title=$ntit></th>\n";
		echo "<td><a href=\"Topology-Networks.php?in[]=ifip&op[]==&st[]=$rd%2F$pfix&draw=png\">$rd/$pfix</a></td>\n";
		echo "<td>".$r[$rd]['nho']." <a href=?rtr=$unh&vrf=$uvn>".$netif[$r[$rd]['nho']]."</a></td><td><img src=\"img/$ifimg\" title=$iftit> ";
		echo "<b>".$ina[$r[$rd]['ifx']]."</b> <i>".$ial[$r[$rd]['ifx']]."</i> ".$icm[$r[$rd]['ifx']]."</td>\n";
		echo "<td align=right>$spd</td><td align=center>".$r[$rd]['me1']."</td>\n";
		echo "<td><img src=\"img/16/$rpimg.png\"> $rp</td><td align=right>".$r[$rd]['age']."</td>\n";
		echo "</tr>\n";
	}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>
<?php
}
include_once ("inc/footer.php");

//===================================================================
// Get IPv4 routes of a device (enhanced by Vasily) TODO put logic in .def?
// comment: Here we try to get IPv4 routes from VRF (if requested), 1 request if failed, 4 if success. todo: sanity check for VRF string. Then, if failed 
// comment: go for ipCidrRouteIfIndex (4 queries if exists), if fail -> get ipRouteIfIndex (6 queries if exists, very common oid), if fail -> inetCidrRouteIfIndex (4 queries, but very exotic oid)
// comment: if fail -> we was not lucky to get route from device via SNMP
function DevRoutes($ip,$rv,$rc,$vrfname){

	global $toumsg, $nonlbl;
	
	if(!empty($vrfname) ){
		$suffix = strlen($vrfname);
		$sufarr = str_split($vrfname);
		
		foreach ($sufarr as $char) {
			$suffix .= ".".ord ($char);
		}
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.3.118.1.4.1.1.8.$suffix") as $ix => $val){
			$r = preg_replace('/.*\.4\.(\d+\.\d+\.\d+\.\d+)\.4\.(\d+\.\d+\.\d+\.\d+)\.\d+\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$nho = preg_replace('/.*\.4\.(\d+\.\d+\.\d+\.\d+)\.4\.(\d+\.\d+\.\d+\.\d+)\.\d+\.4\.(\d+\.\d+\.\d+\.\d+)$/','$3',$ix);
			$msk = preg_replace('/.*\.4\.(\d+\.\d+\.\d+\.\d+)\.4\.(\d+\.\d+\.\d+\.\d+)\.\d+\.4\.(\d+\.\d+\.\d+\.\d+)$/','$2',$ix);
			$route[$r]['nho'] = $nho;
			$route[$r]['msk'] = $msk;
			$route[$r]['ifx'] = $val;
		}
		if(!empty($ix) ){
			#metric
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.3.118.1.4.1.1.14.$suffix") as $ix => $val){
				$r = preg_replace('/.*\.4\.(\d+\.\d+\.\d+\.\d+)\.4\.(\d+\.\d+\.\d+\.\d+)\.\d+\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$route[$r]['me1'] = $val;
				$route[$r]['vrfname']=$vrfname;
			}
			#protocol
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.3.118.1.4.1.1.10.$suffix") as $ix => $val){
				$r = preg_replace('/.*\.4\.(\d+\.\d+\.\d+\.\d+)\.4\.(\d+\.\d+\.\d+\.\d+)\.\d+\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$route[$r]['pro'] = $val;
			}
			#age
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.3.118.1.4.1.1.11.$suffix") as $ix => $val){
				$r = preg_replace('/.*\.4\.(\d+\.\d+\.\d+\.\d+)\.4\.(\d+\.\d+\.\d+\.\d+)\.\d+\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$route[$r]['age'] = $val;
			}
			return $route;
		}else{
			echo "<h4>(VRFs: $nonlbl)</h4>";
		}		
	}
	
	#snmp_set_oid_numeric_print(1); Doesn't work eveerywhere, so I use the preg_replace hack below to avoid problems 
	# now we should try to get ipCidrRouteIfIndex oid. Full table in 4 queries if exists.
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.4.1.5") as $ix => $val){
		$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)\.(\d+\.\d+\.\d+\.\d+)\.\d+\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
		$msk = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)\.(\d+\.\d+\.\d+\.\d+)\.\d+\.(\d+\.\d+\.\d+\.\d+)$/','$2',$ix);
		$nho = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)\.(\d+\.\d+\.\d+\.\d+)\.\d+\.(\d+\.\d+\.\d+\.\d+)$/','$3',$ix);
		$route[$r]['nho'] = $nho;
		$route[$r]['msk'] = $msk;
		$route[$r]['ifx'] = $val;
	}
	if(!$ix ){
		# no ipCidrRouteIfIndex OID, lets try ipRouteIfIndex OID (old one, but very common). This one will complete in 6 SNMP queries.
		echo "<h4>(ipCidrRouteIfIndex: $nonlbl)</h4>";
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.21.1.2") as $ix => $val){
			$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$route[$r]['ifx'] = $val;		
		}
		if(!$ix ){
			#no luck with generic OIDs, try even more exotic inetCidrRoute
			#walking for inetCidrRouteIfIndex
			#netmask in really CIDR now

			echo "<h4>(generic OIDs: $nonlbl)</h4>";

			snmp_set_oid_numeric_print(TRUE);
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.7.1.7") as $ix => $val){
				$r       = preg_replace('/.*\.1\.4\.(\d+\.\d+\.\d+\.\d+)\.(\d+)\.\d+\.1\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$mskcidr = preg_replace('/.*\.1\.4\.(\d+\.\d+\.\d+\.\d+)\.(\d+)\.\d+\.1\.4\.(\d+\.\d+\.\d+\.\d+)$/','$2',$ix);
				$nho     = preg_replace('/.*\.1\.4\.(\d+\.\d+\.\d+\.\d+)\.(\d+)\.\d+\.1\.4\.(\d+\.\d+\.\d+\.\d+)$/','$3',$ix);
				$mskpfix = Masker($mskcidr);
				$route[$r]['nho'] = $nho;
				$route[$r]['msk'] = $mskpfix[1];
				$route[$r]['ifx'] = $val;
			}
			if(!$ix ){							#no OIDs for route info, timeout or route do not exist. 
				echo "</table><h4>$toumsg</h4>";
				if($_SESSION['vol']){echo "<embed src=\"inc/enter2.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
				die;
			}
			#metric inetCidrRouteMetric1
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.7.1.12") as $ix => $val){
				$r = preg_replace('/.*\.1\.4\.(\d+\.\d+\.\d+\.\d+)\.(\d+)\.\d+\.1\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$route[$r]['me1'] = $val;
			}
			#protocol inetCidrRouteProto
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.7.1.9") as $ix => $val){
				$r = preg_replace('/.*\.1\.4\.(\d+\.\d+\.\d+\.\d+)\.(\d+)\.\d+\.1\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$route[$r]['pro'] = $val;
			}
			#age inetCidrRouteAge
			foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.7.1.10") as $ix => $val){
				$r = preg_replace('/.*\.1\.4\.(\d+\.\d+\.\d+\.\d+)\.(\d+)\.\d+\.1\.4\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
				$route[$r]['age'] = $val;
			}
			snmp_set_oid_numeric_print(FALSE);
			return $route;
		}
		#metric ipRouteMetric1
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.21.1.3") as $ix => $val){
			$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$route[$r]['me1'] = $val;
		}
		#nexthop ipRouteNextHop
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.21.1.7") as $ix => $val){
			$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$route[$r]['nho'] = $val;
		}
		#proto ipRouteProto
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.21.1.9") as $ix => $val){
			$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$route[$r]['pro'] = $val;
		}
		#age ipRouteAge
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.21.1.10") as $ix => $val){
			$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$route[$r]['age'] = $val;
		}
		#netmask ipRouteMask
		foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.21.1.11") as $ix => $val){
			$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
			$route[$r]['msk'] = $val;
		}
		return $route;		
	}
	#metric ipCidrRouteMetric1
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.4.1.11") as $ix => $val){
		$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)\.(\d+\.\d+\.\d+\.\d+)\.[0-9]\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
		$route[$r]['me1'] = $val;
	}
	#protocol ipCidrRouteProto
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.4.1.7") as $ix => $val){
		$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)\.(\d+\.\d+\.\d+\.\d+)\.[0-9]\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
		$route[$r]['pro'] = $val;
	}
	#age ipCidrRouteAge
	foreach( Walk($ip,$rv,$rc,"1.3.6.1.2.1.4.24.4.1.8") as $ix => $val){
		$r = preg_replace('/.*\.(\d+\.\d+\.\d+\.\d+)\.(\d+\.\d+\.\d+\.\d+)\.[0-9]\.(\d+\.\d+\.\d+\.\d+)$/','$1',$ix);
		$route[$r]['age'] = $val;
	}
	return $route;
}

//===================================================================
// Return Routing Protocol
function RteProto($p) {

	if	($p == "local")	{return "fogr";}
	elseif	($p == "netmgmt"){return "fobl";}
	elseif	($p == "icmp")	{return "fobl";}
	elseif	($p == "egp")	{return "fobl";}
	elseif	($p == "ggp")	{return "fobl";}
	elseif	($p == "hello")	{return "fobl";}
	elseif	($p == "rip")	{return "fovi";}
	elseif	($p == "is-is")	{return "fobl";}
	elseif	($p =="es-is")	{return "fobl";}
	elseif	($p =="ciscoIgrp"){return "foye";}
	elseif	($p =="bbnSpfIgp"){return "fobl";}
	elseif	($p =="ospf")	{return "foor";}
	elseif	($p =="bgp")	{return "ford";}
	else{return "fogy";}
}

//===================================================================
// Return Routing Protocol String (by Vasily)
function RteProtoNumToText($p) {

	if	($p == 2 )	{return "local";}
	elseif	($p == 3 )	{return "netmgmt";}
	elseif	($p == 4 )	{return "icmp";}
	elseif	($p == 5 )	{return "egp";}
	elseif	($p == 6 )	{return "ggp";}
	elseif	($p == 7 )	{return "hello";}
	elseif	($p == 8 )	{return "rip";}
	elseif	($p == 9 )	{return "is-is";}
	elseif	($p == 10 )	{return "es-is";}
	elseif	($p == 11 )	{return "ciscoIgrp";}
	elseif	($p == 12 )	{return "bbnSpfIgp";}
	elseif	($p == 13 )	{return "ospf";}
	elseif	($p == 14 )	{return "bgp";}
	else{return $p;}
}

?>
