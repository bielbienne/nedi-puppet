<?php
# Program: System-NeDi.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");

$_POST = sanitize($_POST);

$mde = isset($_POST['mde']) ? $_POST['mde'] : "h";

$vrb = isset($_POST['vrb']) ? "checked" : "";
$wco = isset($_POST['wco']) ? "checked" : "";
$quk = isset($_POST['quk']) ? "checked" : "";

$dip = isset($_POST['dip']) ? "checked" : "";
$rte = isset($_POST['rte']) ? "checked" : "";
$oui = isset($_POST['oui']) ? "checked" : "";

$ndv = isset($_POST['ndv']) ? "checked" : "";
$ndn = isset($_POST['ndn']) ? "checked" : "";
$fqd = isset($_POST['fqd']) ? "checked" : "";

$sed = isset($_POST['sed']) ? $_POST['sed'] : "";
$opt = isset($_POST['opt']) ? $_POST['opt'] : "";
$bup = isset($_POST['bup']) ? $_POST['bup'] : "";

$usr = isset($_POST['usr']) ? $_POST['usr'] : "";
$psw = isset($_POST['psw']) ? $_POST['psw'] : "";

$cmd = "$nedipath/nedi.pl";

if($mde == "i"){
	$cmd .= " -i $usr $psw";
}elseif($mde == "y"){
	$cmd .= " -y";
}elseif($opt and $mde == "s"){
	$cmd .= (($vrb)?" -v":"")." -s TUFip=$opt";
}elseif($mde == "d"){
	$arg = "";

	if($vrb){$arg .= "v";}

	if($wco){$arg .= "W";}
	if($dip){$arg .= "p";}
	if($rte){$arg .= "r";}
	if($oui){$arg .= "o";}
	if($ndn){$arg .= "n";}
	if($ndv){$arg .= "N";}
	if($fqd){$arg .= "F";}
	if($arg){$arg = "-" . $arg;}

	if($bup){$arg .= " -".$bup;}
	if($quk){$arg .= " -SOAPjedibatflow ";}

	$cmd .= " -l100 $arg".(($sed)?" -$sed $opt":"");
}elseif($mde == "h"){
	$cmd .= " --help";
}

?>
<script language="JavaScript">
<!--
chg = 0;

function ConfirmSubmit(){

	if (document.nedi.mde[4].checked == true){
		if( confirm('NeDi <?= $reslbl ?>, <?= $cfmmsg ?>') ){
			document.nedi.submit();
		}else{
			return;
		}
	}
	document.nedi.submit();
}

// rufers idea
function UpCmd(){

	var arg = "";
	if(document.nedi.mde[0].checked){
		if(document.nedi.vrb.checked){arg += "v"}
		if(document.nedi.wco.checked){arg += "W"}
		if(document.nedi.dip.checked){arg += "p"}
		if(document.nedi.rte.checked){arg += "r"}
		if(document.nedi.oui.checked){arg += "o"}
		if(document.nedi.ndv.checked){arg += "N"}
		if(document.nedi.ndn.checked){arg += "n"}
		if(document.nedi.fqd.checked){arg += "F"}
		if(arg != ""){arg = "-" + arg}

		if(document.nedi.bup.selectedIndex){arg += " -" + document.nedi.bup.options[document.nedi.bup.selectedIndex].value}
		if(document.nedi.quk.checked){arg += " -SOAPjedibatflow"}
		if(document.nedi.sed.selectedIndex){arg += " -" + document.nedi.sed.options[document.nedi.sed.selectedIndex].value + document.nedi.opt.value}
	}else if(document.nedi.mde[1].checked){
		if(document.nedi.vrb.checked){arg = "-v"}
		arg += " -s TUFip=" + document.nedi.opt.value;
	}else if(document.nedi.mde[2].checked){
		arg = "-y";
	}else if(document.nedi.mde[3].checked){
		arg = "--help";
	}

		cmd = document.getElementById('cmd');
		cmd.innerHTML = "<?= $nedipath ?>/nedi.pl " + arg;
		cmd.style.opacity = 0.6;
}
//--></script>

<h1><?= (($verb1)?"$cmdlbl NeDi":"NeDi $cmdlbl") ?></h1>

