<?php
# Program: Monitoring-Health.php
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

$printable = 1;
$exportxls = 0;

$refresh   = 60;
$firstmsg  = time() - 86400;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");
include_once ("inc/librep.php");

$_GET = sanitize($_GET);
$reg = isset($_GET['reg']) ? $_GET['reg'] : "";
$cty = isset($_GET['cty']) ? $_GET['cty'] : "";
$bld = isset($_GET['bld']) ? $_GET['bld'] : "";

?>
<h1>Monitoring Master</h1>
<form method="get" name="dynfrm" action="<?= $self ?>.php">
<input type="hidden" name="reg" value="<?= $reg ?>">
<input type="hidden" name="cty" value="<?= $cty ?>">
<input type="hidden" name="bld" value="<?= $bld ?>">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a>

</th>
<td valign="top" align="center">

<h3><?= $stalbl ?></h3>
<p>
<?php
$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
list($nmon,$lastok,$monal,$deval,$slow) = TopoMon($loc);
StatusMon($nmon,$lastok,$monal,$deval);
StatusSlow($slow);
?>

</td>
<td valign="top" align="center">

<h3>Incidents <?= $notlbl ?> <?= $acklbl ?></h3>
<?php
StatusIncidents($loc,1);
?>

</td>
<th width="80">

<span id="counter"><?= $refresh ?></span>
<img src="img/16/exit.png" title="Stop" onClick="stop_countdown(interval);">

</th></tr></table>
</form>
<p>

<h2><?= $msglbl ?> <?= $tim['t'] ?></h2>

<table class="full"><tr>
<td  width="20%" class="helper">

<h3>Devices</h3>
<?php
	$query	= GenQuery('events','g','device,readcomm','cnt desc',$_SESSION['lim'],array('time','location'),array('>','~'),array($firstmsg,$loc),array('AND'),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nlev = DbNumRows($res);
		if($nlev){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/dev.png"><br>Device</th>
<th><img src="img/16/bell.png"><br><?= $msglbl ?></th>
<th><img src="img/16/cog.png"><br><?= $cmdlbl ?></th>
<?php
			$row = 0;
			while( ($r = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				$s    = substr($r[0],0,$_SESSION['lsiz']);		# Shorten labels
				$mbar = Bar($r[2],0,'si');
				echo "<tr class=\"$bg\"><th class=\"$bi\" nowrap><a href=\"Devices-Status.php?dev=".urlencode($r[0])."\">$s</th>\n";
				echo "<td nowrap>$mbar <a href=\"$r[1]://".urlencode($r[0])."/Monitoring-Events.php?in[]=level&op[]=>&st[]=150&co[]=AND&in[]=time&op[]=>&st[]=$firstmsg\">$r[2]</a></td>\n";
				echo "<th nowrap>\n";
				echo "<a href=\"$r[1]://".urlencode($r[0])."/Monitoring-Health.php\"><img src=\"img/16/hlth.png\" title=\"$r[0] Health\"></a>\n";
				echo "<a href=\"$r[1]://".urlencode($r[0])."/Monitoring-Setup.php\"><img src=\"img/16/bino.png\" title=\"$r[0] $monlbl $cfglbl\"></a>\n";
				echo "<a href=\"$r[1]://".urlencode($r[0])."/Reports-Combination.php?in[]=&op[]=~&st[]=&rep=mon\"><img src=\"img/16/chrt.png\" title=\"$r[0] $inclbl $sumlbl\"></a>\n";
				echo "<a href=\"$r[1]://".urlencode($r[0])."/Reports-Monitoring.php?rep[]=lat&rep[]=evt\"><img src=\"img/16/dbin.png\" title=\"$r[0] $monlbl $stslbl\"></a>\n";
				echo "<a href=\"$r[1]://".urlencode($r[0])."/System-Services.php\"><img src=\"img/16/cog.png\" title=\"$r[0] $srvlbl\"></a>\n";
				echo "</th></tr>\n";
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
<td width="75%" class="helper">

<h3><?= $mlvl[200] ?> & <?= $mlvl[250] ?> <?= $lstlbl ?></h3>
<?php

Events($_SESSION['lim'],array('level','time','location'),array('>=','>','~'),array(200,$firstmsg,$loc),array('AND','AND'),1);

echo "</td></tr></table>";
if($_SESSION['opt']){
	MonAvail('','','',$_SESSION['lim'],'');
}

include_once ("inc/footer.php");

?>
