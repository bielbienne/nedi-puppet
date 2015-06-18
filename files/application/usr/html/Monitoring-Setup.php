<?php
# Program: Monitoring-Setup.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$tst = isset($_GET['tst']) ? $_GET['tst'] : "";
$top = isset($_GET['top']) ? $_GET['top'] : "";
$trs = isset($_GET['trs']) ? $_GET['trs'] : "";
$adp = isset($_GET['adp']) ? $_GET['adp'] : "";
$rav = isset($_GET['rav']) ? $_GET['rav'] : "";
$uip = isset($_GET['uip']) ? $_GET['uip'] : "";
$efd = isset($_GET['efd']) ? $_GET['efd'] : "";
$elv = isset($_GET['elv']) ? $_GET['elv'] : "";
$inf = isset($_GET['inf']) ? $_GET['inf'] : "";
$al  = isset($_GET['al']) ? $_GET['al'] : "";

$nrp = isset($_GET['nrp']) ? $_GET['nrp'] : "";
$law = isset($_GET['law']) ? $_GET['law'] : "";
$nfy = isset($_GET['nfy']) ? $_GET['nfy'] : "";

$cpa = isset($_GET['cpa']) ? $_GET['cpa'] : "";
$mea = isset($_GET['mea']) ? $_GET['mea'] : "";
$tea = isset($_GET['tea']) ? $_GET['tea'] : "";
$pow = isset($_GET['pow']) ? $_GET['pow'] : "";
$arp = isset($_GET['arp']) ? $_GET['arp'] : "";
$sua = isset($_GET['sua']) ? $_GET['sua'] : "";

$upd = isset($_GET['upd']) ? $_GET['upd'] : "";
$del = isset($_GET['del']) ? $_GET['del'] : "";

$des = isset($_GET['des']) ? $_GET['des'] : "";
$dpt = isset($_GET['dpt']) ? $_GET['dpt'] : "";
$dps = isset($_GET['dps']) ? $_GET['dps'] : "";

$cols = array(	"name"=>"Name",
		"monip"=>"IP $adrlbl",
		"class"=>$clalbl,
		"depend"=>$deplbl,
		"test"=>"$tstlbl",
		"noreply"=>"$nonlbl $rpylbl",
		"alert"=>$mlvl['200'],
		"latwarn"=>"$latlbl $mlvl[150]",
		"testopt"=>"$tstlbl $sndlbl",
		"testres"=>"$tstlbl $rcvlbl",
		"lastok"=>"$laslbl OK",
		"status"=>$stalbl,
		"lost"=>$loslbl,
		"eventdel"=>"$msglbl $dcalbl",
		"eventlvl"=>"$levlbl $limlbl",
		"eventfwd"=>"$msglbl $fwdlbl",
		"notify"=>"notify",
		"cpualert"=>"CPU $mlvl[200]",
		"memalert"=>"Mem $mlvl[200]",
		"tmpalert"=>"$tmplbl $mlvl[200]",
		"poewarn"=>"PoE $mlvl[150]",
		"arppoison"=>"ARPpoison",
		"supplyalert"=>"Supply $mlvl[200]",
		"type"=>"Device $typlbl",
		"devos"=>"Device OS",
		"bootimage"=>"Bootimage",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"devgroup"=>$grplbl
		);

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);

function MonUpdate($tgt,$c,$v,$t,$p){
	
	global $link,$updlbl;

	if($v === "-"){$v='';}
	$uquery	= GenQuery('monitoring','u','name','=',$tgt,array($c),array(),array($v) );
	if( !DbQuery($uquery,$link) ){
		return array ("<img src=\"img/16/bcnl.png\" title=\"" .DbError($link)."\">",$p);
	}else{
		return array ("<img src=\"img/16/bchk.png\" title=\"$t $updlbl OK\">",$v);
	}
}