<?php  if( !isset($_GET['print']) ){ ?>
<form name="nedi" action="<?= $self ?>.php" method="post">
<table class="content">
<tr class="<?= $modgroup[$self] ?>1">
<th width="50" class="<?= $modgroup[$self] ?>1"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>

<td valign="top">

<h3>
<input type="radio" name="mde" value="d" <?= ($mde == "d")?"checked":"" ?> onchange="UpCmd();"> <?= $dsclbl ?>
<input type="radio" name="mde" value="s" <?= ($mde == "s")?"checked":"" ?> onchange="UpCmd();"> Scan IP
<input type="radio" name="mde" value="y" <?= ($mde == "y")?"checked":"" ?> onchange="UpCmd();"> Definitions
<input type="radio" name="mde" value="h" <?= ($mde == "h")?"checked":"" ?> onchange="UpCmd();"> Help
</h3>

<table>
<tr>
<td>
<select size="1" name="sed" onchange="UpCmd();">
<option value="">Seed ->
<option value="a" <?= ($sed == "a")?" selected":"" ?> ><?= $addlbl ?> IP
<option value="A" <?= ($sed == "A")?" selected":"" ?> ><?= $addlbl ?> DB
<option value="t" <?= ($sed == "t")?" selected":"" ?> ><?= $tstlbl ?> IP
</select>
<input type="text" name="opt" value="<?= htmlspecialchars($opt) ?>" size="15" title="seed/scan IP" onfocus="select();" onchange="UpCmd();">
</td>

<td><input type="checkbox" name="vrb" <?= $vrb ?> title="<?= (($verb1)?"$sholbl $deslbl":"$deslbl $sholbl") ?>" onchange="UpCmd();">Verbose</td>
<td><input type="checkbox" name="dip" <?= $dip ?> title="LLDP, CDP, FDP, NDP..." onchange="UpCmd();"> <?= $prolbl ?></td>
<td><input type="checkbox" name="rte" <?= $rte ?> title="<?= (($verb1)?"$dsclbl Routes":"Routes $dsclbl") ?>" onchange="UpCmd();"> Route</td>
<td><input type="checkbox" name="oui" <?= $oui ?> title="<?= (($verb1)?"$dsclbl OUI $venlbl":"OUI $venlbl $dsclbl") ?>" onchange="UpCmd();"> OUI</td>

</tr>
<tr>
	
<td>
<select size="1" name="bup" onchange="UpCmd();">
<option value=""><?= $cfglbl ?>
<option value="b" <?= ($bup == "b")?" selected":"" ?> >DB <?= $buplbl ?>
<option value="B0" <?= ($bup == "B0")?" selected":"" ?> >DB & <?= $fillbl ?>
<option value="B5" <?= ($bup == "B5")?" selected":"" ?> >DB & <?= $fillbl ?> (<?= $maxlbl ?> 5)
<option value="B10" <?= ($bup == "B10")?" selected":"" ?> >DB & <?= $fillbl ?> (<?= $maxlbl ?> 10)
</select>
</td>
<td><input type="checkbox" name="wco" <?= $wco ?> title="<?= $dsclbl ?> Writecommunity" onchange="UpCmd();"> <?= $wrtlbl ?></td>
<td><input type="checkbox" name="quk" <?= $quk ?> title="<?= $notlbl ?> <?= $dsclbl ?> <?= $inflbl ?>" onchange="UpCmd();"> <?= $faslbl ?></td>
<td><input type="checkbox" name="ndn" <?= $ndn ?> title="<?= $nonlbl ?> Node <?= $namlbl ?>" onchange="UpCmd();"> No DNS</td>
<td><input type="checkbox" name="fqd" <?= $fqd ?> title="Device <?= $namlbl ?> & Domain" onchange="UpCmd();"> FQDN</td>
<td><input type="checkbox" name="ndv" <?= $ndv ?> title="Keep devices in Nodes" onchange="UpCmd();"> Dev-> Nod</td>

</tr>
</table>

</td><td valign="top">

<h3>
<input type="radio" name="mde" value="i" <?= ($mde == "i")?"checked":"" ?>> Init
</h3>

<img src="img/16/ucfg.png" title="DB Admin"> <input type="text" name="usr" size="10"><p>
<img src="img/16/loko.png" title="Password"> <input type="password" name="psw" size="10">

</td>
<th width="80" >

<input type="button" name="go" value="<?= $cmdlbl ?>" onClick="ConfirmSubmit();"></th>

</tr></table>
</form>
<?php } ?>

<h2 id="cmd"><?= $cmd ?></h2>
<div class="textpad code txta" name="out">
<?php
ob_end_flush();
system(addslashes($cmd)." 2>&1");
?>
</div><br>

<?php

include_once ("inc/footer.php");
?>
