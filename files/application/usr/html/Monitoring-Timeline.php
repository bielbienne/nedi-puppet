<?php
# Program: Monitoring-Timeline.php
# Programmer: Remo Rickli

$printable = 1;
$refresh   = 600;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libmon.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$gra = isset($_GET['gra']) ? $_GET['gra'] : 3600;
$det = isset($_GET['det']) ? $_GET['det'] : "";
$fmt = isset($_GET['fmt']) ? $_GET['fmt'] : "si";
$sho = isset($_GET['sho']) ? 1 : 0;

$strsta = isset($_GET['sta']) ? $_GET['sta'] : date("m/d/Y H:i", time() - 86400);
$strend = isset($_GET['end']) ? $_GET['end'] : date("m/d/Y H:i");
if(!$sho){# Let graph follow autoupdate
	$strsta = date("m/d/Y H:i",strtotime($strsta) + $refresh);
	$strend = date("m/d/Y H:i");
}
$sta = strtotime($strsta);
$end = strtotime($strend);
if($sta > $end){
	$sta    = $end - 100 * $rrdstep;
	$strsta = date("m/d/Y H:i",$sta);
}
$qstr = strpos($_SERVER['QUERY_STRING'], "sta")?$_SERVER['QUERY_STRING']:$_SERVER['QUERY_STRING']."&sta=".urlencode($strsta)."&end=".urlencode($strend);

$cols = array(	"info"=>"Info",
		"id"=>"ID",
		"level"=>"$levlbl",
		"source"=>$srclbl,
		"class"=>$clalbl,
		"type"=>"Device $typlbl",
		"devos"=>"Device OS",
		"bootimage"=>"Bootimage",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"devgroup"=>$grplbl,
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl"
		);

?>
<h1>Monitoring Timeline</h1>

<?php  if( !isset($_GET['print']) ){ ?>

<form method="get" name="dynfrm" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top">

<?PHP Filters(1); ?>

</td>
<td>

<img src="img/16/ugrp.png" title="<?= $grplbl ?>">
<select size="1" name="det" onchange="this.form.submit();">
<option value=""><?= $nonlbl ?>
<option value="level" <?= ($det == "level")?" selected":"" ?>><?= $levlbl ?>
<option value="source" <?= ($det == "source")?" selected":"" ?>><?= $srclbl ?>
<option value="class" <?= ($det == "class")?" selected":"" ?>><?= $clalbl ?>
</select>
<br>
<img src="img/16/clock.png" title="<?= $timlbl ?> <?= $sizlbl ?>">
<select size="1" name="gra" onchange="this.form.submit();">
<option value="3600"><?= $tim['h'] ?>
<option value="86400" <?= ($gra == "86400")?" selected":"" ?>><?= $tim['d'] ?>
<option value="604800" <?= ($gra == "604800")?" selected":"" ?>><?= $tim['w'] ?>
<option value="2592000" <?= ($gra == "2592000")?" selected":"" ?>><?= $tim['m'] ?>
</select>
<br>
<img src="img/16/form.png" title="<?= $frmlbl ?>">
<select size="1" name="fmt" onchange="this.form.submit();">
<option value="si"><?= (($verb1)?"$siz[s] $imglbl":"$imglbl $siz[s]") ?>
<option value="mi" <?= ($fmt == "mi")?" selected":"" ?>><?= (($verb1)?"$siz[m] $imglbl":"$imglbl $siz[m]") ?>
<option value="li" <?= ($fmt == "li")?" selected":"" ?>><?= (($verb1)?"$siz[l] $imglbl":"$imglbl $siz[l]") ?>
<option value="ms" <?= ($fmt == "ms")?" selected":"" ?>><?= (($verb1)?"$siz[m] $shplbl":"$shplbl $siz[m]") ?>
<option value="ls" <?= ($fmt == "ls")?" selected":"" ?>><?= (($verb1)?"$siz[l] $shplbl":"$shplbl $siz[l]") ?>
<option value="cg" <?= ($fmt == "cg")?" selected":"" ?>><?= $collbl ?> <?= $gralbl ?>
<option value="ag" <?= ($fmt == "ag")?" selected":"" ?>><?= $arclbl ?> <?= $gralbl ?>
</select>

</td>
<td align="center">

<table style="border-spacing: 0px">
<tr class="<?= $modgroup[$self] ?>2"><td>
<a href="?<?=SkewTime($qstr,"sta", -7) ?>"><img src="img/16/bbl2.png" title="<?= $sttlbl ?> -<?= $tim['w'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"sta", -1) ?>"><img src="img/16/bblf.png" title="<?= $sttlbl ?> -<?= $tim['d'] ?>"></a>
</td><td>
<input  name="sta" id="start" type="text" value="<?= $strsta ?>" onfocus="select();" size="15" title="<?= $sttlbl ?>">
</td><td>
<a href="?<?=SkewTime($qstr,"sta", 1) ?>"><img src="img/16/bbrt.png" title="<?= $sttlbl ?> +<?= $tim['d'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"sta", 7) ?>"><img src="img/16/bbr2.png" title="<?= $sttlbl ?> +<?= $tim['w'] ?>"></a>
</td></tr>
<tr class="<?= $modgroup[$self] ?>2"><td>
<a href="?<?=SkewTime($qstr,"all", -7) ?>"><img src="img/16/bbl2.png" title="<?= $gralbl ?> -<?= $tim['w'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"all", -1) ?>"><img src="img/16/bblf.png" title="<?= $gralbl ?> -<?= $tim['d'] ?>"></a>
</td><th>
<img src="img/16/date.png" title="<?= $sttlbl ?> & <?= $endlbl ?>">
</th><td>
<a href="?<?=SkewTime($qstr,"all", 1) ?>"><img src="img/16/bbrt.png" title="<?= $gralbl ?> +<?= $tim['d'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"all", 7) ?>"><img src="img/16/bbr2.png" title="<?= $gralbl ?> +<?= $tim['w'] ?>"></a>
</td></tr>
<tr class="<?= $modgroup[$self] ?>2"><td>
<a href="?<?=SkewTime($qstr,"end", -7) ?>"><img src="img/16/bbl2.png" title="<?= $endlbl ?> -<?= $tim['w'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"end", -1) ?>"><img src="img/16/bblf.png" title="<?= $endlbl ?> -<?= $tim['d'] ?>"></a>
</td><td>
<input  name="end" id="end" type="text" value="<?= $strend ?>" onfocus="select();" size="15" title="<?= $endlbl ?>">
</td><td>
<a href="?<?=SkewTime($qstr,"end", 1) ?>"><img src="img/16/bbrt.png" title="<?= $endlbl ?> +<?= $tim['d'] ?>"></a>
</td><td>
<a href="?<?=SkewTime($qstr,"end", 7) ?>"><img src="img/16/bbr2.png" title="<?= $endlbl ?> +<?= $tim['w'] ?>"></a>
</table>

<script type="text/javascript" src="inc/datepickr.js"></script>
<link rel="stylesheet" type="text/css" href="inc/datepickr.css" />
<script>

new datepickr('start', {'dateFormat': 'm/d/y'});
new datepickr('end', {'dateFormat': 'm/d/y'});
</script>

</td>
<th width="80">
<span id="counter"><?= $refresh ?></span>
<img src="img/16/exit.png" title="Stop" onClick="stop_countdown(interval);">
<p>

<input type="submit" name="sho" value="<?= $sholbl ?>">
</th>
</tr>
</table></form>
<p>
<?php
}
Condition($in,$op,$st,$co);