?>
<h1>Monitoring Setup</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" 'action'="<?= $self ?>.php" name="mons">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top" nowrap>
<h3><?= $fltlbl ?></h3>
<a href="?in[]=status&op[]=>&st[]=0"><img src="img/16/flag.png" title="<?= $tgtlbl ?> <?= $errlbl ?>"></a>
<a href="?in[]=test&op[]=%3D&st[]=uptime"><img src="img/16/clock.png" title="SNMP <?= $tgtlbl ?>"></a>
<a href="?in[]=depend&op[]=%3D&st[]=-"><img src="img/16/ncon.png" title="<?= $nonlbl ?> <?= $deplbl ?>"></a>
<a href="?in[]=eventfwd&op[]=~&st[]=."><img src="img/16/mail.png" title="<?= $msglbl ?> <?= $fwdlbl ?>"></a>
<a href="?in[]=eventdel&op[]=~&st[]=."><img src="img/16/bdis.png" title="<?= $msglbl ?> <?= $dcalbl ?>"></a>
<a href="?in[]=eventlvl&op[]=!%3D&st[]=0"><img src="img/16/fogy.png" title="<?= $levlbl ?> <?= $limlbl ?>"></a>

<?PHP Filters(1); ?>

</td>
<td valign="top" nowrap>

<h3><?= $monlbl ?></h3>
<img src="img/16/bchk.png" title="<?= $tstlbl ?>">
<select size="1" name="tst">
<option value=""><?= $sellbl ?>->
<option value="none">(<?= $nonlbl ?>)
<option value="cifs">cifs
<option value="dns">dns
<option value="mysql">mysql
<option value="ntp">ntp
<option value="http">http
<option value="https">https
<option value="ping">ping
<option value="ssh">ssh
<option value="telnet">telnet
<option value="uptime">uptime
</select>
<input type="number" min="1" max="9" name="nrp" size="1" title="# <?= $nonlbl ?> <?= $rpylbl ?>">

<select size="1" name="al" title="<?= $mlvl['200'] ?>">
<option value=""><?= $mlvl['200'] ?>
<option value="1"><?= $nonlbl ?>
<option value="2"><?= $msglbl ?>
<option value="3">Mail
<option value="131">Mail (<?= substr($rptlbl,0,6) ?>)
<option value="7">Mail & SMS
<option value="135">M&S (<?= substr($rptlbl,0,6) ?>)
</select>
<input type="number" min="5" step="10" name="law" size="3" title="<?= $latlbl ?> <?= $mlvl['150'] ?> [ms]">
<br>

<img src="img/16/bbrt.png" title="<?= $tstlbl ?> <?= $sndlbl ?>">
<input type="text" name="top" size="32">
<br>
<img src="img/16/bblf.png" title="<?= $tstlbl ?> <?= $rcvlbl ?>">
<input type="text" name="trs" size="32">

</td>
<td valign="top" nowrap>

<h3><?= $msglbl ?></h3>
<img src="img/16/bell.png" title="Syslog, Trap, <?= $dsclbl ?>">
<select size="1" name="efd">
<option value="fwd"><?= $fwdlbl ?>
<option value="del"><?= $dcalbl ?>
</select>

<select size="1" name="elv">
<option value=""><?= $levlbl ?> <?= $limlbl ?>
<option value="1"><?= $nonlbl ?>
<option value="11"  class="txtb"><?= $mlvl['10'] ?>
<option value="51"  class="good"><?= $mlvl['50'] ?>
<option value="101" class="noti"><?= $mlvl['100'] ?>
<option value="151" class="warn"><?= $mlvl['150'] ?>
<option value="201" class="alrm"><?= $mlvl['200'] ?>
<option value="251" class="crit"><?= $mlvl['250'] ?>
</select>
<br>
<img src="img/16/abc.png" title="<?= $fltlbl ?>">
<input type="text" name="inf" size="20">
<br>
<img src="img/16/radr.png" title="notify">
<input type="text" name="nfy" size="20">

</td>
<td valign="top" nowrap>

<h3><?= $dsclbl ?></h3>

