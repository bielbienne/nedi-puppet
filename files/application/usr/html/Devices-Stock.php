<?php
# Program: Devices-Stock.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;
if( isset($_GET['lst']) ){$exportxls = 1;}

$cico['10']  = "star";
$cico['100'] = "flas";
$cico['150'] = "warn";
$cico['160'] = "ring";
$cico['200'] = "bstp";
$cico['250'] = "bbox";

include_once ("inc/header.php");
include_once ("inc/libdev.php");
$_GET = sanitize($_GET);
$chg = isset($_GET['chg']) ? $_GET['chg'] : "";
$add = isset($_GET['add']) ? $_GET['add'] : "";
$upd = isset($_GET['upd']) ? $_GET['upd'] : "";
$del = isset($_GET['del']) ? $_GET['del'] : "";

$lst = isset($_GET['lst']) ? $_GET['lst'] : "";
$val = isset($_GET['val']) ? $_GET['val'] : "";

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if($chg){
	$query	= GenQuery('stock','s','*','','',array('serial'),array('='),array($chg) );
	$res	= DbQuery($query,$link);
	$nitm	= DbNumRows($res);
	if ($nitm != 1) {
		echo "<h4>$chg: $nitm $vallbl!</h4>";
		DbFreeResult($res);
	}else{
		$item = DbFetchRow($res);
	}
	DbFreeResult($res);
	$sta  = $item[0];

	$sn   = $item[1];
	$ty   = $item[2];
	$as   = $item[3];
	$lo   = $item[4];

	$ps   = $item[5];
	$pc   = $item[6];
	$pn   = $item[7];
	$ti   = date("m/d/Y",$item[8]);

	$mp   = $item[9];
	$sm   = date("m/d/Y",$item[10]);
	$em   = date("m/d/Y",$item[11]);
	$lw   = date("m/d/Y",$item[12]);

	$com  = $item[13];
	$usr  = $item[14];
	$upd  = $item[15];
}else{
	$sta = isset($_GET['sta']) ? $_GET['sta'] : (($lst == 'st') ? $val : 10);

	$sn = isset($_GET['sn']) ? $_GET['sn'] : "";
	$ty = isset($_GET['ty']) ? $_GET['ty'] : (($lst == 'ty') ? $val : '');
	$as = isset($_GET['as']) ? $_GET['as'] : (($lst == 'as') ? $val : '');
	$lo = isset($_GET['lo']) ? $_GET['lo'] : (($lst == 'lo') ? $val : '');

	$ps = isset($_GET['ps']) ? $_GET['ps'] : (($lst == 'ps') ? $val : '');
	$pc = isset($_GET['pc']) ? $_GET['pc'] : (($lst == 'pc') ? $val : '');
	$pn = isset($_GET['pn']) ? $_GET['pn'] : (($lst == 'pn') ? $val : '');
	$ti = isset($_GET['ti']) ? $_GET['ti'] : (($lst == 'ti') ? $val : '');

	$mp = isset($_GET['mp']) ? $_GET['mp'] : (($lst == 'mp') ? $val : '');
	$sm = isset($_GET['sm']) ? $_GET['sm'] : (($lst == 'sm') ? $val : '');
	$em = isset($_GET['em']) ? $_GET['em'] : (($lst == 'em') ? $val : '');
	$lw = isset($_GET['lw']) ? $_GET['lw'] : (($lst == 'lw') ? $val : '');

	$com = isset($_GET['com']) ? preg_replace('/[\r\n]+/', ' ', $_GET['com']) : '';
}

echo strtotime('');
?>
<h1>Stock <?= $mgtlbl ?></h1>

