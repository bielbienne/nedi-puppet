<?php
# Program: Monitoring-Health.php
# Programmer: Remo Rickli

$refresh   = 60;
$printable = 0;
$firstmsg  = time() - 86400;

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");

$_GET = sanitize($_GET);
$reg   = isset($_GET['reg']) ? $_GET['reg'] : '';
$cty   = isset($_GET['cty']) ? $_GET['cty'] : '';
$bld   = isset($_GET['bld']) ? $_GET['bld'] : '';

$alarr = array();
$loc   = TopoLoc($reg,$cty,$bld);
$evloc = ($loc)?"&co[]=AND&in[]=location&op[]=like&st[]=".urlencode($loc):'';
$rploc = ($loc)?"&in[]=location&op[]=like&st[]=".urlencode($loc):'';

?>
<h1>Monitoring Health</h1>
<form method="get" name="dynfrm" action="<?= $self ?>.php">
<input type="hidden" name="reg" value="<?= $reg ?>">
<input type="hidden" name="cty" value="<?= $cty ?>">
<input type="hidden" name="bld" value="<?= $bld ?>">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top" align="center">
<h3>
<a href="Reports-Monitoring.php?rep[]=mav<?= $rploc ?>"><img src="img/16/dbin.png" title="<?= $avalbl ?> <?= $stslbl ?>"></a>
<a href="Monitoring-Timeline.php?det=level&bsz=si<?= $rploc ?>"><img src="img/16/news.png" title="<?= $msglbl ?> <?= $hislbl ?>"></a> <?= $stalbl ?>
</h3><p>
<?php

$link  = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
list($nmon,$lastok,$talrm) = TopoMon($loc);

StatusMon($nmon,$lastok,$talrm,$_SESSION['gsiz']);

if(!$_SESSION['gsiz']){StatusIncidents($loc);}

StatusSlow($slow);
?>
</td>

<td valign="top" align="center">
<h3>
<a href="Reports-Interfaces.php?rep[]=trf<?= $rploc ?>"><img src="img/16/bbup.png" title="<?= $trflbl ?> <?= $stslbl ?>"></a>
<a href="Reports-Combination.php?rep=poe<?= $rploc ?>"><img src="img/16/batt.png" title="PoE <?= $stslbl ?>"></a>
<?= $lodlbl ?></h3><p>
<?php
if($_SESSION['gsiz']){
?>
<a href="Devices-Graph.php?dv=Totals&if[]=ttr&sho=1"><img src="inc/drawrrd.php?t=ttr&s=<?= $_SESSION['gsiz'] ?>" title="<?= $totlbl ?> <?= $acslbl ?> <?= $trflbl ?>"></a>
<a href="Devices-Graph.php?dv=Totals&if[]=tpw&sho=1"><img src="inc/drawrrd.php?t=tpw&s=<?= $_SESSION['gsiz'] ?>" title="<?= $totlbl ?> PoE <?= $lodlbl ?>"></a>
<?php
}

StatusIf($loc,'bbup');
StatusIf($loc,'bbdn');

if(!$_SESSION['gsiz']){
		$query	= GenQuery('interfaces','s','count(*),round(sum(poe)/1000)','','',array('poe','location'),array('>','like'),array('0',$loc),array('AND'),'JOIN devices USING (device)');
		$res	= DbQuery($query,$link);
		if($res){
			$m = DbFetchRow($res);
			if($m[0]){echo "<p><b><img src=\"img/32/batt.png\" title=\"$m[0] PoE IF\">$m[1] W</b>\n";}
			DbFreeResult($res);
		}else{
			print DbError($link);
		}
}

?>
</td>

<td valign="top" align="center">
<h3>
<a href="Reports-Interfaces.php?rep[]=err<?= $rploc ?>"><img src="img/16/brup.png" title="<?= $errlbl ?> <?= $stslbl ?>"></a>
<a href="Reports-Interfaces.php?rep[]=dis<?= $rploc ?>"><img src="img/16/bdis.png" title="<?= $dsalbl ?> IF <?= $tim['t'] ?>"></a>
<?= $errlbl ?></h3><p>
<?php
if($_SESSION['gsiz']){
?>
<a href="Devices-Graph.php?dv=Totals&if[]=ter&sho=1"><img src="inc/drawrrd.php?t=ter&s=<?= $_SESSION['gsiz'] ?>" title="<?= $totlbl ?> non-Wlan <?= $errlbl ?>"></a>
<a href="Devices-Graph.php?dv=Totals&if[]=ifs&sho=1"><img src="inc/drawrrd.php?t=ifs&s=<?= $_SESSION['gsiz'] ?>" title="IF <?= $stalbl ?> <?= $sumlbl ?>"></a>
<?php
}
StatusIf($loc,'brup');
StatusIf($loc,'brdn');
StatusIf($loc,'bdis');
?>
</td>

