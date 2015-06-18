<?php
# Program: Topology-Links.php
# Programmer: Remo Rickli (based on suggestion of richard.lajaunie)

$printable = 1;
$exportxls = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");

$_GET = sanitize($_GET);
$st = isset($_GET['st']) ? $_GET['st'] : "";
$in = isset($_GET['in']) ? $_GET['in'] : "";
$op = isset($_GET['op']) ? $_GET['op'] : "";
$co = isset($_GET['co']) ? $_GET['co'] : "";

$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
if($_SESSION['opt'] and !$ord and $in[0]) $ord = $in[0];

$map = isset($_GET['map']) ? "checked" : "";
$lim = isset($_GET['lim']) ? preg_replace('/\D+/','',$_GET['lim']) : $listlim;

if( isset($_GET['col']) ){
	$col = $_GET['col'];
	if($_SESSION['opt']) $_SESSION['lnkcol'] = $col;
}elseif( isset($_SESSION['lnkcol']) ){
	$col = $_SESSION['lnkcol'];
}else{
	$col = array('device','ifname','neighbor','nbrifname','linktype','linkdesc');
}

$cols = array(	"id"=>"ID",
		"device"=>"Device $namlbl",
		"ifname"=>"IF $namlbl",
		"type"=>"Device $typlbl",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"devgroup"=>$grplbl,
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl",
		"neighbor"=>"$neblbl",
		"nbrifname"=>"$neblbl IF",
		"bandwidth"=>"$bwdlbl",
		"linktype"=>"$typlbl",
		"linkdesc"=>"$deslbl",
		"nbrduplex"=>"$neblbl Duplex",
		"nbrvlanid"=>"$neblbl Vlan",
		"time"=>$timlbl
		);

$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);							# Above print-header!
?>
<h1>Link <?= $lstlbl ?></h1>

<?php  if( !isset($_GET['print']) and !isset($_GET['xls']) ) { ?>

<form method="get" name="list" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>

<td>
<?PHP Filters(); ?>

</td>
<th valign="top">

<h3><?= $fltlbl ?></h3>
<a href="?in[]=device&op[]=~&st[]=&co[]=%3D&in[]=neighbor"><img src="img/16/brld.png" title="Loops"></a>
<a href="?in[]=time&op[]=<&st[]=<?= time()-2*$rrdstep ?>&ord=time+desc"><img src="img/16/date.png" title="<?= $stco['160'] ?> <?= $cnclbl ?>"></a>

</th>
<th>

<select multiple name="col[]" size="6">
<?php
foreach ($cols as $k => $v){
       echo "<option value=\"$k\"".((in_array($k,$col))?" selected":"").">$v\n";
}
?>
</select>

</th>
<th valign="top">

<?= $optlbl ?><p>
<div align="left">
<img src="img/16/paint.png" title="<?= (($verb1)?"$sholbl $laslbl Map":"Map $laslbl $sholbl") ?>"> 
<input type="checkbox" name="map" <?= $map ?>><br>
<img src="img/16/form.png" title="<?= $limlbl ?>"> 
<select size="1" name="lim">
<?php selectbox("limit",$lim) ?>
</div>

</th>
<th width="80">

<input type="submit" value="<?= $sholbl ?>">
</th>
</tr></table></form><p>
<?php
}
if( is_array($in) ){
	if ($map and !isset($_GET['xls']) and file_exists("map/map_$_SESSION[user].php")) {
		echo "<center><h2>$netlbl Map</h2>\n";
		echo "<img src=\"map/map_$_SESSION[user].php\" style=\"border:1px solid black\"></center><p>\n";
	}
	Condition($in,$op,$st,$co);
	TblHead("$modgroup[$self]2",1);
	$query	= GenQuery('links','s','links.*,type,firstdis,lastdis,location,contact,devgroup',$ord,$lim,$in,$op,$st,$co,'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($l = DbFetchRow($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			$ud = urlencode($l[1]);
			$un = urlencode($l[3]);
			list($fc,$lc) = Agecol($l[12],$l[13],$row % 2);
			list($tc,$tc) = Agecol($l[10],$l[10],$row % 2);

			TblRow($bg);
			if(in_array("id",$col)){
				TblCell($l[0]);
			}
			if( in_array("device",$col) ){
				TblCell($l[1],"?in[]=device&op[]==&st[]=$ud&ord=ifname","nowrap","<a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/16/sys.png\"></a><a href=\"Topology-Linked.php?dv=$ud\"><img src=\"img/16/ncfg.png\"></a>");
			}
			if(in_array("ifname",$col)){
				TblCell($l[2]);
			}
			if(in_array("type",$col)){
				TblCell( $l[11],"?in[]=type&op[]==&st[]=".urlencode($l[11]) );
			}
			if(in_array("location",$col)){
				TblCell( $l[14],"?in[]=location&op[]==&st[]=".urlencode($l[14]) );
			}
			if(in_array("contact",$col)){
				TblCell( $l[15],"?in[]=contact&op[]==&st[]=".urlencode($l[15]) );
			}
			if(in_array("devgroup",$col)){
				TblCell( $l[16],"?in[]=contact&op[]==&st[]=".urlencode($l[16]) );
			}
			if( in_array("firstdis",$col) ){
				TblCell( date($datfmt,$l[12]),"?in[]=firstdis&op[]==&st[]=$l[12]","bgcolor=\"#$fc\"" );
			}
			if( in_array("lastdis",$col) ){
				TblCell( date($datfmt,$l[13]),"?in[]=lastdis&op[]==&st[]=$l[13]","bgcolor=\"#$lc\"" );
			}
			if( in_array("neighbor",$col) ){
				TblCell($l[3],"?in[]=device&op[]==&st[]=$un&ord=ifname","nowrap","<a href=\"Devices-Status.php?dev=$un\"><img src=\"img/16/sys.png\"></a><a href=\"Topology-Linked.php?dv=$un\"><img src=\"img/16/ncfg.png\"></a>");
			}
			if(in_array("nbrifname",$col)){
				TblCell($l[4]);
			}
			if(in_array("bandwidth",$col)){
				TblCell( DecFix($l[5]) );
			}
			if(in_array("linktype",$col)){
				TblCell( $l[6],"?in[]=linktype&op[]==&st[]=$l[6]");
			}
			if(in_array("linkdesc",$col)){
				TblCell($l[7]);
			}
			if(in_array("nbrduplex",$col)){
				TblCell($l[8]);
			}
			if(in_array("nbrvlanid",$col)){
				TblCell($l[9]);
			}
			if(in_array("time",$col)){
				TblCell( date($datfmt,$l[10]),"?in[]=time&op[]==&st[]=$l[10]","bgcolor=\"#$tc\"" );
			}
			echo "</tr>\n";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
	?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Links<?= ($ord)?", $srtlbl: $ord":"" ?><?= ($lim)?", $limlbl: $lim":"" ?></td></tr>
</table>
	<?php
}
include_once ("inc/footer.php");
?>