<?php  if( !isset($_GET['print']) and !isset($_GET['xls']) ) { ?>

<form method="get" action="<?= $self ?>.php" name="add">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a>

</th>
<th valign="top">

<select size="4" name="sta">
<?php
foreach (array_keys($stco) as $c){
	echo "<option value=\"$c\" ".( ($c == $sta)?" selected":"").">$stco[$c]\n";
}
?>
</select>

</th>
<td valign="top">

<img src="img/16/bbox.png" title="<?= $invlbl ?>">
<input type="text" placeholder="<?= $serlbl ?>" name="sn" value="<?= $sn ?>" size="20" OnFocus="select();" <?= (($chg)?"readonly":"") ?>>
<input type="text" placeholder="<?= $typlbl ?>" name="ty" value="<?= $ty ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $numlbl ?>" name="as" value="<?= $as ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $loclbl ?>" name="lo" value="<?= $lo ?>" size="20" OnFocus="select();"><br>

<img src="img/16/cash.png" title="<?= $purlbl ?>"> 
<input type="text" placeholder="<?= $srclbl ?>" name="ps" value="<?= $ps ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $coslbl ?>" name="pc" value="<?= $pc ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $numlbl ?>" name="pn" value="<?= $pn ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $timlbl ?>" name="ti" id="ti" value="<?= $ti ?>" size="20" OnFocus="select();"><br>

<img src="img/16/dril.png" title="<?= $igrp['31'] ?>"> 
<input type="text" placeholder="<?= $igrp['17'] ?>" name="mp" value="<?= $mp ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $sttlbl ?>" name="sm" id="sm" value="<?= $sm ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $endlbl ?>" name="em" id="em" value="<?= $em ?>" size="20" OnFocus="select();">
<input type="text" placeholder="<?= $wtylbl ?>/EoL" name="lw" id="lw" value="<?= $lw ?>" size="20" OnFocus="select();"><br>

<script type="text/javascript" src="inc/datepickr.js"></script>
<link rel="stylesheet" type="text/css" href="inc/datepickr.css" />
<script>

new datepickr('ti', {'dateFormat': 'm/d/y'});
new datepickr('sm', {'dateFormat': 'm/d/y'});
new datepickr('em', {'dateFormat': 'm/d/y'});
new datepickr('lw', {'dateFormat': 'm/d/y'});
</script>
</td>
<th valign="top">
<textarea rows="3" name="com" cols="30" placeholder="<?= $cmtlbl ?>"><?= $com ?></textarea>

</th>
<th width="80">

<input type="hidden" value="<?= $lst ?>" name="lst">
<input type="hidden" value="<?= $val ?>" name="val">
<?php
if($chg){
	echo "<input type=\"submit\" value=\"$updlbl\" name=\"upd\"><p>\n";
	echo "<input type=\"submit\" value=\"$dellbl\" name=\"del\">";
}else{
	echo "<input type=\"submit\" value=\"$addlbl\" name=\"add\">";
}
?>

</th></tr>
</table></form>
<script type="text/javascript">
<?php
if($chg){
	echo "document.add.lo.focus();\n";
}else{
	echo "document.add.sn.focus();\n";
}
?>
</script>
<p>
<?php
}


$tis = strtotime($ti);
$sms = strtotime($sm);
$ems = strtotime($em);
$wint= strtotime( preg_replace("/\s.*$/", "", $lw) );							# Forget the hour on Warranty TODO fix in datepickr!