if( !strpos($fmt,'g') ){
?>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th width="80"><img src="img/16/clock.png"><br><?= $timlbl ?></th>
<th><img src="img/16/bell.png"><br><?= $msglbl ?></th>
</tr>
<?php
}
$istart	= $sta;
$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$tmsg = 0;
$row = 0;
while($istart < $end){
	$iend = $istart + $gra;
	if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
	$fs = urlencode(date("m/d/Y H:i:s",$istart));
	$fe = urlencode(date("m/d/Y H:i:s",$iend));
	if( $gra == "3600" ){
		$chd['labels'][] = date("H",$istart).':00';
	}elseif( $gra == "86400" ){
		$chd['labels'][] = date("D",$istart);
	}elseif( $gra == "604800" ){
		$chd['labels'][] = 'W'.date("W",$istart);
	}elseif( $gra == "2592000" ){
		$chd['labels'][] = date("M",$istart);
	}
	if( !strpos($fmt,'g') ){
		TblRow($bg);
		echo "<th class=\"$bi\" nowrap>\n";
		echo "<a href=\"Monitoring-Events.php?in[]=time&op[]=%3E=&st[]=$fs&co[]=AND&in[]=time&op[]=%3C&st[]=$fe&elm=$listlim\">".date("j.M G:i",$istart)."</a></th><td>\n";
	}
	if($det){
		# Postgres dictates that all columns must appear in the GROUP BY clause or be used in an aggregate function, so we "happily" givem that...%&/(ç(*"*&
		$query = GenQuery('events','g',($det == 'source')?'source,icon':$det,$det,'',array('time','time',$in[0]),array('>=','<',$op[0]),array($istart,$iend,$st[0]),array('AND','AND'),'LEFT JOIN devices USING (device)');
		$res   = DbQuery($query,$link);
		if($res){
			$nmsg = 0;
			while( $m = DbFetchArray($res) ){
				if($det == 'source'){
					$wid = (($fmt == 'li')?"24":"16");
					if($m['source'] == 'nedi'){
						$gico = "<img src=\"img/16/cog.png\" width=\"$wid\" title=\"$srclbl NeDi\">";
					}elseif($m['icon']){
						$gico = "<img src=\"img/dev/$m[icon].png\" width=\"$wid\" title=\"$srclbl $m[source]\">";
					}else{
						$gico = "<img src=\"img/16/say.png\" width=\"$wid\" title=\"$srclbl $m[source]\">";
					}
				}elseif($det == 'level'){
					$gico = "<img src=\"img/16/" . $mico[$m['level']] . ".png\" title=\"" . $mlvl[$m['level']] . "\">";
				}else{
					list($ei,$et) = EvClass($m['class']);
					$gico = "<img src=\"img/16/$ei.png\" title=\"$et\">";
				}
				if( !strpos($fmt,'g') ){
					echo "<a href=\"Monitoring-Events.php?in[]=time&op[]=%3E=&st[]=$fs&co[]=AND&in[]=time&op[]=%3C&st[]=$fe&in[]=$det&op[]==&st[]=".urlencode($m[$det])."&co[]=AND&elm=$listlim\">";
					if( strpos($fmt,'i') ) echo $gico;
					echo Bar($m['cnt'],($det == 'level')?"lvl$m[level]":0,$fmt,$m['cnt'])."</a>\n";
				}
				$dsico[$m[$det]] = $gico;
				$dsval[$m[$det]][$row] = $m['cnt'];
				$nmsg += $m['cnt'];
			}
			if($nmsg){
				if( !strpos($fmt,'g') ) echo "&nbsp;$nmsg $totlbl";
			}
			if( !strpos($fmt,'g') ) echo "</td></tr>\n";
			DbFreeResult($res);
		}else{
			print DbError($link);
		}
	}else{
		$query	= GenQuery('events','s','count(*)','','',array('time','time',$in[0]),array('>=','<',$op[0]),array($istart,$iend,$st[0]),array('AND','AND'),'LEFT JOIN devices USING (device)');
		$res	= DbQuery($query,$link);
		if($res){
			$m = DbFetchRow($res);
			if($m[0]){
				if( !strpos($fmt,'g') ) echo Bar($m[0],0,$fmt)." $m[0]";
			}
			$dsval[$alllbl][$row] = $m[0];
			if( !strpos($fmt,'g') ) echo " </td></tr>\n";
			$tmsg += $m[0];
			DbFreeResult($res);
		}else{
			print DbError($link);
		}
	}
	$istart = $iend;
	$row++;
	flush();
}
if( strpos($fmt,'g') ){
	$ncol = count($chd['labels']);
	ksort($dsval);
	echo "<div style=\"display: block;margin: 0 auto;width:800px;background-color:#ccc;padding:4px;border:1px solid black\">\n";
	foreach ( array_keys($dsval) as $dsgrp ){
		$cds = array();
		if($dsgrp == '50'){
			$rgba = '140,240,140';
		}elseif($dsgrp == '100'){
			$rgba = '140,140,240';
		}elseif($dsgrp == '150'){
			$rgba = '240,240,140';
		}elseif($dsgrp == '200'){
			$rgba = '240,180,100';
		}elseif($dsgrp == '250'){
			$rgba = '240,140,140';
		}elseif($dsgrp == '10'){
			$rgba = '200,200,200';
		}else{
			$rgba = (ord($dsgrp)*2-20).','.(ord( substr($dsgrp,1) )*2-20).','.(ord( substr($dsgrp,-1) )*2-20);
		}
		for($d=0;$d < $ncol;$d++){
			if( !array_key_exists($d,$dsval[$dsgrp]) ){
				$dsval[$dsgrp][$d] = 0;			
			}
		}
		echo "<span style=\"background-color:rgb($rgba);padding:5px\">\n";
		echo "<a href=\"Monitoring-Events.php?in[]=time&op[]=%3E=&st[]=$strsta&co[]=AND&in[]=time&op[]=%3C&st[]=$strend&in[]=$det&op[]==&st[]=".urlencode($dsgrp)."&co[]=AND&elm=$listlim\">";
		echo "$dsico[$dsgrp]</a></span>\n";
		ksort( $dsval[$dsgrp] );
		$cds['fillColor'] = "rgba($rgba,0.5)";
		$cds['strokeColor'] = "rgba($rgba,1)";
		$cds['data'] = array_values( $dsval[$dsgrp] );
		$chd['datasets'][] = $cds;
	}
?>
</div>
<p>
<script src="inc/Chart.min.js"></script>
<canvas id="evchart" style="display: block;margin: 0 auto;padding: 10px;border:1px solid black;background-color:#fff" width="960" height="400"></canvas>
<script language="javascript">
var data = <?= json_encode($chd,JSON_NUMERIC_CHECK) ?>

var ctx = document.getElementById("evchart").getContext("2d");
var myNewChart = new Chart(ctx).<?= ($fmt == 'cg')?'Bar':'Line' ?>(data);
</script>

<?php
	if($debug){
		echo "<div class=\"textpad code txta\">\n";
		print_r($chd);
		echo "</div>\n";
	}
}else{
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?>, <?= $tmsg ?> <?= $msglbl ?></td></tr>
</table>
<?php
}

include_once ("inc/footer.php");
?>
