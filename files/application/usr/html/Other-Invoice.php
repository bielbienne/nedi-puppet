<?php
# Program: Other-Invoice.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$to = isset($_GET['to']) ? $_GET['to'] : "Happy User\n5000 Voluntary Rd\nNicetown, JO\nGreatland\n";
$no = isset($_GET['no']) ? $_GET['no'] : "";
$cu = isset($_GET['cu']) ? $_GET['cu'] : "u";

$inr = substr(ip2long($_SERVER['SERVER_ADDR']),-6) + date("z") * date("j");

$lnk = DbConnect($dbhost,$dbuser,$dbpass,$dbname);							# Above print-header!
$qry = GenQuery('devices','s','count(*)','','',array('snmpversion'),array('>'),array('0'));
$res = DbQuery($qry,$lnk);
if ($res) {
	$sdv = DbFetchRow($res);
	DbFreeResult($res);
}else{
	print DbError($lnk);
	die;
}

$qry = GenQuery('devices','s','count(*)','','',array('snmpversion'),array('='),array('0'));
$res = DbQuery($qry,$lnk);
if ($res) {
	$ndv = DbFetchRow($res);
	DbFreeResult($res);
}else{
	print DbError($lnk);
	die;
}

$qry = GenQuery('nodes','s','count(*)');
$res = DbQuery($qry,$lnk);
if ($res) {
	$nod = DbFetchRow($res);
	DbFreeResult($res);
}else{
	print DbError($lnk);
	die;
}

if($cu == "u"){
	$cuf = 0.95;
	$cul = 'USD';
	$ibn = 'CH72 0070 0130 0072 8546 9';
}elseif($cu == "e"){
	$cuf = 1.2;
	$cul = 'EUR';
	$ibn = 'CH77 0070 0130 0079 5031 4';
}elseif($cu == "c"){
	$cuf = 1;
	$cul = 'CHF';
	$ibn = 'CH31 0070 0110 0041 9947 4';
}

$sdr = round( 12 / log($sdv[0]) / $cuf,2 );
$sda = intval($sdr * $sdv[0]);

$ndr = round( 6 / log($ndv[0]) / $cuf,2 );
$nda = intval($ndr * $ndv[0]);

$nor = round( 0.8 / log($nod[0]/3) / $cuf,2 );
$noa = intval($nor * $nod[0]);

$tot = $sda + $nda + $noa;
?>

<form method="get" name="bill" action="<?= $self ?>.php">
<?php  if( !isset($_GET['print']) ) { ?>
<h1>NeDi <?= $icelbl ?></h1>
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td>

</td>
<th width="80">

<input type="submit" value="<?= $updlbl ?>">
</th>
</tr></table>
<p></p>
<div class="textpad good">
Adjust the textboxes, select a currency and click on the "Print" icon the create an invoice.<br>
If you email a PDF version to the address on the bottom, it'll be kept for the record.<br>
Thanks for supporting NeDi by paying the suggested annual contribution!
</div>
<?php  } ?>
<p>
<div style="margin:auto;height:780px;width:96%;border:1px solid #111111;background-color:#f4f4f4;font-size:150%">
<table class="full fixed">
<tr><td>
<b>NeDi - Find IT</b><p>
Remo Rickli<br>
Steinbruchstrasse 10b<br>
8187 Weiach<br>
Switzerland<br>

</td>
<td align="right">

<?= $icelbl ?> #<?= $inr ?><br>
<?= date("l, j.F Y") ?>

</td></tr>
<tr class="txtb"><td valign="top">
<b><?= $igrp['33'] ?></b><br>
<?php
if( isset($_GET['print']) ){
	echo "<pre class=\"imga\">$to</pre>\n";

}else{
	echo "<textarea rows=\"5\" name=\"to\" cols=\"25\">$to</textarea>\n";
}
?>

</td>
<td valign="top">
<b><?= $cmtlbl ?></b><br>
<?php
if( isset($_GET['print']) ){
	echo "<pre>$no</pre>\n";

}else{
	echo "<textarea rows=\"5\" name=\"no\" cols=\"25\">$no</textarea>\n";
}
?>

</td>
</tr>
</table>

<p>
<h3><?= $dsclbl ?> <?= $srvlbl ?> 1.Jan.<?= date("Y") ?> - 31.Dec.<?= date("Y") ?></h3>

<table class="full">
<tr class="<?= $modgroup[$self] ?>1">

<?PHP
TblCell($deslbl,'','','','th');
TblCell($qtylbl,'','','','th');
TblCell($metlbl,'','','','th');
TblCell($totlbl,'','','','th');
echo "</tr>\n";
TblRow('txta');
TblCell( 'SNMP Devices' );
TblCell( $sdv[0],"",'align="right"' );
TblCell( $sdr,"",'align="right"' );
TblCell( $sda,"",'align="right"' );
echo "</tr>\n";
TblRow('txtb');
TblCell( "$nonlbl-SNMP Devices" );
TblCell( $ndv[0],"",'align="right"' );
TblCell( $ndr,"",'align="right"' );
TblCell( $nda,"",'align="right"' );
echo "</tr>\n";
TblRow('txta');
TblCell( 'Nodes' );
TblCell( $nod[0],"",'align="right"' );
TblCell( $nor,"",'align="right"' );
TblCell( $noa,"",'style="border-bottom:solid 1px #444" align="right"' );
echo "</tr>\n";
TblRow('txtb');
TblCell($totlbl,'','align="right"','','th');
echo "<td></td><td >";
if( isset($_GET['print']) ){
	echo $cul;
}else{
	echo "Currency <select size=\"1\" name=\"cu\" onchange=\"this.form.submit();\">\n";
	echo "<option value=\"u\"".( ($cu == "u")?" selected":"").">USD\n";
	echo "<option value=\"e\"".( ($cu == "e")?" selected":"").">EUR\n";
	echo "<option value=\"c\"".( ($cu == "c")?" selected":"").">CHF\n";
	echo "</select>\n";
}
echo "</td>";
TblCell($tot,'','style="border-bottom:double 4px #444" align="right"','','th');
?>
</tr>
</table>

<table style='position:relative;top:80px;left:20px;'>
<tr><td>email</td><td>rickli@nedi.ch</td></tr>
<tr><td>phone</td><td>+41 41 511 98 41</td></tr>
<tr><td>swift</td><td>ZKBKCHZZ80A</td></tr>
<tr><td>iban</td><td><?= $ibn ?></td></tr>
</table>

</div>
</form>
<?php
include_once ("inc/footer.php");
?>
