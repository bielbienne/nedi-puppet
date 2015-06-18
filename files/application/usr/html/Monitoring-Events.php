<?php
# Program: Monitoring-Events.php
# Programmer: Remo Rickli

$refresh   = 60;
$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libmon.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$elm = isset($_GET['elm']) ? preg_replace('/\D+/','',$_GET['elm']) : 20;
$off = (isset($_GET['off']) and !isset($_GET['sho']))? preg_replace('/\D+/','',$_GET['off']) : 0;

$nof = $off;
if( isset($_GET['p']) ){
	$nof = abs($off - $elm);
}elseif( isset($_GET['n']) ){
	$nof = $off + $elm;
}
$dlim = ($elm)?"$elm OFFSET $nof":'';

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if( isset($_GET['del']) ){# TODO fix for Postgres
	if($isadmin){
		$query	= GenQuery('events','d','*','id desc',$elm,$in,$op,$st,$co );
		if(DbQuery($query,$link) ){
			echo "<h5> $msglbl $dellbl OK </h5>";
		}else{
			echo "<h4>".DbError($link)."</h4>";
		}
	}else{
		echo $nokmsg;
	}
}

$cols = array(	"info"=>"Info",
		"id"=>"ID",
		"level"=>"$levlbl",
		"time"=>$timlbl,
		"source"=>$srclbl,
		"class"=>$clalbl,
		"location"=>$loclbl,
		"contact"=>$conlbl
		);

?>
<h1>Monitoring <?= $msglbl ?></h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" name="dynfrm" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td>
<?PHP Filters(); ?>

</td>
<?php
if($st[0]){
	echo "<th valign=\"top\">\n\n<h3>$cmdlbl</h3>";
	echo "<a href=\"Monitoring-Timeline.php?det=level&in[]=$in[0]&op[]=$op[0]&st[]=".urlencode($st[0])."\"><img src=\"img/16/news.png\" title=\"Monitoring-Setup, $fltlbl $srclbl\"></a>\n";
	if($in[0] == 'source'){
		echo "<a href=\"Monitoring-Setup.php?in[]=name&op[]==&st[]=".urlencode($st[0])."\"><img src=\"img/16/bino.png\" title=\"Monitoring-Setup, $fltlbl $srclbl\"></a>\n";
		echo "<a href=\"Reports-Monitoring.php?rep[]=evt&in[]=name&op[]==&st[]=".urlencode($st[0])."\"><img src=\"img/16/dbin.png\" title=\"Reports-Monitoring, $fltlbl $srclbl\"></a>\n";
		echo "<a href=\"Other-Noodle.php?str=".urlencode($st[0])."\"><img src=\"img/16/find.png\" title=\"Other-Noodle, $fltlbl $srclbl\"></a>\n";
	}
	echo "</th>";
}
?>
<th valign="top">

<h3><?= $fltlbl ?></h3>
<a href="?in[]=class&op[]=like&st[]=ned%&elm=<?= $elm ?>"><img src="img/16/radr.png" title="<?= $dsclbl ?>"></a>
<a href="?in[]=class&op[]=like&st[]=sec%&elm=<?= $elm ?>"><img src="img/16/hat.png" title="Security <?= $msglbl ?>"></a>
<a href="?in[]=class&op[]=like&st[]=usr%&elm=<?= $elm ?>"><img src="img/16/user.png" title="<?= $usrlbl ?> <?= $msglbl ?>"></a>
<a href="?in[]=class&op[]=like&st[]=cfg%&elm=<?= $elm ?>"><img src="img/16/conf.png" title="<?= $cfglbl ?>"></a>
<br>
<a href="?in[]=class&op[]=like&st[]=mon%&elm=<?= $elm ?>"><img src="img/16/bino.png" title="Monitoring"></a>
<a href="?in[]=class&op[]==&st[]=trap&elm=<?= $elm ?>"><img src="img/16/warn.png" title="Traps"></a>
<a href="?in[]=class&op[]==&st[]=dev&elm=<?= $elm ?>"><img src="img/16/dev.png" title="Device <?= $msglbl ?>"></a>
<a href="?in[]=class&op[]==&st[]=node&elm=<?= $elm ?>"><img src="img/16/node.png" title="Node <?= $msglbl ?>"></a>

</th>
<td valign="top">

<h3>
<img src="img/16/exit.png" title="Stop" onClick="stop_countdown(interval);">
<span id="counter"><?= $refresh ?></span>
</h3>
<img src="img/16/form.png" title="<?= $limlbl ?>"> 
<select size="1" name="elm">
<?php selectbox("limit",$elm) ?>
</select>

</td>
<th width="80">
	
<input type="submit" name="sho" value="<?= $sholbl ?>">
<p>
<input type="hidden" name="off" value="<?= $nof ?>">
<input type="submit" name="p" value=" < ">
<input type="submit" name="n" value=" > ">
<p>

<input type="submit" name="del" value="<?= $dellbl ?>" onclick="return confirm('<?= $dellbl ?>, <?= $cfmmsg ?>')" >
</th></tr>
</table></form>
<p>
<?php
}
Condition($in,$op,$st,$co);

Events($dlim,$in,$op,$st,$co);

include_once ("inc/footer.php");
?>