<td valign="top" align="center" width="200">
<h3>
<img src="img/16/exit.png" title="Stop" onClick="stop_countdown(interval);">
<span id="counter"><?= $refresh ?></span>
</h3>
<?php
StatusCpu($loc);
StatusMem($loc);
StatusTmp($loc);

if($_SESSION['gsiz']){StatusIncidents($loc);}

?>
</td></tr></table>
</form>
<p>
<?php
if($_SESSION['lim']){
?>

<h2><?= $msglbl ?> <?= $tim['t'] ?></h2>

<table class="full"><tr>
<td  width="13%" class="helper">

<h3><?= $levlbl ?></h3>
<?php
	$query	= GenQuery('events','g','level','level desc',$_SESSION['lim'],array('time','location'),array('>','like'),array($firstmsg,$loc),array('AND'),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nlev = DbNumRows($res);
		if($nlev){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th width="40"><img src="img/16/idea.png"><br><?= $levlbl ?></th>
<th><img src="img/16/bell.png"><br><?= $msglbl ?></th>
<?php
			$row = 0;
			while( ($m = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$mbar = Bar($m[1],0,'si');
				echo "<tr class=\"$bg\"><th class=\"".$mbak[$m[0]]."\">\n";
				echo "<img src=\"img/16/".$mico[$m[0]].".png\" title=\"".$mlvl[$m[0]]."\"></th><td nowrap>$mbar <a href=\"Monitoring-Events.php?in[]=level&op[]==&st[]=$m[0]$evloc\">$m[1]</a></td></tr>\n";
			}
			echo "</table>\n";
		}else{
			echo "<p><h5>$nonlbl</h5>";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
?>

</td>
<td  width="13%" class="helper">

<h3><?= $clalbl ?></h3>
<?php
	$query	= GenQuery('events','g','class','cnt desc',$_SESSION['lim'],array('time','location'),array('>','like'),array($firstmsg,$loc),array('AND'),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nlev = DbNumRows($res);
		if($nlev){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th width="40"><img src="img/16/abc.png"><br><?= $clalbl ?></th>
<th><img src="img/16/bell.png"><br><?= $msglbl ?></th>
<?php
			$row = 0;
			while( ($m = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				list($ei,$et)   = EvClass($m[0]);
				$mbar = Bar($m[1],"lvl$m[0]",'si');
				echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
				echo "<img src=\"img/16/$ei.png\" title=\"$et\"></th><td nowrap>$mbar <a href=\"Monitoring-Events.php?in[]=class&op[]==&st[]=$m[0]$evloc\">$m[1]</a></td></tr>\n";
			}
			echo "</table>\n";
		}else{
			echo "<p><h5>$nonlbl</h5>";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
?>

</td>
<td  width="13%" class="helper">

<h3><?= $srclbl ?></h3>
<?php
	$query	= GenQuery('events','g','source','cnt desc',$_SESSION['lim'],array('time','location'),array('>','like'),array($firstmsg,$loc),array('AND'),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nlev = DbNumRows($res);
		if($nlev){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/say.png"><br><?= $srclbl ?></th>
<th><img src="img/16/bell.png"><br><?= $msglbl ?></th>
<?php
			$row = 0;
			while( ($r = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$s    = substr($r[0],0,$_SESSION['lsiz']);		# Shorten sources
				$mbar = Bar($r[1],0,'si');
				echo "<tr class=\"$bg\"><th class=\"$bi\" align=\"left\" title=\"$r[0]\">$s</th>\n";
				echo "<td nowrap>$mbar <a href=\"Monitoring-Events.php?in[]=source&op[]==&st[]=".urlencode($r[0])."$evloc\">$r[1]</a></td></tr>\n";
			}
			echo "</table>\n";
		}else{
			echo "<p><h5>$nonlbl</h5>";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
?>

</td>
<td width="61%" class="helper">

<h3><?= $mlvl[200] ?> & <?= $mlvl[250] ?> <?= $lstlbl ?></h3>
<?php
	Events($_SESSION['lim'],array('level','time','location'),array('>=','>','like'),array(200,$firstmsg,$loc),array('AND','AND'),2);
	echo "</td></tr></table>";
}

if($_SESSION['col']){

	TopoTable($reg,$cty,$bld);

	if(!$reg) $leok = 1;
	if( count($dreg) == 1 ){
		$reg = array_pop ( array_keys($dreg) );
		if( count($dcity[$reg]) == 1 ){
			$cty = array_pop ( array_keys($dcity[$reg]) );
		}
	}

	if(!$reg){
		TopoRegs();
	}elseif(!$cty){
		TopoCities($reg);
	}elseif(!$bld){
		TopoBuilds($reg,$cty);
	}else{
		TopoFloors($reg,$cty,$bld);
	}
	if($leok) TopoLocErr();
}

include_once ("inc/footer.php");

?>
