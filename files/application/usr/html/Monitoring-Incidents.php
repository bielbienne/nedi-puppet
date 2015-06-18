<?php
# Program: Monitoring-Incidents.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");

$_GET = sanitize($_GET);
$id = isset($_GET['id']) ? $_GET['id'] : "";
$dli = isset($_GET['dli']) ? $_GET['dli'] : "";
$ugr = isset($_GET['ugr']) ? $_GET['ugr'] : "";
$ucm = isset($_GET['ucm']) ? $_GET['ucm'] : "";
$cmt = isset($_GET['cmt']) ? $_GET['cmt'] : "";
$grp = isset($_GET['grp']) ? $_GET['grp'] : "";
$end = isset($_GET['end']) ? 'checked':'';
$lim = isset($_GET['lim']) ? preg_replace('/\D+/','',$_GET['lim']) : 20;
$off = (isset($_GET['off']) and !isset($_GET['sho']))? $_GET['off'] : 0;

$nof = $off;

if( isset($_GET['p']) ){
	$nof = abs($off - $lim);
}elseif( isset($_GET['n']) ){
	$nof = $off + $lim;
}
$dlim = ($lim)?"$lim OFFSET $nof":'';

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if($dli){
	$query	= GenQuery('incidents','d','','','',array('id'),array('='),array($dli) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>Incident $dli $dellbl OK</h5>";}
}elseif($ugr){
	$query	= GenQuery('incidents','u','id','=',$ugr,array('usrname','time','grp'),array(),array($_GET['usr'],$_GET['tme'],$grp) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5> Incident $ugr $updlbl OK</h5>";}
	$grp = "";
}elseif($ucm){
	$query	= GenQuery('incidents','u','id','=',$ucm,array('usrname','comment'),array(),array($_GET['usr'],$cmt) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5> Incident $ucm $updlbl OK</h5>";}
}
?>
<h1>Monitoring Incidents</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
<img src="img/16/abc.png" title="<?= $clalbl ?>"> 
<select name="grp">
<option value=""><?= $fltlbl ?> ->
<?php
foreach (array_keys($igrp) as $ig){
	echo "<option value=\"$ig\" ";
	if($ig == $grp){echo "selected ";}
	echo (strpos($ig,'0')?"style=\"color:blue\">$igrp[$ig]\n":">- $igrp[$ig]\n");
}
?>
</select>

<img src="img/16/bbrt.png" title="<?= $fltlbl ?> <?= $stco['100'] ?> (<?= $nonlbl ?> <?= $endlbl ?>)"> 
<input type="checkbox" name="end" <?= $end ?> onchange="this.form.submit();">

</th>
<th>
	
<img src="img/16/form.png" title="<?= $limlbl ?>"> 
<select name="lim">
<?php selectbox("limit",$lim) ?>
</select>
</th>
<th width="80"><input type="submit" name="sho" value="<?= $sholbl ?>">
<p>
<input type="hidden" name="off" value="<?= $nof ?>">
<input type="submit" name="p" value=" < ">
<input type="submit" name="n" value=" > ">
</th>
</tr></table></form><p>
<?}?>

<h2><?= ($grp)?$igrp[$grp]:"" ?> <?= $inclbl ?> <?= $lstlbl ?></h2>

<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th width="80" colspan="2"><img src="img/16/eyes.png"><br><?= $inclbl ?></th>
<th colspan="2"><img src="img/16/trgt.png"><br><?= $tgtlbl ?></th>
<th><img src="img/16/bblf.png"><br><?= $sttlbl ?></th>
<th><img src="img/16/bbrt.png"><br><?= $endlbl ?></th>
<th colspan="2"><img src="img/16/user.png"><br><?= $usrlbl ?></th>
<th colspan="2"><img src="img/16/find.png"><br><?= $inflbl ?></th>
</tr>

<?php
$flte = ($end)?'AND':'';
if(strpos($grp,'0') ){
	$query	= GenQuery('incidents','s','*','id desc',$dlim,array('grp','endinc'),array('~','='),array("^".substr($grp,0,1).".",0),array($flte));
}elseif($grp){
	$query	= GenQuery('incidents','s','*','id desc',$dlim,array('grp','endinc'),array('=','='),array($grp,0),array($flte));
}elseif($id){
	$query	= GenQuery('incidents','s','*','','',array('id'),array('='),array($id));
}elseif($flte){
	$query	= GenQuery('incidents','s','*','id desc',$dlim,array('endinc'),array('='),array(0));
}else{
	$query	= GenQuery('incidents','s','*','id desc',$dlim);
}
$res	= DbQuery($query,$link);
if($res){
	$nin = 0;
	$row = 0;
	while( ($i = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$fs = date("d.M H:i",$i[4]);
		if($i[5]){
			$dur = intval(($i[5] - $i[4]) / 3600);
			$ls  = date("d.M H:i",$i[5]); # . " ($dur h)";
		}else{
			$ls  = "-";
		}
		if($i[7]){$at = date("d.M H:i",$i[7]);}else{$at = "-";}
		$ud = urlencode($i[2]);
		list($fc,$lc) = Agecol($i[4],$i[5],$row % 2);
		TblRow($bg);
		echo "<th class=\"$bi\"><a href=\"?id=$i[0]\">$i[0]</a></th><th class=\"".$mbak[$i[1]]."\" width=\"40\"><img src=\"img/16/" . $mico[$i[1]] . ".png\" title=\"" . $mlvl[$i[1]] . "\"></th>\n";
		echo "<td><a href=\"Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$ud\">$i[2]</a></td><td>$i[3] deps</td>\n";
		echo "<td bgcolor=#$fc>$fs</td><td bgcolor=#$fc>$ls</td><th>$i[6]</th><td>$at</td><td>";

		if( isset($_GET['print']) ){
			echo "<img src=\"img/16/".IncImg($i[8]).".png\">".$igrp[$i[8]]."</td><td>$i[9]";
		}else{
?>
<form method="get" action="<?= $self ?>.php">
<img src="img/16/<?=IncImg($i[8]) ?>.png">
<input type="hidden" name="ugr" value="<?= $i[0] ?>">
<input type="hidden" name="usr" value="<?= ($i[6])?$i[6]:$_SESSION['user'] ?>">
<input type="hidden" name="tme" value="<?= ($i[7])?$i[7]:time() ?>">
<input type="hidden" name="lim" value="<?= $lim ?>">
<input type="hidden" name="off" value="<?= $nof ?>">
<select size="1" name="grp" onchange="this.form.submit();" title="<?= $sellbl ?> <?= $clalbl ?>">
<?php
		foreach (array_keys($igrp) as $ig){
			echo "<option value=\"$ig\" ".(strpos($ig,'0')?"style=\"color: blue\" ":"");
			if($ig == $i[8]){echo "selected ";}
			echo (strpos($ig,'0')?"style=\"color: blue\">$igrp[$ig]\n":">- $igrp[$ig]\n");
		}
?>
</select>
</td><td>
</form>
<form method="get" action="<?= $self ?>.php">
<input type="hidden" name="usr" value="<?= ($i[6])?$i[6]:$_SESSION['user'] ?>">
<input type="hidden" name="ucm" value="<?= $i[0] ?>">
<input type="hidden" name="lim" value="<?= $lim ?>">
<input type="hidden" name="off" value="<?= $nof ?>">
<input type="text" name="cmt" size="30" value="<?= $i[9] ?>" onchange="this.form.submit();">
<a href="<?= $self ?>.php?dli=<?= $i[0] ?>"><img src="img/16/bcnl.png" onclick="return confirm('<?= $dellbl ?> Incident <?= $i[0] ?>?');" title="<?= $dellbl ?> Incident"></a>
</form>
</td>
</tr>
<?php
		}
		$nin++;
		if($nin == $lim){break;}
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}
	?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Incidents</td></tr>
</table>
	<?php

include_once ("inc/footer.php");
?>