if ($add and $sn and $ty){
	$query	= GenQuery('stock','i','','','',array('serial','type','usrname','asupdate','location','state','comment','lastwty','source','asset','cost','ponumber','time','partner','startmaint','endmaint'),array(),array($sn,$ty,$_SESSION['user'],time(),$lo,$sta,$com,($wint === FALSE)?0:$wint,$ps,$as,($pc)?$pc:0,$pn,($tis === FALSE)?0:$tis,$mp,($sms === FALSE)?0:$sms,($ems === FALSE)?0:$ems) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$serlbl $sn $updlbl OK</h5>";}
}elseif ($upd and $sn and $ty and $lo){
	$query	= GenQuery('stock','u','serial','=',$sn,array('type','usrname','asupdate','location','state','comment','lastwty','source','asset','cost','ponumber','time','partner','startmaint','endmaint'),array(),array($ty,$_SESSION['user'],time(),$lo,$sta,$com,($wint === FALSE)?0:$wint,$ps,$as,($pc)?$pc:0,$pn,($tis === FALSE)?0:$tis,$mp,($sms === FALSE)?0:$sms,($ems === FALSE)?0:$ems) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$serlbl $sn $updlbl OK</h5>";}
}elseif($del ){
	$query	= GenQuery('stock','d','','','',array('serial'),array('='),array($sn) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$serlbl $del $dellbl OK</h5>";}
}

if($lst){
	if($lst == "ty"){
		echo "<h2>$typlbl \"$val\" $lstlbl</h2>\n";
		$col = "type";
	}elseif($lst == "lo"){
		echo "<h2>$loclbl \"$val\" $lstlbl</h2>\n";
		$col = "location";
	}elseif($lst == "st"){
		echo "<h2>$stalbl \"$stco[$val]\" $lstlbl</h2>\n";
		$col = "state";
	}elseif($lst == "us"){
		echo "<h2>$usrlbl \"$val\" $lstlbl</h2>\n";
		$col = "usrname";
	}elseif($lst == "ps"){
		echo "<h2>$srclbl \"$val\" $lstlbl</h2>\n";
		$col = "source";
	}elseif($lst == "mp"){
		echo "<h2>$igrp[14] \"$val\" $lstlbl</h2>\n";
		$col = "partner";
	}else{
		echo "<h2>$wtylbl \"".date($datfmt,$val)."\" $lstlbl</h2>\n";
		$col = "lastwty";
	}
?>
	<table class="content">
	<tr class="<?= $modgroup[$self] ?>2">
<?php
	TblCell($serlbl,"","colspan=\"2\"","<img src=\"img/16/key.png\"><br>","th-img");
	TblCell($typlbl,"","","<img src=\"img/16/abc.png\"><br>","th-img");
	TblCell($invlbl,"","","<img src=\"img/16/bbox.png\"><br>","th-img");
	TblCell($loclbl,"","","<img src=\"img/16/home.png\"><br>","th-img");
	TblCell($srclbl,"","","<img src=\"img/16/ugrp.png\"><br>","th-img");
	TblCell($coslbl,"","","<img src=\"img/16/cash.png\"><br>","th-img");
	TblCell($qtylbl,"","","<img src=\"img/16/form.png\"><br>","th-img");
	TblCell($purlbl,"","","<img src=\"img/16/date.png\"><br>","th-img");
	TblCell($igrp['17'],"","","<img src=\"img/16/dril.png\"><br>","th-img");
	TblCell($sttlbl,"","","<img src=\"img/16/bblf.png\"><br>","th-img");
	TblCell($endlbl,"","","<img src=\"img/16/bbrt.png\"><br>","th-img");
	TblCell($wtylbl,"","","<img src=\"img/16/bbr2.png\"><br>","th-img");
	TblCell($cmtlbl,"","","<img src=\"img/16/say.png\"><br>","th-img");
	TblCell($usrlbl,"","","<img src=\"img/16/user.png\"><br>","th-img");
	TblCell($updlbl,"","","<img src=\"img/16/clock.png\"><br>","th-img");
	echo "</tr>\n";

	$query	= GenQuery('stock','s','*','type,serial','',array("$col"),array('='),array("$val") );
	$res	= DbQuery($query,$link);
	if($res){
		$row = 0;
		$uv  = urlencode($val);
		$dats = preg_replace('/[G:i]/i','',$datfmt);
		while( ($item = DbFetchRow($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			list($a1c,$a2c) = Agecol($item[15],$item[15],$row % 2);
			TblRow($bg);
			TblCell( $item[0],'',"class=\"$bi\" width=\"50px\"","<a href=\"?chg=".urlencode($item[1])."&lst=$lst&val=$uv\"><img src=\"img/16/" . $cico[$item[0]] . ".png\" title=" . $stco[$item[0]] . "></a>","th-imx");
			TblCell( $item[1],"","class=\"mrn code\"" );
			TblCell( $item[2],"?lst=ty&val=".urlencode($item[2]) );
			TblCell( $item[3] );
			TblCell( $item[4],"?lst=lo&val=".urlencode($item[4]) );
			TblCell( $item[5],"?lst=ps&val=".urlencode($item[5]) );
			TblCell( $item[6] );
			TblCell( $item[7] );
			TblCell( ($item[8])?date($dats,$item[8]):'-' );
			TblCell( $item[9],"?lst=mp&val=".urlencode($item[9]) );
			TblCell( ($item[10])?date($dats,$item[10]):'-' );
			TblCell( ($item[11])?date($dats,$item[11]):'-' );
			TblCell( ($item[12])?date($dats,$item[12]):'-',"?lst=lw&val=".date($dats,$item[12]),"nowrap ".WtyBg($item[12]) );
			TblCell( $item[13] );
			TblCell( $item[14],"?lst=us&val=".urlencode($item[14]) );
			TblCell( date($dats,$item[15]),'',"bgcolor=\"#$a1c\"" );
			echo "</tr>\n";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
?>
	</table>
	<table class="content">
	<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
	</table>
<?php
	include_once ("inc/footer.php");
	exit;
}
?>

<table class="full fixed"><tr><td class="helper">

<h2><?= $invlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/abc.png"><br><?= $typlbl ?></th>
<th><img src="img/16/form.png"><br><?= $qtylbl ?></th>

<?php
$query	= GenQuery('stock','g','type');
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$stbar = Bar($item[1],-10,'mi');
		echo "<tr class=\"$bg\">\n";
		echo "<td><a href=\"?lst=ty&val=".urlencode($item[0])."\">$item[0]</a></td><td>$stbar $item[1]</td></tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

</td><td class="helper">

<h2><?= $chglbl ?> <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/clock.png"><br><?= $updlbl ?></th>
<th width="120"><img src="img/16/abc.png"><br><?= $typlbl ?></th>
<th><img src="img/16/user.png"><br><?= $usrlbl ?></th>
<th><img src="img/16/home.png"><br><?= $loclbl ?></th>
<th><img src="img/16/say.png"><br><?= $cmtlbl ?></th>


<?php
$query	= GenQuery('stock','s','*','time desc',$_SESSION['lim']);
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		list($a1c,$a2c) = Agecol($item[15],$item[15],$row % 2);
		echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
		echo "<img src=\"img/16/" . $cico[$item[0]] . ".png\" title=" . $stco[$item[0]] . "></th>\n";
		echo "<td bgcolor=\"#$a1c\">".date($datfmt,$item[15])."</td>\n";
		echo "<td><a href=\"?lst=ty&val=".urlencode($item[2])."\">$item[2]</a></td>\n";
		echo "<td><a href=\"?lst=us&val=".urlencode($item[14])."\">$item[14]</a></td>\n";
		echo "<td><a href=\"?lst=lo&val=".urlencode($item[4])."\">$item[4]</a></td><td>$item[13]</td>";
		echo "</tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

<h2><?= $loclbl ?> <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/home.png"><br><?= $loclbl ?></th>
<th width="30%"><img src="img/16/form.png"><br><?= $qtylbl ?></th>

<?php
$query	= GenQuery('stock','g','location');
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$stbar = Bar($item[1],0,'mi');
		echo "<tr class=\"$bg\">\n";
		echo "<td><a href=\"?lst=lo&val=".urlencode($item[0])."\">$item[0]</a></td><td>$stbar $item[1]</td></tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

<h2><?= $srclbl ?> <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/ugrp.png"><br><?= $srclbl ?></th>
<th width="30%"><img src="img/16/form.png"><br><?= $qtylbl ?></th>

<?php
$query	= GenQuery('stock','g','source');
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$stbar = Bar($item[1],0,'mi');
		echo "<tr class=\"$bg\">\n";
		echo "<td><a href=\"?lst=ps&val=".urlencode($item[0])."\">$item[0]</a></td><td>$stbar $item[1]</td></tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

<h2><?= $igrp['17'] ?> <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/dril.png"><br><?= $igrp['17'] ?></th>
<th width="30%"><img src="img/16/form.png"><br><?= $qtylbl ?></th>

<?php
$query	= GenQuery('stock','g','partner');
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$stbar = Bar($item[1],0,'mi');
		echo "<tr class=\"$bg\">\n";
		echo "<td><a href=\"?lst=mp&val=".urlencode($item[0])."\">$item[0]</a></td><td>$stbar $item[1]</td></tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

<h2><?= $usrlbl ?> <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/user.png"><br><?= $usrlbl ?></th>
<th width="30%"><img src="img/16/form.png"><br><?= $qtylbl ?></th>
<?php
$query	= GenQuery('stock','g','usrname');
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$stbar = Bar($item[1],0,'mi');
		echo "<tr class=\"$bg\">\n";
		echo "<td><a href=\"?lst=us&val=".urlencode($item[0])."\">$item[0]</a></td><td>$stbar $item[1]</td></tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

<h2><?= $stalbl ?> <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/find.png"><br><?= $stalbl ?></th>
<th width="30%"><img src="img/16/form.png"><br><?= $qtylbl ?></th>
<?php
$query	= GenQuery('stock','g','state');
$res	= DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($item = DbFetchRow($res)) ){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		$stbar = Bar($item[1],0,'mi');
		echo "<tr class=\"$bg\">\n";
		echo "<th class=\"$bi\" width=\"50px\"><img src=\"img/16/" . $cico[$item[0]] . ".png\" title=" . $stco[$item[0]] . "></th>\n";
		echo "<td><a href=\"?lst=st&val=$item[0]\">". $stco[$item[0]] ."</a></td><td>$stbar $item[1]</td></tr>\n";
	}
}
?>
</table>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>

</td></tr></table>

<?php

function WtyBg($wd){
	if($wd){
		if( time() > $wd ){
			return "class=\"crit\"";
		}elseif( time() + 30 * 86400 > $wd ){
			return "class=\"warn\"";
		}else{
			return "class=\"good\"";
		}
	}else{
		return "";
	}
}

include_once ("inc/footer.php");
?>
