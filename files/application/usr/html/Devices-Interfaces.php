<?php
# Program: Devices-Interfaces.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
if($_SESSION['opt'] and !$ord and $in[0]) $ord = $in[0];

$map = isset($_GET['map']) ? "checked" : "";
$lim = isset($_GET['lim']) ? preg_replace('/\D+/','',$_GET['lim']) : $listlim;

$tal = ($_GET['tal']) ? $_GET['tal'] : 0;								# Set to 0 if empty
$bal = ($_GET['bal']) ? $_GET['bal'] : 0;
$maf = ($_GET['maf']) ? $_GET['maf'] : 0;

if( isset($_GET['col']) ){
	$col = $_GET['col'];
	if($_SESSION['opt']) $_SESSION['intcol'] = $col;
}elseif( isset($_SESSION['intcol']) ){
	$col = $_SESSION['intcol'];
}else{
	$col = array('imBL','ifname','device','ifdesc','alias','comment');
}

$cols = array(	"imBL"=>$imglbl,
		"ifname"=>"IF $namlbl",
		"ifidx"=>"IF $idxlbl",
		"device"=>"Device $namlbl",
		"type"=>"Device $typlbl",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl",
		"linktype"=>"Link $typlbl",
		"iftype"=>"IF $typlbl",
		"ifmac"=>"MAC $adrlbl",
		"ifdesc"=>$deslbl,
		"alias"=>"Alias",
		"ifstat"=>$stalbl,
		"lastchg"=>"$laslbl $chglbl",
		"speed"=>$spdlbl,
		"duplex"=>"Duplex",
		"pvid"=>"Port Vlan $idxlbl",
		"inoct"=>"$totlbl $trflbl ".substr($inblbl,0,3),
		"outoct"=>"$totlbl $trflbl ".substr($oublbl,0,3),
		"inerr"=>"$totlbl $errlbl ".substr($inblbl,0,3),
		"outerr"=>"$totlbl $errlbl ".substr($oublbl,0,3),
		"indis"=>"$totlbl Discards ".substr($inblbl,0,3),
		"outdis"=>"$totlbl Discards ".substr($oublbl,0,3),
		"inbrc"=>"$totlbl Broadcasts ".substr($inblbl,0,3),
		"dinoct"=>"$laslbl $trflbl ".substr($inblbl,0,3),
		"doutoct"=>"$laslbl $trflbl ".substr($oublbl,0,3),
		"dinerr"=>"$laslbl $errlbl ".substr($inblbl,0,3),
		"douterr"=>"$laslbl $errlbl ".substr($oublbl,0,3),
		"dindis"=>"$laslbl Discards ".substr($inblbl,0,3),
		"doutdis"=>"$laslbl Discards ".substr($oublbl,0,3),
		"dinbrc"=>"$laslbl Broadcasts ".substr($inblbl,0,3),
		"poe"=>'PoE',
		"comment"=>$cmtlbl,
		"trafalert"=>"$trflbl $mlvl[200]",
		"bcastalert"=>"Bcast $mlvl[200]",
		"macflood"=>"MAC Flood",
		"poNS"=>$poplbl,
		"gfNS"=>"IF $gralbl",
		"rdrNS"=>"Radar $gralbl"
		);

$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);							# Above print-header!
?>
<script src="inc/Chart.min.js"></script>

<h1>Interface <?= $lstlbl ?></h1>

