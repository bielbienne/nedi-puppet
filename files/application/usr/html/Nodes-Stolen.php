<?php
# Program: Nodes-Stolen.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libnod.php");

$_GET = sanitize($_GET);
$na = isset($_GET['na']) ? $_GET['na'] : "-";
$ip = isset($_GET['ip']) ? $_GET['ip'] : "";
$stl = isset($_GET['stl']) ? strtolower(preg_replace("/[^0-9a-f]/i", "",$_GET['stl'])) : "";
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$ifn = isset($_GET['ifn']) ? $_GET['ifn'] : "";
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
$del = isset($_GET['del']) ? $_GET['del'] : "";

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);

if ($stl){
	$query	= GenQuery('stolen','i','','','',array('name','stlip','mac','device','ifname','user','time'),'',array($na,ip2long($ip),$stl,$dev,$ifn,$_SESSION['user'],time()) );
	if( !DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h4>";}else{echo "<h5>$stl $updlbl OK</h5>";}
}elseif ($del){
	$query	= GenQuery('stolen','d','','','',array('mac'),array('='),array($del) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$dellbl $del ok</h5>";}
}
?>
<h1>Stolen Nodes</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
<form method="get" action="<?= $self ?>.php">
Sort
<SELECT name="ord" size="1" onChange="submit();">
<OPTION VALUE="name" <?= ($ord == "name")?" selected":"" ?> ><?= $namlbl ?>
<OPTION VALUE="stlip" <?= ($ord == "stlip")?" selected":"" ?> >IP <?= $adrlbl ?>
<OPTION VALUE="mac" <?= ($ord == "mac")?" selected":"" ?> >MAC <?= $adrlbl ?>
<OPTION VALUE="device" <?= ($ord == "device")?" selected":"" ?> >Device
<OPTION VALUE="time" <?= ($ord == "updated")?" selected":"" ?> ><?= $timlbl ?>
</select>
</form>
</th>
<th align="right">
<form method="get" action="<?= $self ?>.php">
<?= $namlbl ?> <input type="text" name="na" value="<?= $na ?>" size="20">
IP <input type="text" name="stlip" value="<?= $ip ?>" size="15">
MAC <input type="text" name="stl" value="<?= $stl ?>" size="12">
<p>
Device <input type="text" name="dev" value="<?= $dev ?>" size="20">
IF <input type="text" name="ifn" value="<?= $ifn ?>" size="8">

</th>
<th width="80"><input type="submit" value="<?= $addlbl ?>">
</form>
</th>
</tr></table><p>
<?php
}
$query	= GenQuery('stolen','s','stolen.*',$ord,'',array(),array(),array(),array(),'LEFT JOIN devices USING (device)');
$res	= DbQuery($query,$link);
if($res){
?>
<h2>Stolen Nodes <?= $lstlbl ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="3"><img src="img/32/node.png"><br>Node <?= $inflbl ?></th>
<th colspan="2"><img src="img/32/dev.png"><br>Device - IF</th>
<th><img src="img/32/eyes.png"><br><?= $laslbl ?> / <?= $timlbl ?></th>
<th><img src="img/32/user.png"><br><?= $actlbl ?> / <?= $usrlbl ?></th>

<?php
	$row = 0;
	while( ($s = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$nquery	= GenQuery('nodes','s','*','','',array('mac'),array('='),array($s[2]));
		$nres	= DbQuery($nquery,$link);
		$nnod	= DbNumRows($nres);
		if ($nnod == 1) {
			$n	= DbFetchRow($nres);
			DbFreeResult($nres);
		}else{
			$n = array($s[0],$s[1],$s[2],'-',0,0,'Not in nodes','-');
		}
		$dbna	= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
		$dbip	= long2ip($n[1]);
		$img	= Nimg("$n[2];$n[3]");
		$ls	= date("r",$n[5]);
		list($fc,$lc) = Agecol($n[4],$n[5],$row % 2);
		$na	= preg_replace("/^(.*?)\.(.*)/","$1", $s[0]);
		$ip	= long2ip($s[1]);
		$sup	= date("r",$s[6]);
		$simg	= "";
		list($s1c,$s2c) = Agecol($s[6],$s[6],$row % 2);
		if ($n[5] > $s[6]){$simg = "<img src=\"img/16/eyes.png\" title=\"$dsclbl!\">";}

		echo "<tr class=\"$bg\">";
		echo "<th class=\"$bi\" width=120 rowspan=2><a href=\"Nodes-Status.php?mac=$n[2]\"><img src=\"img/oui/$img.png\" title=\"$n[3]\" vspace=8></a><br>$s[2]\n";
		echo "<td>$dbna</td><td>$dbip</td><td>$n[6]</td><td>$n[7]</td><td bgcolor=#$lc>$ls</td>\n";
		echo "<th>$simg <a href=\"?del=$s[2]\"><img src=\"img/16/bcnl.png\" onclick=\"return confirm('$dellbl $s[2]?')\"></a></th>\n";
		echo "</tr><tr class=\"$bg\"><td>$na</td><td>$ip</td><td>$s[3]</td><td>$s[4]</td><td bgcolor=#$s1c>$sup</td><td align=center>$s[5]</td>\n";
		echo "";
		echo "</tr>\n";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}
	?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Nodes</td></tr>
</table>
	<?php

include_once ("inc/footer.php");
?>