<img src="img/16/cpu.png" title="CPU <?= $mlvl['200'] ?>"><input type="number" min="5" max="100" step="5" name="cpa" size="2">
<img src="img/16/mem.png" title="Mem <?= $mlvl['200'] ?>"><input type="number" min="10" step="10" name="mea" size="3">
<br>
<img src="img/16/temp.png" title="<?= $tmplbl ?> <?= $mlvl['200'] ?>"><input type="number" min="5" max="195" step="5" name="tea" size="2">
<img src="img/16/batt.png" title="PoE <?= $mlvl['150'] ?>"><input type="number" min="10" step="10" name="pow" size="3">
<br>
<img src="img/16/drop.png" title="ARPpoison"><input type="number" min="1" name="arp" size="2">
<img src="img/16/file.png" title="Supply <?= $mlvl[200] ?>"><input type="number" min="0" name="sua" size="3">

</td>
<th valign="top" nowrap>

<h3><?= $reslbl ?></h3>
<img src="img/16/ncon.png" title="Auto <?= $deplbl ?>"> 
<input type="checkbox" name="adp">
<br>
<img src="img/16/net.png" title="IP <?= $updlbl ?>"> 
<input type="checkbox" name="uip">
<br>
<img src="img/16/walk.png" title="<?= $avalbl ?>">
<input type="checkbox" name="rav">
</th>

<th width="80">
<input type="submit" value="<?= $sholbl ?>">
<p>
<input type="submit" name="upd" value="<?= $updlbl ?>">
<p>
<input type="submit" name="del" value="<?= $dellbl ?>" onclick="return confirm('Monitor <?= $dellbl ?>, <?= $cfmmsg ?>')" >

</th>
</tr></table></form><p>
<?php
}
if($del){
	$query	= GenQuery('monitoring','d','','','',$in,$op,$st,$co);
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$dellbl $ina $op[0] $sta OK</h5>";}
}

