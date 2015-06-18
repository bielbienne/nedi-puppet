<?php
# Program: Nodes-Status.php
# Programmer: Remo Rickli

#$timeout = 1;												# Uncomment this, if you override the timeout for the mini-portscan
$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libnod.php");
include_once ("inc/libmon.php");
include_once ("inc/libdev.php");

$_GET = sanitize($_GET);
$mac = isset($_GET['mac']) ? $_GET['mac'] : "";
$wol = isset($_GET['wol']) ? $_GET['wol'] : "";
$del = isset($_GET['del']) ? $_GET['del'] : "";
$trk = isset($_GET['trk']) ? $_GET['trk'] : "";
$mon = isset($_GET['mon']) ? $_GET['mon'] : "";
?>
<script src="inc/Chart.min.js"></script>

<h1>Node <?= $stalbl ?></h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
MAC <?= $adrlbl ?> <input type="text" name="mac" value="<?= $mac ?>" size="15">
</th>
<th width="80"><input type="submit" value="<?= $sholbl ?>"></th>
</tr></table></form><p>
<?php
}
$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if ($trk){
	$mac = $trk;
	if($isadmin){
		$query	= GenQuery('nodes','u','mac','=',$mac,array('ipchanges'),array(),array('0') );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$mac ipchanges $updlbl OK</h5>";}
		$query	= GenQuery('nodes','u','mac','=',$mac,array('ifchanges'),array(),array('0') );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$mac ifchanges $updlbl OK</h5>";}
		$query	= GenQuery('iptrack','d','','','',array('mac'),array('='),array($mac) );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$mac iptrack $dellbl OK</h5>";}
		$query	= GenQuery('iftrack','d','','','',array('mac'),array('='),array($mac) );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$mac iftrack $dellbl OK</h5>";}
	}else{
		echo $nokmsg;
	}
}