<?php  if( !isset($_GET['print']) and !isset($_GET['xls']) ) { ?>

<form method="get" name="list" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td>

<?PHP Filters(); ?>

</td>
<th>
	
<select multiple name="col[]" size="6" title="<?= $collbl ?>">
<?php
foreach ($cols as $k => $v){
       echo "<option value=\"$k\" ".((in_array($k,$col))?" selected":"").">$v\n";
}
?>
</select>

</th>
</th>
<td>

<img src="img/16/paint.png" title="<?= (($verb1)?"$sholbl $laslbl Map":"Map $laslbl $sholbl") ?>"> 
<input type="checkbox" name="map" <?= $map ?>><br>
<img src="img/16/form.png" title="<?= $limlbl ?>"> 
<select size="1" name="lim">
<?php selectbox("limit",$lim) ?>
</select>
<p>
<img src="img/16/bino.png" title="<?= $vallbl ?> <?= $mlvl['200'] ?>">
<input type="text" name="tal" size="2" title="<?= $trflbl ?> %">
<input type="text" name="bal" size="3" title="<?= $inblbl ?> Broadcast/s">
<input type="text" name="maf" size="3" title="# MACs">

</td>
<th width="80">

<input type="submit" value="<?= $sholbl ?>">
<p>
<input type="submit" name="trk" value="Track">
<p>
<input type="submit" name="upm" value="<?= $updlbl ?>">
</th>
</tr></table></form><p>
<?php
}
if( count($in) ){
	if ($map and !isset($_GET['xls']) and file_exists("map/map_$_SESSION[user].php")) {
		echo "<center><h2>$netlbl Map</h2>\n";
		echo "<img src=\"map/map_$_SESSION[user].php\" style=\"border:1px solid black\"></center><p>\n";
	}
	Condition($in,$op,$st,$co);
	TblHead("$modgroup[$self]2",1);
	$query	= GenQuery('interfaces','s','interfaces.*,type,firstdis,lastdis,location,contact',$ord,$lim,$in,$op,$st,$co,'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$row = 0;
		$trkst = '';
		$monst = '';
		while( ($if = DbFetchRow($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			$ud = urlencode($if[0]);
			$ui = urlencode($if[1]);
			list($fc,$lc) = Agecol($if[33],$if[34],$row % 2);
			list($cc,$cc) = Agecol($if[26],$if[26],$row % 2);

			if($isadmin and $_GET['trk']){
				$trkst = AddRecord('nodetrack',"device='$if[0]' AND ifname='$if[1]'","device,ifname,value,source,usrname,time","'$if[0]','$if[1]','-','-','$_SESSION[user]','".time()."'");
			}
			if($isadmin and $_GET['upm']){
				$query	= GenQuery('interfaces','u',"CONCAT(device,ifname)",'=',"$if[0]$if[1]",array('trafalert','brcalert','macflood'),array(),array($tal,$bal,$maf) );
				$monst = DbQuery($query,$link)?"<img src=\"img/16/bchk.png\" title=\" $monlbl $updlbl OK\" vspace=\"4\">":"<img src=\"img/16/bcnl.png\" title=\"".DbError($link)."\" vspace=\"4\">";
			}

			TblRow($bg);
			if(in_array("imBL",$col)){
				list($ifbg,$ifst)	= Ifdbstat($if[8]);
				list($ifimg,$iftyp)	= Iftype($if[4]);
				TblCell("","","width=\"50\" class=\"".(($ifbg)?$ifbg:$bi)."\"","<img src=\"img/$ifimg\" title=\"$iftyp - $ifst\">","th-img");
			}

			if( in_array("ifname",$col) ){
				TblCell("$if[1] $trkst $monst","?in[]=ifname&op[]==&st[]=$ui","align=\"left\"","","th");
			}
			if( in_array("ifidx",$col) ){TblCell($if[2],"?in[]=ifidx&op[]==&st[]=$if[2]","align=\"right\"");}

			if( in_array("device",$col) ){
				TblCell($if[0],"?in[]=device&op[]==&st[]=$ud&ord=ifname","nowrap","<a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/16/sys.png\"></a>");
			}
			if( in_array("type",$col) ){TblCell($if[32],"?in[]=type&op[]==&st[]=$if[32]");}
			if( in_array("location",$col) ){TblCell( $if[35],"?in[]=location&op[]==&st[]=".urlencode($if[35]) );}
			if( in_array("contact",$col) ){TblCell( $if[36],"?in[]=contact&op[]==&st[]=".urlencode($if[36]) );}
			if( in_array("firstdis",$col) ){
				TblCell( date($datfmt,$if[33]),"?in[]=firstdis&op[]==&st[]=$if[33]","bgcolor=\"#$fc\"" );
			}
			if( in_array("lastdis",$col) ){
				TblCell( date($datfmt,$if[34]),"?in[]=lastdis&op[]==&st[]=$if[34]","bgcolor=\"#$lc\"" );
			}
			if( in_array("linktype",$col) ){TblCell($if[3],"?in[]=linktype&op[]==&st[]=$if[3]");}
			if( in_array("iftype",$col) ){TblCell($if[4],"?in[]=iftype&op[]==&st[]=$if[4]");}
			if( in_array("ifmac",$col) ){TblCell($if[5],"","class=\"mrn code\"");}
			if( in_array("ifdesc",$col) ){TblCell($if[6]);}
			if( in_array("alias",$col) ){TblCell($if[7]);}
			if( in_array("ifstat",$col) ){TblCell($if[8],"","align=\"right\"");}
			if( in_array("lastchg",$col) ){TblCell(date($datfmt,$if[26]),"?in[]=lastchg&op[]==&st[]=$if[26]","bgcolor=\"#$cc\"");}
			if( in_array("speed",$col) ){TblCell( DecFix($if[9]),"","align=\"right\"" );}
			if( in_array("duplex",$col) ){TblCell($if[10]);}
			if( in_array("pvid",$col) ){TblCell($if[11],"","align=\"right\"");}

			if( in_array("inoct",$col) ){TblCell( DecFix($if[12])."B","","align=\"right\"" );}
			if( in_array("outoct",$col) ){TblCell( DecFix($if[14])."B","","align=\"right\"" );}
			if( in_array("inerr",$col) ){TblCell( DecFix($if[13]),"","align=\"right\"" );}
			if( in_array("outerr",$col) ){TblCell( DecFix($if[15]),"","align=\"right\"" );}
			if( in_array("indis",$col) ){TblCell( DecFix($if[20]),"","align=\"right\"" );}
			if( in_array("outdis",$col) ){TblCell( DecFix($if[21]),"","align=\"right\"" );}
			if( in_array("inbrc",$col) ){TblCell( DecFix($if[24]),"","align=\"right\"" );}

			if( in_array("dinoct",$col) ){TblCell( DecFix($if[16])."B","","align=\"right\"" );}
			if( in_array("doutoct",$col) ){TblCell( DecFix($if[18])."B","","align=\"right\"" );}
			if( in_array("dinerr",$col) ){TblCell( DecFix($if[17]),"","align=\"right\"" );}
			if( in_array("douterr",$col) ){TblCell( DecFix($if[19]),"","align=\"right\"" );}
			if( in_array("dindis",$col) ){TblCell( DecFix($if[22]),"","align=\"right\"" );}
			if( in_array("doutdis",$col) ){TblCell( DecFix($if[23]),"","align=\"right\"" );}
			if( in_array("dinbrc",$col) ){TblCell( DecFix($if[25]),"","align=\"right\"" );}

			if( in_array("poe",$col) ){TblCell($if[27]."mW","?in[]=poe&op[]==&st[]=$if[27]","align=\"right\"");}
			if( in_array("comment",$col) ){TblCell($if[28]);}
			if( in_array("trafalert",$col) ){TblCell($if[29].'%');}
			if( in_array("bcastalert",$col) ){TblCell($if[30].'/s');}
			if( in_array("macflood",$col) ){TblCell($if[31]);}
			if( in_array("poNS",$col) and !isset($_GET['xls']) ){
				$pop = NodPop(array('device','ifname'),array('=','='),array($if[0],$if[1]),array('AND'));
				if ($pop){
					echo "<td nowrap>".Bar($pop,24,'mi')." <a href=Nodes-List.php?in[]=device&op[]==&st[]=$ud&in[]=ifname&op[]==&st[]=$if[1]&co[]=AND\">$pop</td>";
				}else{
					TblCell();
				}
				DbFreeResult($np);
			}
			if( in_array("gfNS",$col) and !isset($_GET['xls']) ){
				echo "<td nowrap align=\"center\">\n";
				IfGraphs($ud,$ui,$if[9], $_SESSION['gsiz']);
				echo "</td>\n";
			}
			if( in_array("rdrNS",$col) and !isset($_GET['xls']) ){
				echo "<td nowrap align=\"center\">\n";
				IfRadar("rt$row",$_SESSION['gsiz'],'248',$if[12],$if[14],$if[13],$if[15],$if[20],$if[21],$if[24]);
				IfRadar("rl$row",$_SESSION['gsiz'],'284',$if[16],$if[18],$if[17],$if[19],$if[22],$if[23],$if[25]);
				echo "</td>\n";
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
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Interfaces<?= ($ord)?", $srtlbl: $ord":"" ?><?= ($lim)?", $limlbl: $lim":"" ?></td></tr>
</table>
	<?php
}
include_once ("inc/footer.php");
?>