if( count($in) ){
	Condition($in,$op,$st,$co);
?>

<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/trgt.png"><br><?= $tgtlbl ?></th>
<th><img src="img/16/chrt.png"><br><?= $stslbl ?></th>
<th><img src="img/16/bchk.png"><br><?= $tstlbl ?></th>
<th><img src="img/16/ncon.png"><br><?= $deplbl ?></th>
<th><img src="img/16/flag.png"><br><?= $mlvl['200'] ?> </th>
<th><img src="img/16/bell.png"><br><?= $msglbl ?> <?= $actlbl ?>
<th><img src="img/16/radr.png"><br><?= $dsclbl ?></th>
</th></tr>

<?php
	$query	= GenQuery('monitoring','s','monitoring.*,devip','monitoring.name','',$in,$op,$st,$co,'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$row  = 0;
		$nnod = 0;
		$ndev = 0;
		$srcip= 0;
		while( ($mon = DbFetchRow($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			$una = urlencode($mon[0]);
			list($statbg,$stat) = StatusBg(1,($mon[3] != 'none')?1:0,$mon[7],$bi);

			TblRow($bg);
			$cmpip = 1;
			if ($mon[2] == "dev"){
				$ndev++;
				$srcip = $mon[29];
				$query	= GenQuery('links','s','neighbor,nbrifname','','',array('device'),array('='),array($mon[0]) );
				$dres	= DbQuery($query,$link);
				$neb	= array();
				if($dres){
					if ( DbNumRows($dres) ) {
						while( ($l = DbFetchRow($dres)) ){
							$neb[$l[0]] = $l[1];
						}
						DbFreeResult($dres);
					}
				}else{
					print DbError($link);
				}
				echo "<th class=\"$statbg\"><a href=\"Devices-Status.php?dev=$una\"><img src=\"img/16/dev.png\" title=\"$stat\"></a>";
			}elseif($mon[2] == "node"){
				$nnod++;
				$query	= GenQuery('nodes','s','nodip,device,ifname','','',array('name'),array('='),array($mon[0]) );
				$dres	= DbQuery($query,$link);
				$neb	= array();
				if($dres){
					$nnod = DbNumRows($dres);
					if($nnod == 1) {
						echo "<th class=\"$statbg\"><a href=\"Nodes-List.php?in[]=name&op[]=%3D&st[]=$una\"><img src=\"img/16/node.png\"  title=\"$stat\"></a>";
						$l = DbFetchRow($dres);
						$srcip = $l[0];
						$neb[$l[1]] = $l[2];
					}elseif($nnod > 1){
						$cmpip = 0;
						echo "<th class=\"warn part\"><a href=\"Nodes-List.php?in[]=name&op[]=%3D&st[]=$una\"><img src=\"img/16/nods.png\" title=\"$mullbl Nodes $namlbl!\"></a>";
					}else{
						$cmpip = 0;
						echo "<th class=\"warn part\"><a href=\"Nodes-List.php?in[]=nodip&op[]=%3D&st[]=$mon[1]\"><img src=\"img/16/bcls.png\" title=\"$nonlbl Nodes! (IP $stat)\"></a>";
					}
					DbFreeResult($dres);
				}else{
					print DbError($link);
				}
			}else{
				echo "<th class=\"txtb\"><img src=\"img/16/bbox.png\">";
			}

			$depst = "";
			$alst  = "";
			$elst  = "";

			if($upd){
				if($adp){
					if(count(array_keys($neb) ) == 1){
						$dquery	= GenQuery('monitoring','u','name','=',$mon[0],array('depend'),array(),array( key($neb) ) );
						if( !DbQuery($dquery,$link) ){
							$depst = "<img src=\"img/16/bcnl.png\" title=\"" .DbError($link)."\">";
						}else{
							$depst = "<img src=\"img/16/bchk.png\" title=\"Auto $deplbl OK\">";
							$mon[18] = key($neb);
						}
					}else{
						$depst = "<img src=\"img/16/bdis.png\" title=\"$mullbl $deplbl\">";
					}
				
				}

				if($rav){
					$uquery	= GenQuery('monitoring','u','name','=',$mon[0],array('lastok','status','lost','ok','latency','latmax','latavg'),array(),array(0,0,0,0,0,0,0) );
					if( !DbQuery($uquery,$link) ){
						$ravst = "<img src=\"img/16/bcnl.png\" title=\"" .DbError($link)."\">";
					}else{
						$ravst = "<img src=\"img/16/bchk.png\" title=\"$avalbl $reslbl OK\">";
						$mon[6] = 0;
						$mon[7] = 0;
						$mon[8] = 0;
						$mon[9] = 0;
						$mon[10] = 0;
						$mon[11] = 0;
						$mon[12] = 0;
					}
				}

				if($uip){
					list($uipst,$mon[1]) = MonUpdate($mon[0],'monip',$srcip,'IP',$mon[3]);
				}
				if($tst){
					list($testst,$mon[3]) = MonUpdate($mon[0],'test',$tst,$tstlbl,$mon[3]);
				}
				if($nrp != ''){
					list($nrpst,$mon[21]) = MonUpdate($mon[0],'noreply',$nrp,"$nonlbl $rpylbl",$mon[21]);
				}
				if($al){
					list($alst,$mon[14]) = MonUpdate($mon[0],'alert',$al-1,$mlvl['200'],$mon[14]);	# Adding 1 in the form, so it's still true with 0
				}
				if($law != ''){
					list($lawst,$mon[22]) = MonUpdate($mon[0],'latwarn',$law,"$latlbl $mlvl[200]",$mon[22]);
				}
				if($top){
					list($topst,$mon[4]) = MonUpdate($mon[0],'testopt',$top,"$tstlbl $sndlbl",$mon[4]);
				}
				if($trs){
					list($trsst,$mon[5]) = MonUpdate($mon[0],'testres',$trs,"$tstlbl $rcvlbl",$mon[5]);
				}
				if($elv){
					$myelv = ($efd == "fwd" or $elv == 1)?$elv-1:$elv;		# Adding 1 in the form, so it's still true with 0 (remove eventlevel)
					list($elst,$mon[16]) = MonUpdate($mon[0],'eventlvl',$myelv,"$levlbl $limlbl",$mon[16]);
				}
				if($inf){
					if($efd == "fwd"){
						list($infst,$mon[15]) = MonUpdate($mon[0],'eventfwd',$inf,"$fwdlbl $fltlbl",$mon[15]);
					}else{
						list($infst,$mon[17]) = MonUpdate($mon[0],'eventdel',$inf,"$dcalbl $fltlbl",$mon[17]);
					}
				}
				if($nfy != ''){
					list($nfyst,$mon[20]) = MonUpdate($mon[0],'notify',$nfy,"$notify",$mon[20]);
				}
				if($cpa != ''){
					list($cpast,$mon[23]) = MonUpdate($mon[0],'cpualert',$cpa,"CPU $mlvl[200]",$mon[23]);
				}
				if($mea != ''){
					list($meast,$mon[24]) = MonUpdate($mon[0],'memalert',$mea,"Mem $mlvl[200]",$mon[24]);
				}
				if($tea != ''){
					list($teast,$mon[25]) = MonUpdate($mon[0],'tempalert',$tea,"$tmplbl $mlvl[200]",$mon[25]);
				}
				if($pow != ''){
					list($powst,$mon[26]) = MonUpdate($mon[0],'poewarn',$pow,"PoE $mlvl[150]",$mon[26]);
				}
				if($arp != ''){
					list($arpst,$mon[27]) = MonUpdate($mon[0],'arppoison',$arp,"PoE $mlvl[150]",$mon[27]);
				}
				if($sua != ''){
					list($suast,$mon[28]) = MonUpdate($mon[0],'supplyalert',$sua,"Supply $mlvl[200]",$mon[28]);
				}
			}elseif($des and $des ==  $mon[0] and ($dps or $dpt) ){
				$dpt = ($dps)?$dps:$dpt;
				$dquery	= GenQuery('monitoring','u','name','=',$mon[0],array('depend'),array(),array($dpt) );
				if( !DbQuery($dquery,$link) ){
					$depst = "<img src=\"img/16/bcnl.png\" title=\"" .DbError($link)."\">";
				}else{
					$depst = "<img src=\"img/16/bchk.png\" title=\"$deplbl = $dpt OK\">";
					$mon[18] = $dpt;
				}
			}

			if($mon[1] != $srcip and $cmpip){
				echo "<img src=\"img/16/bdis.png\" title=\"IP $chglbl ".long2ip($mon[1])." -> ".long2ip($srcip).": $updlbl!\">";
			}

?>
<td><b><a href="?in[]=name&op[]=%3D&st[]=<?= $una ?>"><?= substr($mon[0],0,$_SESSION['lsiz']) ?></a><?= $uipst ?></b>

</td>
<td>

<?php
			if ($mon[6]){
				$lac = ($mon[10] > $latw)?'drd':'grn';
				$lmc = ($mon[11] > $latw)?'drd':'grn';
				$lvc = ($mon[12] > $latw)?'drd':'grn';
				$los = ($mon[8])?'drd':'grn';
				$las = ($mon[6] < (time() - $rrdstep) )?'drd':'grn';
				echo "$latlbl: <span class=\"$lac\" title=\"$laslbl\">$mon[10]ms </span>\n";
				echo "<span class=\"$lvc\" title=\"$avglbl\">$mon[12]ms</span>\n";
				echo "<span class=\"$lmc\" title=\"$maxlbl\">$mon[11]ms</span>\n";
				echo "<span class=\"gry\" title=\"$latlbl $mlvl[150]\">$mon[22]ms</span><br>\n";
				echo "$loslbl/OK: <span class=\"$los\">$mon[8]/$mon[9]</span><br>\n";
				echo " $laslbl: <span class=\"$las\">". date($datfmt,$mon[6]) . "</span>\n";
			}
			echo $ravst;
?>

</td>
<th>

<a href="?in[]=test&op[]=~&st[]=<?= ($mon[3])?$mon[3]:"^$" ?>"><?=TestImg($mon[3],$mon[4],$mon[5]) ?></a><?= $testst ?><?= $topst ?><?= $trsst ?> <span class="gry" title="<?= $nonlbl ?> <?= $rpylbl ?>"><?= $mon[21] ?><?= $nrpst ?></span><br>

</th>
<td>

<?php  if( isset($_GET['print']) ){ ?>
<?= $mon[18] ?>
<?php  }else{ ?>
<form method="get">
<input type="hidden" name="in[]" value="<?= $in[0] ?>">
<input type="hidden" name="op[]" value="<?= $op[0] ?>">
<input type="hidden" name="st[]" value="<?= $st[0] ?>">
<input type="hidden" name="des" value="<?= $mon[0] ?>">
<input type="text" name="dpt" size="12" value="<?= $mon[18] ?>" onfocus="select();" onchange="this.form.submit();" title="<?= $wrtlbl ?> <?= $namlbl ?>">
<select size="1" name="dps" onchange="this.form.submit();" title="<?= $namlbl ?>">
<option value=""><?= $sellbl ?>
<option value="-">(<?= $nonlbl ?>)
<?php
			if($neb){
				foreach ($neb as $nen => $nif){
					echo "<option value=\"$nen\">".substr($nen,0,$_SESSION['lsiz'])."\n";
				}
			}
?>
</select> <?= $depst ?>
</form>
<?php } ?>

</td>
<th>

<a href="?in[]=alert&op[]==&st[]=<?= $mon[14] ?>">
<?php
if($mon[14] & 128){
	echo "<img src=\"img/16/brld.png\" title=\"Mail $rptlbl\">";
}elseif($mon[14] & 2){
	echo "<img src=\"img/16/mail.png\" title=\"Mail\">";
}elseif($mon[14] & 1){
	echo "<img src=\"img/16/bell.png\" title=\"$msglbl\">";
}else{
	echo "<img src=\"img/16/bcls.png\" title=\"$nonlbl Mail\">";
}
if($mon[14] & 4){
	echo "<img src=\"img/16/sms.png\" title=\"SMS\">";
}else{
	echo "<img src=\"img/16/bcls.png\" title=\"$nonlbl SMS\">";
}
?>
</a>
<?= $alst ?>

</th>
<td>

<?php
if($mon[15] or $mon[16] and !($mon[16]%2) ){
?>
<img src="img/16/mail.png" title="<?= $fwdlbl ?>">
<?php
	if($mon[16] and !($mon[16]%2) ){
?>
<a href="?in[]=eventlvl&op[]==&st[]=<?= $mon[16] ?>"><img src="img/16/<?= $mico[$mon[16]] ?>.png" title="<?= $mlvl[$mon[16]] ?>"></a>
<?	}
	if($mon[15]){
?>
<a href="?in[]=eventfwd&op[]==&st[]=<?= $mon[15] ?>"><?= $mon[15] ?></a>
<?
	}
}

if($mon[16]%2 or $mon[17]){
?>
<br><img src="img/16/bdis.png" title="<?= $dcalbl ?>">
<?php
	if($mon[16]%2){
?>
<a href="?in[]=eventlvl&op[]==&st[]=<?= $mon[16] ?>"><img src="img/16/<?= $mico[$mon[16]-1] ?>.png" title="<?= $mlvl[$mon[16]-1] ?>"></a>
<?	}
	if($mon[17]){
?>
<a href="?in[]=eventdel&op[]==&st[]=<?= $mon[17] ?>"><?= $mon[17] ?></a> 
<?	}
}
?>
<?= $infst ?><?= $elst ?>
</td>
<td>
<span class="gry" title="CPU <?= $mlvl[200]?>"><?= $mon[23] ?>%</span><?= $cpast ?> 
<span class="gry" title="Mem <?= $mlvl[200]?>"><?= $mon[24] ?><?= ($mon[24] < 101)?'%':'kB' ?></span><?= $meast ?> 
<span class="gry" title="<?= $tmplbl ?> <?= $mlvl[200]?>"><?= $mon[25] ?>C</span><?= $teast ?> 
<span class="gry" title="PoE <?= $mlvl[150]?>"><?= $mon[26] ?>%</span><?= $powst ?> 
<span class="gry" title="ARPposon"><?= $mon[27] ?></span><?= $arpst ?> 
<span class="gry" title="Supply <?= $mlvl[200] ?>"><?= $mon[28] ?></span><?= $suast ?> 
<br>
<span class="gry" title="notify"><?= $mon[20] ?></span> <?= $nfyst ?>
</td>
</tr>

<?php
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $nnod ?> Nodes, <?= $ndev ?> Devices <?= $totlbl ?></td></tr>
</table>
<?php
}
include_once ("inc/footer.php");
?>