if ($mac){
	$query	= GenQuery('nodes','s','nodes.*,location,contact,snmpversion','','',array('mac'),array('='),array($mac),array(),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	$nnod	= DbNumRows($res);
	while( ($n = DbFetchRow($res)) ){
		$name		= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
		$ip		= long2ip($n[1]);
		if(!$name) $name = $ip;
		$ip6		= ($n[16])?DbIPv6($n[16]):'';
		$img		= Nimg($n[3]);
		$fs		= date($datfmt,$n[4]);
		$ls		= date($datfmt,$n[5]);
		list($fc,$lc)	= Agecol($n[4],$n[5],0);
		$wasup		= ($n[5] > time() - $rrdstep*2)?1:0;
		$ud 		= urlencode($n[6]);
		$ui 		= urlencode($n[7]);
		$au		= date($datfmt,$n[12]);
		list($a1c,$a2c) = Agecol($n[12],$n[12],0);
		$fu		= date($datfmt,$n[21]);
		list($f1c,$f2c) = Agecol($n[21],$n[21],0);
		$l		= explode($locsep, $n[23]);

		if($n[7]){
			$query	= GenQuery('interfaces','s','*','','',array('device','ifname'),array('=','='),array($n[6],$n[7]),array('AND') );
			$res	= DbQuery($query,$link);
			if (DbNumRows($res) == 1) {
				$if	= DbFetchRow($res);
				list($ifbg,$ifst)   = Ifdbstat($if[8]);
				list($ifimg,$iftyp) = Iftype($if[4]);
			}else{
				echo "<h4>IF DB $errlbl</h4>";
			}
			DbFreeResult($res);
			$iu		= date($datfmt,$n[10]);
			list($i1c,$i2c) = Agecol($n[10],$n[10],1);
		}
		$vl[2] = "-";
		if($n[8] and $n[9] > 255){
			$query	= GenQuery('vlans','s','*','','',array('device','vlanid'),array('=','='),array($n[6],$n[8]),array('AND') );
			$res	= DbQuery($query,$link);
			if (DbNumRows($res) == 1) {
				$vl = DbFetchRow($res);
			}else{
				echo "<h4>Vlan DB $errlbl</h4>";
			}
			DbFreeResult($res);
		}
	
		$query	= GenQuery('monitoring','s','*','','',array('monip'),array('='),array($n[1]) );
		$res	= DbQuery($query,$link);
		if (DbNumRows($res) == 1) {
			$most = DbFetchRow($res);
			list($statbg,$stat) = StatusBg(1,($most[3])?1:0,$most[7],"imga");
		}else{
			$statbg = "imga";
			$stat   = "";
		}
		DbFreeResult($res);
?>

<table class="full fixed"><tr><td class="helper">

<h2><?= $sumlbl ?></h2><p>
<table class="content"><tr>
<th class="<?= $statbg ?>" width="80"><a href="?mac=<?= $n[2] ?>"><img src="img/oui/<?= $img ?>.png" title="<?= $stat ?>"></a><br><?= $name ?></th>
<th class="<?= $modgroup[$self] ?>2">

<div  style="float:left">
<?php
		if(preg_match("/dsk/",$_SESSION['group']) ){
			echo "<a href=\"Nodes-Stolen.php?na=$n[0]&ip=$ip&stl=$n[2]&dev=$ud&ifn=$ui\"><img src=\"img/16/step.png\" title=\"Mark as stolen!\"></a>";
			echo "<a href=\"$_SERVER[PHP_SELF]?wol=$n[2]\"><img src=\"img/16/exit.png\" title=\"WOL $srvlbl\"></a>";
		}
		$src = $mac.(($n[0] == "" or $n[0] == "-")?"":"|$n[0]").(($ip)?"|^$ip$":"");
?>
<a href="Monitoring-Events.php?in[]=source&in[]=info&op[]=~&op[]=~&st[]=<?= $src ?>&st[]=<?= $src ?>&co[]=OR"><img src="img/16/bell.png" title="<?= $msglbl ?>"></a>

</div><div  style="float:right">

<?php
		if($isadmin){
			if($n[1]){
				if(!isset($most) ){
					if ($mon == 1){
						$mona  = ($n[0])?$n[0]:$ip;
						echo AddRecord('monitoring',"name='$mona'","name,monip,class,test,device,depend","'$mona','$n[1]','node','ping','$n[6]','$n[6]'");
					}else{
						echo "<a href=\"?mac=$mac&mon=1\"><img src=\"img/16/bino.png\" title=\"Monitor $addlbl\"></a>";
					}
				}else{
					echo "<a href=\"Monitoring-Setup.php?in[]=monip&op[]=%3D&st[]=$ip\">".TestImg($most[3])."</a>";
				}
			}
			echo "<a href=\"Devices-Status.php?dev=".urlencode($name)."&ip=$ip&sn=$n[2]&ina=Et1&mac=$n[2]&spd=$if[9]&ico=svan&loc=".urlencode($n[23])."&con=".urlencode($n[24])."\"><img src=\"img/16/sys.png\" title=\"$addlbl Device\"></a>";
			echo "<a href=\"?trk=$n[2]\"><img src=\"img/16/bdis.png\" onclick=\"return confirm('$dellbl IF/IP $chglbl  $n[2]?')\" title=\"$dellbl IF/IP $chglbl\"></a>";
			echo "<a href=\"?del=$n[2]\"><img src=\"img/16/bcnl.png\" onclick=\"return confirm('$dellbl $n[2] ?')\" title=\"$dellbl Node!\"></a>";
		}
?>

</div>

</th></tr>
<tr><th class="<?= $modgroup[$self] ?>2">MAC <?= $adrlbl ?></th>	<td class="txta">
<b class="drd"><?= rtrim(chunk_split($n[2],2,"-"),"-") ?></b> -
<b class="drd"><?= rtrim(chunk_split($n[2],2,":"),":") ?></b> -
<b class="drd"><?= rtrim(chunk_split($n[2],4,"."),".") ?></b></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">NIC <?= $venlbl ?></th>	<td class="txtb"><a href="http://www.google.com/search?q=<?= urlencode($n[3]) ?>&btnI=1" target="window"><?= $n[3] ?></a></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $dsclbl ?></th>	<th class="txta">
<span style="border:1px solid black;border-radius: 6px;padding : 0 3px;background-color:#<?= $fc ?>" title="<?= $fislbl ?>"> <?= $fs ?> </span>
<?= Bar( intval(($n[5]-$n[4])/86400),0,'mi',$tim['d']) ?>
<span style="border:1px solid black;border-radius: 6px;padding : 0 3px;background-color:#<?= $lc ?>" title="<?= $laslbl ?>"> <?= $ls ?> </span>
</th></tr>

<tr><th class="<?= $modgroup[$self] ?>2">IP <?= $adrlbl ?></th>	<td class="txta">

<div style="float:right;margin:2px 2px">
<a href="Nodes-Toolbox.php?Dest=<?= $ip ?>"><img src="img/16/dril.png" title="Toolbox"></a>
</div>

<?php
		if($n[1] and $wasup and $isadmin) { ?>
<div style="float:right;margin:2px 2px">
<form method="post" name="nedi" action="System-NeDi.php">
<input type="hidden" name="mde" value="d">
<input type="hidden" name="sed" value="a">
<input type="hidden" name="vrb" value="v">
<input type="hidden" name="opt" value="<?= $ip ?>">
<input type="image" src="img/16/radr.png" value="Submit" title="<?=  (($verb1)?"$dsclbl $tim[n]":"$tim[n] $dsclbl")  ?>">
</form>
</div>

<div style="float:right;margin:2px 2px">
<form method="post" name="nedi" action="System-NeDi.php">
<input type="hidden" name="mde" value="s">
<input type="hidden" name="opt" value="<?= $ip ?>">
<input type="hidden" name="vrb" value="v">
<input type="image" src="img/16/find.png" value="Submit" title="Scan Node">
</form>
</div>
<?php
		}
?>

<span class="blu"><?= $ip ?></span> DNS <?= $tim['n']?>: <?= ($n[1])?gethostbyaddr($ip):"" ?>
<br><span class="prp"><?= $ip6 ?></span>
</td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">IP <?= $updlbl ?></th>	<td class="txtb"><span style="border:1px solid black;border-radius: 6px;padding : 0 3px;background-color:#<?= $a1c ?>"> <?= $au ?> </span> &nbsp; (<?= $n[13] ?> <?= $chglbl ?> / <?= $n[14] ?> <?= $loslbl ?> / <?= $n[15] ?> ARP <?= $vallbl ?>)</td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">Device</th>		<td class="txta"><a href="Devices-Status.php?dev=<?= $ud ?>&pop=on"><img src="img/16/sys.png" title="<?= $n[6] ?> <?= $stalbl ?>"></a><b><?= $n[6] ?></b> <img src="img/16/home.png" title="<?= $loclbl ?>"> <?= $l[1] ?> <?= $l[2] ?> <img src="img/16/ucfg.png" title="<?= $conlbl ?>"> <?= $n[24] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">Interface</th>		<td class="<?= ($ifbg)?$ifbg:"txtb" ?>"><img src="img/<?= $ifimg ?>" title="<?= $iftyp ?> - <?= $ifst ?>"><b><?= $n[7] ?></b> (<?= DecFix($if[9]) ?>-<?= $if[10] ?>) <i><?= $if[7] ?> <?= $if[20] ?></i>, <?= $stalbl ?> <?= $chglbl ?> <?= date($datfmt,$if[26])?> </td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">Vlan</th>		<td class="txta"><?= $n[8] ?> <?= $vl[2] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $laslbl ?></th>	<td class="txtb"><?= $trflbl ?> <?= DecFix($if[16]) ?>/<?= DecFix($if[18]) ?> <?= $errlbl ?> <?= DecFix($if[17]) ?>/<?= DecFix($if[19]) ?> Discards <?= DecFix($if[22]) ?>/<?= DecFix($if[23]) ?> Broadcasts <?= DecFix($if[25]) ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $totlbl ?></th>	<td class="txta"><?= $trflbl ?> <?= DecFix($if[12]) ?>/<?= DecFix($if[14]) ?> <?= $errlbl ?> <?= DecFix($if[13]) ?>/<?= DecFix($if[15]) ?> Discards <?= DecFix($if[20]) ?>/<?= DecFix($if[21]) ?> Broadcasts <?= DecFix($if[24]) ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">IF <?= $updlbl ?></th>	<td class="txtb"><span style="border:1px solid black;border-radius: 6px;padding : 0 3px;background-color:#<?= $i1c ?>"> <?= $iu ?> </span> &nbsp; - <?= $chglbl ?>: <?= $n[11] ?> - <?= ($n[9] < 255)?"SNR ".Bar($n[9],-30,'mi')."$n[9]db":"Metric $n[9]" ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $usrlbl ?></th>	<td class="txta"><?= $n[22] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">TCP <?= $porlbl ?></th>		<td class="txtb"><?= $n[17] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">UDP <?= $porlbl ?></th>		<td class="txta"><?= $n[18] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><?= $typlbl ?>/OS</th>	<td class="txtb"><?= $n[19] ?> / <?= $n[20] ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2">OS <?= $updlbl ?></th>	<td bgcolor=#<?= $f1c ?>><?= $fu ?></td></tr>

</table>

</td><td class="helper">

<?php
		flush();
		if($n[1]){
?>
<h2><?= $srvlbl ?></h2><p>
<table class="content"><tr>
<th class="<?= $modgroup[$self] ?>2" width="80"><img src="img/32/nwin.png"><br>Netbios</th><td class="txta"><?= (($wasup)?NbtStat($ip):"-") ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><a href="http://<?= $ip ?>" target="window"><img src="img/32/glob.png"></a><br>HTTP</th>
<td class="txtb"><?= (($wasup)?CheckTCP($ip,'80',"GET / HTTP/1.0\r\n\r\n"):"-") ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><a href="https://<?= $ip ?>" target="window"><img src="img/32/glok.png"></a><br>HTTPS</th>
<td class="txta"><?= (($wasup)?CheckTCP($ip,'443',''):"-") ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><a href="ssh://<?= $ip ?>"><img src="img/32/lokc.png"></a><br>SSH</th>
<td class="txtb"><?= (($wasup)?CheckTCP($ip,'22',''):"-") ?></td></tr>
<tr><th class="<?= $modgroup[$self] ?>2"><a href="telnet://<?= $ip ?>"><img src="img/32/loko.png"></a><br>Telnet</th>
<td class="txta"><?= (($wasup)?CheckTCP($ip,'23','\n'):"-") ?></td></tr>
</table>
<?php
		}else{
			echo "<h4>No IP!</h4>";
		}
		echo "</td></tr></table>";
?>

<?PHP		if(($n[25]) and $rrdcmd){?>
<table class="full fixed">
<tr><td class="helper" align="center">

<h2><?= $n[6] ?>-<?= $n[7] ?> <?= $gralbl ?></h2>
<?PHP IfGraphs($ud, $ui, $if[9], $_SESSION['gsiz']); ?>

</td></tr>
</table>
<?		}?>

<table class="full fixed">
<tr><td class="helper" align="center">

<h2><?= $stslbl ?> <?= $totlbl ?> / <?= $laslbl ?></h2>
<?PHP IfRadar('radtot','4','248',$if[12],$if[14],$if[13],$if[15],$if[20],$if[21],$if[24],1); ?>

<?PHP IfRadar('radlast','4','284',$if[16],$if[18],$if[17],$if[19],$if[22],$if[23],$if[25],1); ?>

</td><td class="helper" align="center">

<h2>Nodemap</h2>
<a href="Topology-Map.php?tit=<?= $ud ?>+<?= $neblbl ?>+Map&in[]=mac&op[]==&st[]=<?=$n[2] ?>&mde=f&fmt=png&xo=-100&lev=6&ifi=on&&lit=w&ipd=on&loo=on"><img style="border:1px solid black" src="inc/drawmap.php?dim=400x200&in[]=mac&op[]==&st[]=<?= $n[2]?>&mde=f&lev=6&xo=-100&pos=s&ifi=on&len=150&lit=w&ipd=on&loo=on"></a>

</td></tr>
</table>

<table class="full fixed">
<tr><td class="helper">

<h2>IP <?= $chglbl ?></h2>

<?php
		$query	= GenQuery('iptrack','s','*','ipupdate','',array('mac'),array('='),array($n[2]) );
		$res	= DbQuery($query,$link);
		if( DbNumRows($res) ){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan=2><img src="img/16/clock.png"><br><?= $updlbl ?></th>
<th><img src="img/16/abc.png"><br><?= $namlbl ?></th>
<th><img src="img/16/net.png"><br>IP <?= $adrlbl ?></th>
</table>
<div class="scroller">
<table class="content" >
<?php
			$row = 0;
			while( $l = DbFetchRow($res) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$lip = long2ip($l[3]);
				echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
				echo "$row</th><td>". date($datfmt,$l[1]) ."</td><td>$l[2]</td><td><a href=\"Nodes-List.php?in[]=nodip&op[]==&st[]=$lip\">$lip</a></td></tr>\n";
			}
			DbFreeResult($res);
?>
</table>
</div>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> IP <?= $chglbl ?></td></tr>
</table>
<?php
		}else{
?>
<h5><?= $nonlbl ?></h5>
<?php
		}
?>
</td><td class="helper">

<h2>IF <?= $chglbl ?></h2>

<?php
		$query	= GenQuery('iftrack','s','*','ifupdate','',array('mac'),array('='),array($n[2]) );
		$res	= DbQuery($query,$link);
		if( DbNumRows($res) ){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan=2><img src="img/16/clock.png"><br><?= $updlbl ?></th>
<th><img src="img/16/dev.png"><br>Device</th>
<th><img src="img/16/port.png"><br>IF</th>
<th><img src="img/16/vlan.png"><br>Vlan</th>
<th><img src="img/16/dcal.png"><br>Metric</th>
</table>
<div class="scroller">
<table class="content" >
<?php
			$row = 0;
			while( $l = DbFetchRow($res) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$utd = rawurlencode($l[2]);
				$uti = rawurlencode($l[3]);
				echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
				echo "$row</th><td>". date($datfmt,$l[1]) ."</td>\n";
				echo "<td><a href=\"Devices-Status.php?dev=$utd&shp=on\">$l[2]</a></td><td>";
				echo "<a href=\"Nodes-List.php?in[]=device&op[]==&st[]=$utd&co[]=AND&in[]=ifname&op[]==&st[]=$uti\">$l[3]</td><td>$l[4]</td><td>$l[5]</td></tr>\n";
			}
			DbFreeResult($res);
?>
</table>
</div>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> IF <?= $chglbl ?></td></tr>
</table>

<?php
		}else{
?>
<h5><?= $nonlbl ?></h5>
<?php
		}
?>
</td></tr></table>
<?php
	}
}elseif ($wol){
	if(preg_match("/dsk/",$_SESSION['group']) ){
		$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
		$query	= GenQuery('nodes','s','*','','',array('mac'),array('='),array($wol));
		$res	= DbQuery($query,$link);
		$nnod	= DbNumRows($res);
		if ($nnod != 1) {
			echo "<h4>$wol: $nnod $vallbl!</h4>";
			DbFreeResult($res);
			die;
		}else{
			$n  = DbFetchRow($res);
			DbFreeResult($res);
			$ip = long2ip($n[1]);
		}
		$query	= GenQuery('networks','s','inet_ntoa(ifip|power(2, 32 - prefix )-1)','','1',array('ifip','(ifip|power(2, 32 - prefix )-1)'),array('>','COL ='),array(0,"($n[1]|power(2, 32 - prefix )-1)"),array('AND'));
		$bres = DbQuery($query,$link);
		$bcst = DbFetchRow($bres);
		Wake($bcst[0],$wol, 9);
		Wake("255.255.255.255",$wol, 9);							# In case local broadcast addr is not allowed
	}else{
		echo $nokmsg;
	}
?>
<script language="JavaScript"><!--
setTimeout("history.go(-1)",3000);
//--></script>
<?php
}elseif ($del){
	if($isadmin){
		$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
		$query	= GenQuery('nodes','d','','','',array('mac'),array('='),array($del) );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$del $dellbl OK</h5>";}
		$query	= GenQuery('iptrack','d','','','',array('mac'),array('='),array($del) );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$del iptrack $dellbl OK</h5>";}
		$query	= GenQuery('iftrack','d','','','',array('mac'),array('='),array($del) );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$del iptrack $dellbl OK</h5>";}
?>
<script language="JavaScript"><!--
setTimeout("history.go(-2)",3000);
//--></script>
<?php
	}else{
		echo $nokmsg;
	}
}

include_once ("inc/footer.php");
?>
