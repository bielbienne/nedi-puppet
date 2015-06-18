<?php
# Program: Other-Calculator.php
# Programmer: Remo Rickli (based on the Perl IP Calculator of Krischan Jodies)

$printable = 1;
$exportxls = 1;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$getip  = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];
$getmsk = isset($_GET['nmsk']) ? $_GET['nmsk'] : "24";
$getsub = isset($_GET['smsk']) ? $_GET['smsk'] : "";

if( !isset($_GET['xls']) ) {
?>
<h1>IP Calculator</h1>
<form method="get" action="<?= $self ?>.php" name="calc">
<table class="content" ><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
IP <?= $adrlbl ?> <input type="text" name="ip" value="<?= $getip ?>" size="15">
/ <input type="text" name="nmsk" value="<?= $getmsk ?>" size="15">
</th><th>
Sub/Supernet <?= $msklbl ?> <input type="text" name="smsk" value="<?= $getsub ?>" size="15">
</th>
<th width="80">
<input type="submit" value="<?= $sholbl ?>" name="calc">
</th>
</tr>
</table></form>
<?php
}

if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$getip) ){
	$ip	= $getip;
	$dip	= ip2long($getip);
}else{
	$getip = $getip + 0;													// force 32 Bit unsigned for PHP!!!
	$ip	= long2ip($getip);
	$dip	= $getip;
}
list($pfix,$mask,$bmsk,$dmsk) = Masker($getmsk);

$hmsk	= "0x".ip2hex($mask);
$hip	= "0x".ip2hex($ip);
$bip	= ip2bin($ip);
//or $bip	= base_convert($dip,10,2);

$dwmsk	= ~$dmsk;
$wmsk	= long2ip($dwmsk);
$bwmsk	= ip2bin($wmsk);
$hwmsk	= "0x".ip2hex($wmsk);

$dnet	= ($dip & $dmsk);
$net	= long2ip($dnet);
$bnet	= ip2bin($net);
$hnet	= "0x".ip2hex($net);

$bc	= long2ip($dnet + $dwmsk);
$dbc	= ip2long($bc);
$bbc	= ip2bin($bc);
$hbc	= "0x".ip2hex($bc);

$fho	= long2ip($dnet + 1);
$bfho	= ip2bin($fho);
$hfho	= "0x".ip2hex($fho);

$lho	= long2ip($dbc - 1);
$blho	= ip2bin($lho);
$hlho	= "0x".ip2hex($lho);

$nho	= $dbc - $dnet - 1;

if( !isset($_GET['xls']) ) {
?>
<h2>Base Info</h2>
<table class="content" >
<tr class="<?= $modgroup[$self] ?>1"><th width="80">&nbsp;</th><th width="30%">Dotted Decimal</th><th>Binary</th><th>Hexadecimal</th></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>2"><?= $adrlbl ?></th><td class="blu code"><?= $ip ?> (<?= sprintf("%u", ip2long($ip)) ?>)</td><td class="blu code"><?= $bip ?></td><td class="blu code"><?= $hip ?></td></tr>
<tr class="txtb"><th class="<?= $modgroup[$self] ?>2"><?= $msklbl ?></th><td class="grn code"><?= $mask ?> = <?= $pfix ?> (<?= $dmsk ?>)</td><td class="grn code"><?= $bmsk ?></td><td class="grn code"><?= $hmsk ?></td></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>2">Wildcard</th><td class="grn code"><?= $wmsk ?></td><td class="grn code"><?= $bwmsk ?></td><td class="grn code"><?= $hwmsk ?></td></tr>
<tr class="txtb"><th class="<?= $modgroup[$self] ?>2"><?= $netlbl ?></th><td class="prp code"><?= $net ?></td><td class="prp code"><?= $bnet ?></td><td class="prp code"><?= $hnet ?></td></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>2">Broadcast</th><td class="prp code"><?= $bc ?></td><td class="prp code"><?= $bbc ?></td><td class="prp code"><?= $hbc ?></td></tr>
<tr class="txtb"><th class="<?= $modgroup[$self] ?>2">1. Host</th><td class="drd code"><?= $fho ?></td><td class="drd code"><?= $bfho ?></td><td class="drd code"><?= $hfho ?></td></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>2">n. Host</th><td class="drd code"><?= $lho ?> (<?= $nho ?> total)</td><td class="drd code"><?= $blho ?></td><td class="drd code"><?= $hlho ?></td></tr>
</table>
<?php
}

