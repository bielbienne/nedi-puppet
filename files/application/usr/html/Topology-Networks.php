<?php
# Program: Topology-Networks.php
# Programmer: Remo Rickli

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
	if($_SESSION['opt']) $_SESSION['netcol'] = $col;
}elseif( isset($_SESSION['netcol']) ){
	$col = $_SESSION['netcol'];
}else{
	$col = array('imBL','ifip','device','ifname','vrfname');
}

$cols = array(	"imBL"=>$imglbl,
		"ifip"=>"IP $adrlbl",
		"ifip6"=>"IPv6 $adrlbl",
		"prefix"=>"Prefix",
		"device"=>"Device $namlbl",
		"type"=>"Device $typlbl",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl",
		"ifname"=>"IF $namlbl",
		"vrfname"=>"VRF $namlbl",
		"status"=>$stalbl
		);

$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);							# Above print-header!
?>
<h1>Topology <?= $netlbl ?> <?= $lstlbl ?></h1>

<?php  if( !isset($_GET['print']) and !isset($_GET['xls']) ) { ?>

<form method="get" name="list" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>

<td>
<?PHP Filters(); ?>

</td>
<th>

<select multiple name="col[]" size=4>
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
	$query	= GenQuery('networks','s','networks.*,type,firstdis,lastdis,location,contact',$ord,$lim,$in,$op,$st,$co,'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($m = DbFetchRow($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			$ip  = ($m[2])?long2ip($m[2]):"";
			$ip6 = DbIPv6($m[3]);
			list($ntimg,$ntit) = Nettype($ip,$ip6);
			$ud  = urlencode($m[0]);
			list($fc,$lc) = Agecol($m[8],$m[9],$row % 2);
			TblRow($bg);
			if(in_array("imBL",$col)){
				TblCell("","","class=\"$bi\" width=\"50\"","<img src=\"img/$ntimg\" title=\"$ntit\">","th-img");
			}
			if(in_array("ifip",$col)){
				TblCell($ip,"?in[]=ifip&op[]==&st[]=$ip/$m[4]");
			}
			if(in_array("ifip6",$col)){
				TblCell($ip6,"","class=\"prp\"" );
			}
			if(in_array("prefix",$col)){TblCell($m[4]);}

			if( in_array("device",$col) ){
				TblCell($m[0],"?in[]=device&op[]==&st[]=$ud&ord=ifname","nowrap","<a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/16/sys.png\"></a>");
			}
			if(in_array("type",$col)){TblCell( $m[7],"?in[]=type&op[]==&st[]=".urlencode($m[7]) );}			if(in_array("location",$col)){TblCell( $m[11],"?in[]=location&op[]==&st[]=".urlencode($m[11]) );}
			if(in_array("contact",$col)){TblCell( $m[12],"?in[]=contact&op[]==&st[]=".urlencode($m[12]) );}
			if( in_array("firstdis",$col) ){
				TblCell( date($datfmt,$m[8]),"?in[]=firstdis&op[]==&st[]=$m[9]","bgcolor=\"#$fc\"" );
			}
			if( in_array("lastdis",$col) ){
				TblCell( date($datfmt,$m[9]),"?in[]=lastdis&op[]==&st[]=$m[10]","bgcolor=\"#$lc\"" );
			}
			if(in_array("ifname",$col)){TblCell( $m[1],"?in[]=ifname&op[]==&st[]=".urlencode($m[1]) );}
			if(in_array("vrfname",$col)){TblCell( $m[5],"?in[]=vrfname&op[]==&st[]=".urlencode($m[5]) );}
			if(in_array("status",$col)){TblCell( $m[6],"?in[]=status&op[]==&st[]=".urlencode($m[6]) );}
			echo "</tr>\n";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
	?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $netlbl ?><?= ($ord)?", $srtlbl: $ord":"" ?><?= ($lim)?", $limlbl: $lim":"" ?></td></tr>
</table>
	<?php
}
include_once ("inc/footer.php");
?>