if ($getsub){
	list($spfix,$smask,$bsmsk) = Masker($getsub);

	$hsmsk	= "0x".str_pad(ip2hex($smask),8,0);
	$dsmsk	= ip2long($smask);

	$dwsmsk	= ~ $dsmsk;
	$wsmsk	= long2ip($dwsmsk);
	$bwsmsk	= ip2bin($wsmsk);
	$hwsmsk	= "0x".ip2hex($wsmsk);

	if($pfix < $spfix){
		if( !isset($_GET['xls']) ) {
?>
<h2>Subnet <?= $sumlbl ?></h2>
<table class="content">
<tr class="<?= $modgroup[$self] ?>1"><th width="80">&nbsp;</th><th width="30%">Dotted Decimal</th><th>Binary</th><th>Hexadecimal</th></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>1">Mask</th><td class="grn code"><?= $smask ?> = <?= $spfix ?></td><td class="grn code"><?= $bsmsk ?></td><td class="grn code"><?= $hsmsk ?></td></tr>
<tr class="txtb"><th class="<?= $modgroup[$self] ?>1">Wildcard</th><td class="grn code"><?= $wsmsk ?></td><td class="grn code"><?= $bwsmsk ?></td><td class="grn code"><?= $hwsmsk ?></td></tr>
</table>
<p>
<h2>Subnets</h2>

<?php
		}
		$cols = array(	"net-BL"=>"#",
				"ippre"=>"$netlbl",
				"fst"=>"$sttlbl",
				"las"=>"$endlbl",
				"brc"=>"Broadcast",
				"tot"=>"$totlbl Hosts"
				);

		TblHead("$modgroup[$self]2",3);

		$nsnets = pow(2, ($spfix-$pfix) );
		$snoff  = pow(2, (32 - $spfix) );

		$nsho= 0;
		for ($s=0;$s < $nsnets; $s++){
			if ($s % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$dsnet	= $dnet + $s * $snoff;
			$snet	= long2ip($dsnet);
			list($ntimg,$ntit) = Nettype($snet);
			$fsho	= long2ip($dsnet + 1);
			$sbc	= long2ip($dsnet + $dwsmsk);
			$lsho	= long2ip($dsnet + $dwsmsk - 1);
			$nsho	+= $snoff - 2;

			TblRow($bg);
			TblCell($s,'',"class=\"$bi\" width=\"50px\"","<img src=\"img/$ntimg\" title=\"$ntit\">",'th');
			TblCell("$snet/$spfix",'','class="prp code"');
			TblCell("$fsho",'','class="drd code"');
			TblCell("$lsho",'','class="drd code"');
			TblCell("$sbc",'','class="prp code"');
			TblCell("$nsho",'','class="blu code"');
			echo "</tr>\n";
		}
	}elseif($pfix > $spfix){
		$snet	= long2ip($dip & $dsmsk);
		$dsnet	= ip2long($snet);
		$bsnet	= ip2bin($snet);
		$hsnet	= "0x".str_pad(ip2hex($snet),8,0);

		$sbc	= long2ip($dsnet + $dwsmsk);
		$dsbc	= ip2long($sbc);
		$bsbc	= ip2bin($sbc);
		$hsbc	= "0x".ip2hex($sbc);

?>
<h2>Supernet</h2>
<table class="content" >
<tr class="<?= $modgroup[$self] ?>1"><th width="80">&nbsp;</th><th width="30%">Dotted Decimal</th><th>Binary</th><th>Hexadecimal</th></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>1">Mask</th><td class="grn code"><?= $smask ?> = <?= $spfix ?></td><td class="grn code"><?= $bsmsk ?></td><td class="grn code"><?= $hsmsk ?></td></tr>
<tr class="txtb"><th class="<?= $modgroup[$self] ?>1">Wildcard</th><td class="grn code"><?= $wsmsk ?></td><td class="grn code"><?= $bwsmsk ?></td><td class="grn code"><?= $hwsmsk ?></td></tr>
<tr class="txta"><th class="<?= $modgroup[$self] ?>1">Network</th><td class="prp code"><?= $snet ?></td><td class="prp code"><?= $bsnet ?></td><td class="prp code"><?= $hsnet ?></td></tr>
<tr class="txtb"><th class="<?= $modgroup[$self] ?>1">Broadcast</th><td class="prp code"><?= $sbc ?></td><td class="prp code"><?= $bsbc ?></td><td class="prp code"><?= $hsbc ?></td></tr>
</table>
<?php
	}
	echo "</table>\n";
}
include_once ("inc/footer.php");
?>
