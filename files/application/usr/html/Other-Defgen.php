<?php
# Program: Other-Defgen.php
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");

$_GET = sanitize($_GET);
$co = isset($_GET['co']) ? $_GET['co'] : "public";
$so = isset($_GET['so']) ? $_GET['so'] : "1.3.6.1.4.1.?";
$sc = isset($_GET['sc']) ? $_GET['sc'] : "";
$ip = isset($_GET['ip']) ? $_GET['ip'] : "";
$wr = isset($_POST['wr']) ? $_POST['wr'] : "";

$def = "";
$dis = "";																	# 0 is not working with javascript!

$typ = "";
$des = "";
$to = "";

$df  = array();
$ver = array();

$ico = "w2an";
$hgt = "1";

$bi = "";
$sn = "";
$vln = "";
$vnx = "";
$arp = "";
$grp = "";
$dmo = "";
$cfc = "";
$cfw = "";

$stx = "";
$enx = "";
$ina = "";
$ifa = "";
$ial = "";
$iax = "";
$idu = "";
$idx = "";
$hdv = "";
$fdv = "";
$brc = "";
$idi = "";
$odi = "";
$ivl = "";
$ivx = "";
$ipw = "";
$pem = "";
$ipx = "";

$msl = "";
$mcl = "";
$mcv = "";
$mde = "";
$mhw = "";
$msw = "";
$mfw = "";
$msn = "";
$mmo = "";

$cpu = "";
$cmu = "";
$tmp = "";
$tmu = "";
$mcp = "";
$mmu = "";
$mio = "";

$cul = "";
$cuv = "";

$os  = "";
$bfd = "";
$dpr = "";

$wrw = "";


$defpath = "$nedipath/sysobj";

if($isadmin and $so and $sc){
?>
<script language="JavaScript"><!--
setTimeout("history.go(-1)",2000);
//--></script>
<?php
	if( copy("$defpath/$sc.def", "$defpath/$so.def") ){
		echo "<h5>$coplbl $defpath/$so.def OK</h5>\n";
	}else{
		echo "<h4>$errlbl $coplbl $defpath/$so.def!</h4>\n";
	}
	include_once ("inc/footer.php");
	exit(0);
}

if($so){
	if( file_exists("$defpath/$so.def") ){
		$deffile = file("$defpath/$so.def");
		$wrw = "onclick=\"return confirm('".(($verb1)?"$rpllbl $so.def":"$so.def $rpllbl").", $cfmmsg')\"";
	}elseif( file_exists("log/$so.def") ){
		$deffile = file("log/$so.def");
		$defpath = "log";
	}else{
		$deffile = "";
	}
	if ($deffile){
		$mailbody = rawurlencode( str_replace('	','____',implode('',$deffile)) );			# Replacing tabs with something I can easily substitute again, since mail clients convert tabs to spaces...
		$def = "$realbl $defpath/$so.def OK\n\n";
		do {
			$defhead = array_shift($deffile);
			$def .= $defhead;
		}while(preg_match('/^[#;]/',$defhead) );

		foreach ($deffile as $l) {
			if( !preg_match('/^[#;]/', $l) ){
				$d = preg_split('/\t+/',rtrim($l) );
				if($d[0] == 'SNMPv' AND $d[1] == '2HC'){$ver['2HC'] = ' selected';}
				elseif($d[0] == 'SNMPv' AND $d[1] == '2MC'){$ver['2MC'] = ' selected';}
				elseif($d[0] == 'SNMPv' AND $d[1] == '2'){$ver['2'] = ' selected';}
				elseif($d[0] == 'Type'){$typ = $d[1];}
				elseif($d[0] == 'Sysdes'){$des = $d[1];}
				elseif($d[0] == 'Icon'){$ico = $d[1];}
				elseif($d[0] == 'Size'){$hgt = $d[1];}
				elseif($d[0] == 'Typoid'){$to = $d[1];}
				elseif($d[0] == 'OS' AND $d[1]){$os = $d[1];}
				elseif($d[0] == 'Bridge' AND $d[1]){$bfd = $d[1];}
				elseif($d[0] == 'ArpND' AND $d[1]){$arp = $d[1];}
				elseif($d[0] == 'Dispro'){$dpr = $d[1];}
				elseif($d[0] == 'Serial'){$sn  = $d[1];}
				elseif($d[0] == 'Bimage'){$bi  = $d[1];}
				elseif($d[0] == 'VLnams'){$vln = $d[1];}
				elseif($d[0] == 'VLnamx'){$vnx = $d[1];}
				elseif($d[0] == 'Group'){$grp = $d[1];}
				elseif($d[0] == 'Mode'){$dmo = $d[1];}
				elseif($d[0] == 'CfgChg'){$cfc = $d[1];}
				elseif($d[0] == 'CfgWrt'){$cfw = $d[1];}
				elseif($d[0] == 'StartX'){$stx = $d[1];}
				elseif($d[0] == 'EndX'){$enx = $d[1];}
				elseif($d[0] == 'IFname'){$ina = $d[1];}
				elseif($d[0] == 'IFaddr'){$ifa = $d[1];}
				elseif($d[0] == 'IFalia'){$ial = $d[1];}
				elseif($d[0] == 'IFalix'){$iax = $d[1];}
				elseif($d[0] == 'IFdupl'){$idu = $d[1];}
				elseif($d[0] == 'IFduix'){$idx = $d[1];}
				elseif($d[0] == 'Halfdp'){$hdv = $d[1];}
				elseif($d[0] == 'Fulldp'){$fdv = $d[1];}
				elseif($d[0] == 'InBcast'){$brc = $d[1];}
				elseif($d[0] == 'InDisc'){$idi = $d[1];}
				elseif($d[0] == 'OutDisc'){$odi = $d[1];}
				elseif($d[0] == 'IFvlan'){$ivl = $d[1];}
				elseif($d[0] == 'IFvlix'){$ivx = $d[1];}
				elseif($d[0] == 'IFpowr'){$ipw = $d[1];$pem = $d[2];}
				elseif($d[0] == 'IFpwix'){$ipx = $d[1];}
				elseif($d[0] == 'Modesc'){$mde = $d[1];}
				elseif($d[0] == 'Moclas'){$mcl = $d[1];}
				elseif($d[0] == 'Movalu'){$mcv = $d[1];}
				elseif($d[0] == 'Moslot'){$msl = $d[1];}
				elseif($d[0] == 'Modhw'){$mhw  = $d[1];}
				elseif($d[0] == 'Modsw'){$msw  = $d[1];}
				elseif($d[0] == 'Modfw'){$mfw  = $d[1];}
				elseif($d[0] == 'Modser'){$msn = $d[1];}
				elseif($d[0] == 'Momodl'){$mmo = $d[1];}
				elseif($d[0] == 'CPUutl'){$cpu = $d[1];$cmu = $d[2];}
				elseif($d[0] == 'Temp'){$tmp   = $d[1];$tmu = $d[2];}
				elseif($d[0] == 'MemCPU'){$mcp = $d[1];$mmu = $d[2];}
				elseif($d[0] == 'MemIO'){$cuv = $d[1];$cul = "MemIO;G;Bytes";}	# Support legacy .defs
				elseif($d[0] == 'Custom'){$cul = $d[1];$cuv = $d[2];}
			}
		}
	}
}

echo "<h1>Definition Generator</h1>\n";
if($isadmin and $wr){
?>
<script language="JavaScript"><!--
setTimeout("history.go(-1)",2000);
//--></script>
<?php
	$def  = str_replace ('____', '	', preg_replace("/\r|\/|\\|\.\.|/", "", $_POST['def']) );
	$so   = preg_replace("/\/|\\|\.\.|/", "", $_POST['so'] );
	$hdle = fopen("$defpath/$so.def", "w");
	if( fwrite($hdle, $def) ){
		echo "<h5>$wrtlbl $defpath/$so.def OK</h5>\n";
	}else{
		echo "<h4>$errlbl $wrtlbl $defpath/$so.def!</h4>\n";
	}
	fclose($hdle);

	echo "<table align=\"center\" bgcolor=\"#cccccc\" cellpadding=\"20\"><tr><td><pre>$def</pre></td></tr></table>\n";
	include_once ("inc/footer.php");
	exit(0);
}
?>

<script language="JavaScript">
<!--
dis = '<?= $dis ?>';

function update() {

	if (dis){
		alert('Controls disabled!');
	}else{
		document.gen.so.value = document.bld.so.value;
		document.gen.def.value = "# Definition for " + document.bld.so.value + " created by Defgen 1.8 on <?= date('j.M Y') ?> (<?= $_SESSION['user'] ?>)\n" +
		" \n# General\n" +
		"SNMPv\t" + document.bld.ver.options[document.bld.ver.selectedIndex].value + "\n" +
		"Type\t" + document.bld.typ.value + "\n" +
		"Typoid\t" + document.bld.to.value + "\n" +
		"Sysdes\t" + document.bld.des.value + "\n" +
		"OS\t" + document.bld.os.options[document.bld.os.selectedIndex].value + "\n" +
		"Icon\t" + document.bld.ico.value + "\n" +
		"Size\t" + document.bld.hgt.value + "\n" +
		"Bridge\t" + document.bld.brg.options[document.bld.brg.selectedIndex].value + "\n" +
		"ArpND\t" + document.bld.arp.options[document.bld.arp.selectedIndex].value + "\n" +
		"Dispro\t" + document.bld.dpr.value + "\n" +
		"Serial\t" + document.bld.sn.value + "\n" +
		"Bimage\t" + document.bld.bi.value + "\n" +
		"CfgChg\t" + document.bld.cfc.value + "\n" +
		"CfgWrt\t" + document.bld.cfw.value + "\n" +
		"VLnams\t" + document.bld.vln.value + "\n" +
		"VLnamx\t" + document.bld.vnx.value + "\n" +
		"Group\t" + document.bld.grp.value + "\n" +
		"Mode\t" + document.bld.dmo.value + "\n" +
		" \n# Interfaces\n" +
		"StartX\t" + document.bld.stx.value + "\n" +
		"EndX\t" + document.bld.enx.value + "\n" +
		"IFname\t" + document.bld.ina.value + "\n" +
		"IFaddr\t" + document.bld.ifa.options[document.bld.ifa.selectedIndex].value + "\n" +
		"IFalia\t" + document.bld.ial.value + "\n" +
		"IFalix\t" + document.bld.iax.value + "\n" +
		"InBcast\t" + document.bld.brc.value + "\n" +
		"InDisc\t" + document.bld.idi.value + "\n" +
		"OutDisc\t" + document.bld.odi.value + "\n" +
		"IFvlan\t" + document.bld.ivl.value + "\n" +
		"IFvlix\t" + document.bld.ivx.value + "\n" +
		"IFpowr\t" + document.bld.ipw.value + "\t" + document.bld.pem.value + "\n" +
		"IFpwix\t" + document.bld.ipx.value + "\n" +
		"IFdupl\t" + document.bld.idu.value + "\n" +
		"IFduix\t" + document.bld.idx.value + "\n" +
		"Halfdp\t" + document.bld.hdv.value + "\n" +
		"Fulldp\t" + document.bld.fdv.value + "\n" +
		" \n# Modules\n" +
		"Modesc\t" + document.bld.mde.value + "\n" +
		"Moclas\t" + document.bld.mcl.value + "\n" +
		"Movalu\t" + document.bld.mcv.value + "\n" +
		"Moslot\t" + document.bld.msl.value + "\n" +
		"Modhw\t" + document.bld.mhw.value + "\n" +
		"Modsw\t" + document.bld.msw.value + "\n" +
		"Modfw\t" + document.bld.mfw.value + "\n" +
		"Modser\t" + document.bld.msn.value + "\n" +
		"Momodl\t" + document.bld.mmo.value + "\n" +
		" \n# RRD Graphing\n" +
		"CPUutl\t" + document.bld.cpu.value + "\t" + document.bld.cmu.value + "\n" +
		"Temp\t" + document.bld.tmp.value + "\t" + document.bld.tmu.value + "\n" +
		"MemCPU\t" + document.bld.mcp.value + "\t" + document.bld.mmu.value + "\n" +
		"Custom\t" + document.bld.cul.value + "\t" + document.bld.cuv.value;

		document.gen.wr.disabled=false;
	}
}

function setgen(gen) {
	if('1' == gen){
		document.bld.sn.value = "1.3.6.1.2.1.47.1.1.1.1.11.1";
		document.bld.bi.value = "";
		document.bld.ico.value = "w2gn";
		document.bld.hgt.value = "1";
		document.bld.to.value = "1.3.6.1.2.1.47.1.1.1.1.13.1";
		document.bld.ver.selectedIndex  = 2;
		document.bld.os.selectedIndex   = 0;
		document.bld.brg.selectedIndex  = 1;
		document.bld.arp.selectedIndex  = 1;
		document.bld.dpr.value = "LLDP";
		document.bld.vln.value = "1.3.6.1.2.1.17.7.1.4.3.1.1";
		document.bld.grp.value = "";
		document.bld.dmo.value = "";
		document.bld.cfc.value = "";
		document.bld.cfw.value = "";
	}else{
		document.bld.sn.value = "";
		document.bld.bi.value = "";
		document.bld.ico.value = "w2an";
		document.bld.hgt.value = "1";
		document.bld.ver.selectedIndex  = 0;
		document.bld.os.selectedIndex  = 0;
		document.bld.brg.selectedIndex  = 0;
		document.bld.arp.selectedIndex  = 0;
		document.bld.dpr.value = "";
		document.bld.vln.value = "";
		document.bld.grp.value = "";
		document.bld.dmo.value = "";
		document.bld.cfc.value = "";
		document.bld.cfw.value = "";
	}
	update();
}

function setint(typ) {
	if ('1' == typ){
		document.bld.stx.value = "";
		document.bld.enx.value = "";
		document.bld.ina.value = "1.3.6.1.2.1.31.1.1.1.1";
		document.bld.ifa.selectedIndex = 2;
		document.bld.ial.value = "1.3.6.1.2.1.31.1.1.1.18";
		document.bld.iax.value = "";
		document.bld.idu.value = "1.3.6.1.2.1.10.7.2.1.19";
		document.bld.idx.value = "1.3.6.1.2.1.10.7.2.1.1";
		document.bld.hdv.value = "2";
		document.bld.fdv.value = "3";
		document.bld.brc.value = "1.3.6.1.2.1.31.1.1.1.3";
		document.bld.idi.value = "1.3.6.1.2.1.2.2.1.13";
		document.bld.odi.value = "1.3.6.1.2.1.2.2.1.19";
		document.bld.ivl.value = "1.3.6.1.2.1.17.7.1.4.5.1.1";
		document.bld.ivx.value = "";
		document.bld.ipw.value = "";
		document.bld.pem.selectedIndex = 0;
		document.bld.ipx.value = "";
	}else{
		document.bld.stx.value = "";
		document.bld.enx.value = "";
		document.bld.ina.value = "";
		document.bld.ifa.selectedIndex = 1;
		document.bld.ial.value = "";
		document.bld.iax.value = "";
		document.bld.idu.value = "";
		document.bld.idx.value = "";
		document.bld.hdv.value = "";
		document.bld.fdv.value = "";
		document.bld.brc.value = "";
		document.bld.idi.value = "";
		document.bld.odi.value = "";
		document.bld.ivl.value = "";
		document.bld.ivx.value = "";
		document.bld.ipw.value = "";
		document.bld.pem.selectedIndex = 0;
		document.bld.ipx.value = "";
	}
	update();
}

function setmod(typ) {
	if ('1' == typ){
		document.bld.mde.value = "1.3.6.1.2.1.47.1.1.1.1.2";
		document.bld.mcl.value = "1.3.6.1.2.1.47.1.1.1.1.5";
		document.bld.mcv.value = "9";
		document.bld.msl.value = "1.3.6.1.2.1.47.1.1.1.1.7";
		document.bld.mhw.value = "1.3.6.1.2.1.47.1.1.1.1.8";
		document.bld.msw.value = "1.3.6.1.2.1.47.1.1.1.1.9";
		document.bld.mfw.value = "1.3.6.1.2.1.47.1.1.1.1.10";
		document.bld.msn.value = "1.3.6.1.2.1.47.1.1.1.1.11";
		document.bld.mmo.value = "1.3.6.1.2.1.47.1.1.1.1.13";
	}else if ('2' == typ){
		document.bld.mde.value = "1.3.6.1.2.1.43.11.1.1.6.1";
		document.bld.mcl.value = "";
		document.bld.mcv.value = "30";
		document.bld.msl.value = "1.3.6.1.2.1.43.11.1.1.5.1";
		document.bld.mhw.value = "1.3.6.1.2.1.43.11.1.1.9.1";
		document.bld.msw.value = "";
		document.bld.mfw.value = "1.3.6.1.2.1.43.11.1.1.8.1";
		document.bld.msn.value = "";
		document.bld.mmo.value = "";
	}else{
		document.bld.mde.value = "";
		document.bld.mcl.value = "";
		document.bld.mcv.value = "";
		document.bld.msl.value = "";
		document.bld.mhw.value = "";
		document.bld.msw.value = "";
		document.bld.mfw.value = "";
		document.bld.msn.value = "";
		document.bld.mmo.value = "";
	}
	update();
}

function setrrd(typ) {
	if ('1' == typ){
		document.bld.cpu.value = "1.3.6.1.4.1.2021.10.1.3.3";
		document.bld.tmp.value = "1.3.6.1.2.1.99.1.1.1.4.11";
		document.bld.mcp.value = "1.3.6.1.4.1.2021.4.11.0";
		document.bld.cul.value = "";
		document.bld.cuv.value = "";
	}else{
		document.bld.cpu.value = "";
		document.bld.cmu.value = "";
		document.bld.tmp.value = "";
		document.bld.tmu.value = "";
		document.bld.mcp.value = "";
		document.bld.mmu.value = "";
		document.bld.cuv.value = "";
	}
	update();
}

function get(oid) {
	window.open('inc/snmpget.php?d=<?= $debug ?>&ip=' + document.bld.ip.value + '&v=' + document.bld.ver.value.substr(0,1) + '&c=' + document.bld.co.value + '&oid=' + oid,'SNMP','scrollbars=1,menubar=0,resizable=1,width=400,height=300');
}

function walk(oid) {
	window.open('inc/snmpwalk.php?d=<?= $debug ?>&ip=' + document.bld.ip.value + '&v=' + document.bld.ver.value.substr(0,1) + '&c=' + document.bld.co.value + '&oid=' + oid,'SNMP','scrollbars=1,menubar=0,resizable=1,width=500,height=600');
}

//-->
</script>
<table class="content" ><tr class="<?= $modgroup[$self] ?>2">
<th width="50" rowspan="3" class="<?= $modgroup[$self] ?>1"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td>

<form name="bld">
<table width="100%">

<tr><th align="right">IP</th>
<td><input type="text" name="ip" value="<?= $ip ?>" size="20" onfocus="select();" title="target's IP address">
<?= (Devcli($ip,22,1)) ?>
<?= (Devcli($ip,23,1)) ?>
</td>

<th align="right">Community</th><td><input type="text" name="co" value="<?= $co ?>" size="20" onfocus="select();" title="target's SNMP community"></td></tr>

<tr class="<?= $modgroup[$self] ?>1"><th colspan="4">
<img src="img/16/bcnl.png" align="right" onClick="setgen();" title="<?= $reslbl ?>">
<img src="img/16/idea.png" align="right" onClick="setgen('1');" title="Standard System OIDs">
<img src="img/16/dev.png" align="left" >
<?= $manlbl ?>
</th></tr>

<tr><th align="right">
SysObjId</th><td>
<input type="text" name="so" value="<?= $so ?>" size="30" title="SysObj ID -> <?= $fillbl ?> <?= $namlbl ?>" onfocus="select();" onchange="update();">
<br>
<?php
$mybase = substr($so, 0, -strlen(strrchr($so, ".")));
$mytyp  = substr(strrchr($so, "."), 1);

foreach (glob("$defpath/$mybase*") as $f){
	$defoid  = substr($f, strlen($defpath)+1,-4);
	$defbase = substr($defoid, 0, -strlen(strrchr($defoid, ".")));
	$deftyp  = substr(strrchr($defoid, "."), 1);
	if( $mybase == $defbase and $deftyp > ($mytyp-10) and $deftyp < ($mytyp+10) ){
		$df[] = "$defbase.$deftyp";
	}
}
sort($df);
foreach ($df as $f){
	if($f == $so){
?>
<img src="img/16/brld.png" title="<?= $reslbl ?> Form" onClick="document.location.href='?ip='+document.bld.ip.value+'&co='+document.bld.co.value+'&so='+document.bld.so.value;">
<?			
	}elseif($isadmin){
?>
<a href="<?= $self ?>.php?ip=<?= $ip ?>&co=<?= $co ?>&so=<?= $so ?>&sc=<?= $f ?>"><img src="img/16/geom.png" title="<?= $coplbl ?> <?= $srclbl ?> <?= $f ?>" <?= $wrw ?>></a>
<?php
	}
}

?>
</td><th align="right">
Typeoid</th><td>
<input type="text" name="to" value="<?= $to ?>" size="30" title="<?= $altlbl ?> device <?= $typlbl ?>" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.to.value);">
</td></tr>
<tr><th align="right">
<?= $typlbl ?></th><td>
<input type="text" name="typ" value="<?= $typ ?>" size="30" title="SNMP Sysobj <?= $typlbl ?>" onfocus="select();" onchange="update();">
</td><th align="right">
OS</th><td>
<select size="1" name="os" title="Operating System" onchange="update();">
<option value="other"><?= $mlvl['10'] ?>
<option value="Printer"<?= ($os == "Printer")?" selected":"" ?>>Printer
<option value="">--------
<option value="ArubaOS"<?=($os == "ArubaOS")?" selected":""?>>Aruba OS
<option value="">--------
<option value="EOS"<?= ($os == "EOS")?" selected":"" ?>>Enterasys OS
<option value="">--------
<option value="XOS"<?= ($os == "XOS")?" selected":"" ?>>Extreme OS
<option value="Xware"<?= ($os == "Xware")?" selected":"" ?>>ExtremeWare
<option value="">--------
<option value="IOS"<?= ($os == "IOS")?" selected":"" ?>>Cisco IOS
<option value="IOS-old"<?= ($os == "IOS-old")?" selected":"" ?>>IOS (<12.1)
<option value="IOS-css"<?= ($os == "IOS-css")?" selected":"" ?>>IOS (CSS)
<option value="IOS-pix"<?= ($os == "IOS-fw")?" selected":"" ?>>IOS (PIX)
<option value="IOS-fw"<?= ($os == "IOS-fw")?" selected":"" ?>>IOS (ASA)
<option value="IOS-fv"<?= ($os == "IOS-fv")?" selected":"" ?>>IOS (FWSM)
<option value="IOS-rtr"<?= ($os == "IOS-rtr")?" selected":"" ?>>IOS (Router)
<option value="IOS-wlc"<?= ($os == "IOS-wlc")?" selected":"" ?>>IOS (Wlan Ctrlr)
<option value="IOS-ap"<?= ($os == "IOS-ap")?" selected":"" ?>>IOS (Fat AP)
<option value="NXOS"<?= ($os == "NXOS")?" selected":"" ?>>Nexus OS
<option value="CatOS"<?= ($os == "CatOS")?" selected":"" ?>>Cisco CatOS
<option value="Cvpn"<?= ($os == "Cvpn")?" selected":"" ?>>Cisco vpn
<option value="">--------
<option value="FortiOS"<?=($os == "FortiOS")?" selected":""?>>FortiOS
<option value="">--------
<option value="Hirschmann"<?=($os == "Hirschmann")?" selected":""?>>Hirschmann
<option value="">--------
<option value="Comwar3"<?= ($os == "Comwar3")?" selected":"" ?>>HP Comware 3
<option value="Comware"<?= ($os == "Comware")?" selected":"" ?>>HP Comware
<option value="MSM"<?= ($os == "MSM")?" selected":"" ?>>HP MSM
<option value="ProCurve"<?= ($os == "ProCurve")?" selected":"" ?>>HP ProCurve
<option value="SROS"<?= ($os == "SROS")?" selected":"" ?>>HP SROS
<option value="TMS"<?= ($os == "TMS")?" selected":"" ?>>HP TMS
<option value="VC"<?= ($os == "VC")?" selected":"" ?>>HP VC
<option value="">--------
<option value="HuaweiVRP"<?= ($os == "HuaweiVRP")?" selected":"" ?>>Huawei VRP
<option value="">--------
<option value="Ironware"<?= ($os == "Ironware")?" selected":"" ?>>Ironware
<option value="">--------
<option value="JunOS"<?= ($os == "JunOS")?" selected":"" ?>>Juniper OS
<option value="NetScreen"<?= ($os == "NetScreen")?" selected":"" ?>>NetScreen OS
<option value="">--------
<option value="LANCOM"<?= ($os == "LANCOM")?" selected":"" ?>>LANCOM
<option value="">--------
<option value="Maipu"<?= ($os == "Maipu")?" selected":"" ?>>Maipu
<option value="">--------
<option value="Netgear"<?= ($os == "Netgear")?" selected":"" ?>>Netgear
<option value="">--------
<option value="Baystack"<?= ($os == "Baystack")?" selected":"" ?>>Nortel Legacy
<option value="Nortel"<?= ($os == "Nortel")?" selected":"" ?>>Nortel (CLI)
<option value="">--------
<option value="Omnistack"<?= ($os == "Omnistack")?" selected":"" ?>>Omnistack
<option value="">--------
<option value="ZDOS"<?= ($os == "ZDOS")?" selected":"" ?>>Ruckus ZDOS
<option value="">--------
<option value="ESX"<?= ($os == "ESX")?" selected":"" ?>>VMware ESX
</select>

SNMP <select size="1" name="ver" title="HC=64bit, MC=64bit & 32bit" onchange="update();">
<option value="1">v1
<option value="2"<?= $ver['2'] ?>>v2
<option value="2MC"<?= $ver['2MC'] ?>>v2MC
<option value="2HC"<?= $ver['2HC'] ?>>v2HC
</select>
<img src="img/16/walk.png" title="Test 64bit Counters" onClick="walk('1.3.6.1.2.1.31.1.1.1.6');">

</td></tr>
<tr><th align="right">
Icon</th><td>
<input type="text" name="ico" value="<?= $ico ?>" size="20" onfocus="select();" onchange="update();">
<img src="img/16/icon.png" onClick="window.open('inc/browse-icon.php','Icons','scrollbars=1,menubar=0,resizable=1,width=600,height=800');" title="<?= $sellbl ?> Icon">
</td><th align="right">
Bridge</th><td>
<select size="1" name="brg" title="<?= $fwdlbl ?> <?= $lstlbl ?>" onchange="update();" >
<option value=""> <?= $nonlbl ?>
<option value="normal"<?= ($bfd == "normal")?" selected":"" ?>>Normal
<option value="normalX"<?= ($bfd == "normalX")?" selected":"" ?>>Normal, IF indexed
<option value="qbri"<?= ($bfd == "qbri")?" selected":"" ?>>Q-bridge
<option value="qbriX"<?= ($bfd == "qbriX")?" selected":"" ?>>Q-bridge, IF indexed
<option value="VLX"<?= ($bfd == "VLX")?" selected":"" ?>>Vlan indexed
<option value="VXP"<?= ($bfd == "VXP")?" selected":"" ?>>VLX, w/o Port (N5K)
<option value="Aruba"<?= ($bfd == "Aruba")?" selected":"" ?>>Aruba Controller
<option value="CAP"<?= ($bfd == "CAP")?" selected":"" ?>>Cisco fat AP
<option value="WLC"<?= ($bfd == "WLC")?" selected":"" ?>>Cisco WLC Controller
<option value="MSM"<?= ($bfd == "MSM")?" selected":"" ?>>HP MSM Controller
<option value="WRT"<?= ($bfd == "WRT")?" selected":"" ?>>DD-WRT AP
</select>
<img src="img/16/walk.png" title="normal bridge-fwd" onClick="walk('1.3.6.1.2.1.17.4.3.1.2');">
<img src="img/16/walk.png" title="Q-bridge-fwd, 1st #=vlid (use normal if empty)" onClick="walk('1.3.6.1.2.1.17.7.1.2.2.1.2');">
<img src="img/16/walk.png" title="IF indexed, if numbers are different" onClick="walk('1.3.6.1.2.1.17.1.4.1.2');">
</td></tr>
<tr><th align="right">
<?= $sizlbl ?></th><td>
<input type="text" name="hgt" value="<?= $hgt ?>" size="2" onfocus="select();" onchange="update();"> RU

<select size="1" name="pem" title="POWER-ETHERNET-MIB <?= $opolbl ?>" onchange="update();" >
<option value=""> PEM->
<option value="P"<?= ($pem == "P")?" selected":"" ?>><?= $strlbl ?>
<option value="M"<?= ($pem == "M")?" selected":"" ?>>Module-<?= $strlbl ?>
<option value="S"<?= ($pem == "S")?" selected":"" ?>>Cisco-StackMib
</select>
<img src="img/16/walk.png" onClick="walk('1.3.6.1.2.1.105.1.3.1.1.2');" title="<?= $totlbl ?> PoE">
<img src="img/16/walk.png" onClick="walk('1.3.6.1.2.1.105.1.1.1.3');" title="IF PoE Admin <?= $stalbl ?>">
</td><th align="right">
ARP/ND</th><td>
<select size="1" name="arp" title="IPv4 & IPv6 <?= $adrlbl ?>" onchange="update();" >
<option value=""> <?= $nonlbl ?>
<option value="old"<?= ($arp == "old")?" selected":"" ?>>Old
<option value="phy"<?= ($arp == "phy")?" selected":"" ?>>IPv4/6
<option value="oldphy"<?= ($arp == "oldphy")?" selected":"" ?>>Old & IPv4/6
<option value="oldip6"<?= ($arp == "oldip6")?" selected":"" ?>>Old & IPv6
<option value="oldcie"<?= ($arp == "oldcie")?" selected":"" ?>>Old & Cisco
</select>
<img src="img/16/walk.png" title="IPv4 (ipNetToMedia)" onClick="walk('1.3.6.1.2.1.4.22.1.2');">
<img src="img/16/walk.png" title="IPv4/6 (ipNetToPhysical)" onClick="walk('1.3.6.1.2.1.4.35.1.4');">
<img src="img/16/walk.png" title="IPv6 (ipv6Addr)" onClick="walk('1.3.6.1.2.1.55.1.12.1.2');">
<img src="img/16/walk.png" title="Cisco (cInetNetToMedia)" onClick="walk('1.3.6.1.4.1.9.10.86.1.1.3.1.3');">
</td></tr>
<tr><th align="right">
<?= $serlbl ?></th><td>
<input type="text" name="sn" value="<?= $sn ?>" size="30" title="OID for SN#" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.sn.value);">
</td><th align="right">
<?= $dsclbl ?> <?= $prolbl ?></th><td>
<input type="text" name="dpr" value="<?= $dpr ?>" size="10" title="e.g. CDP|LLDPXN" onfocus="select();" onchange="update();">
<span class="<?= $modgroup[$self] ?>1"><img src="img/16/walk.png" title="LLDP" onClick="document.bld.dpr.value = 'LLDP';walk('1.0.8802.1.1.2.1.4.1.1');update();">
<img src="img/16/walk.png" title="+X index = ifdesc, +XA index = ifalias" onClick="document.bld.dpr.value = 'LLDPX';walk('1.0.8802.1.1.2.1.3.7.1.4');update();">
<img src="img/16/walk.png" title="+XN index = ifname" onClick="document.bld.dpr.value = 'LLDPXN';walk('1.0.8802.1.1.2.1.3.7.1.3');update();">
<img src="img/16/walk.png" title="LLDP <?= $neblbl ?> IP <?= $adrlbl ?>" onClick="walk('1.0.8802.1.1.2.1.4.2.1.3');">
</span>
<img src="img/16/walk.png" title="Cisco discovery protocol" onClick="document.bld.dpr.value = 'CDP';walk('1.3.6.1.4.1.9.9.23.1.2.1.1');update();">
<img src="img/16/walk.png" title="Foundry discovery protocol" onClick="document.bld.dpr.value = 'FDP';walk('1.3.6.1.4.1.1991.1.1.3.20.1.2.1.1');update();">
<img src="img/16/walk.png" title="Nortel discovery protocol" onClick="document.bld.dpr.value = 'NDP';walk('1.3.6.1.4.1.45.1.6.13.2.1.1');update();">
</td></tr>

<tr>
<th align="right">
Bootimage</th><td>
<input type="text" name="bi" value="<?= $bi ?>" size="30" title="OID for bootimage" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.bi.value);">
</td>
<th align="right"><?= $deslbl ?></th>
<td><input type="text" name="des" value="<?= $des ?>" size="30" title="Override standard sysdes for exotic devices/printers" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.des.value);"></td>
</tr>

<tr>
<th align="right">Vlan <?= $namlbl ?></th>
<td><input type="text" name="vln" value="<?= $vln ?>" size="30" title="OID for Vlan names, if available" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.vln.value);"></td>
<th align="right">Vlid <?= $idxlbl ?></th>
<td><input type="text" name="vnx" value="<?= $vnx ?>" size="30" title="Vlname to Vlid index, if not indexed with OID" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.vnx.value);"></td>
</tr>

<tr>
<th align="right"><?= $grplbl ?></th>
<td><input type="text" name="grp" value="<?= $grp ?>" size="30" title="Group can be VTP domain on Cisco devices" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.grp.value);"></td>
<th align="right"><?= $modlbl ?></th>
<td><input type="text" name="dmo" value="<?= $dmo ?>" size="30" title="Mode (e.g. client, server or transparent for VTP devices)" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.dmo.value);"></td>
</tr>

<tr>
<th align="right"><?= substr($cfglbl,0,4) ?> <?= $chglbl ?></th>
<td><input type="text" name="cfc" value="<?= $cfc ?>" size="30" title="<?= ($verb1)?"$laslbl $cfglbl $chglbl":"$cfglbl $chglbl $laslbl" ?>" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.cfc.value);"></td>
<th align="right"><?= substr($cfglbl,0,4) ?> <?= $wrtlbl ?></th>
<td><input type="text" name="cfw" value="<?= $cfw ?>" size="30" title="<?= ($verb1)?"$laslbl $cfglbl $wrtlbl":"$cfglbl $wrtlbl $laslbl" ?>" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.cfw.value);"></td>
</tr>

<tr class="<?= $modgroup[$self] ?>1"><th colspan="4">
<img src="img/16/bcnl.png" align="right" onClick="setint('0');" title="<?= $reslbl ?>">
<img src="img/16/idea.png" align="right" onClick="setint('1');" title="Standard dot3-MIB">
<img src="img/16/port.png" align="left">
Interfaces</th></tr>

<tr><th align="right">
<?= $idxlbl ?></th><td>
<input type="text" name="stx" value="<?= $stx ?>" size="3" onfocus="select();" onchange="update();"> <?= $sttlbl ?> &nbsp;
<input type="text" name="enx" value="<?= $enx ?>" size="3" onfocus="select();" onchange="update();"> <?= $endlbl ?>
</td></tr>
<tr><th align="right">
</th><td>
</td></tr>
<tr><th align="right">
IF <?= $namlbl ?></th><td>
<input type="text" name="ina" value="<?= $ina ?>" size="30" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.ina.value);">
</td><th align="right">
IF <?= $adrlbl ?></th><td>
<select size="1" name="ifa" title="IPv4 & IPv6 <?= $adrlbl ?>" onchange="update();" >
<option value=""> <?= $nonlbl ?>
<option value="old"<?= ($ifa == "old")?" selected":"" ?>>Old
<option value="adr"<?= ($ifa == "adr")?" selected":"" ?>>IPv4/6
<option value="oldadr"<?= ($ifa == "oldadr")?" selected":"" ?>>Old & IPv4/6
<option value="oldip6"<?= ($ifa == "oldip6")?" selected":"" ?>>Old & IPv6
<option value="oldcie"<?= ($ifa == "oldcie")?" selected":"" ?>>Old & Cisco
</select>
<img src="img/16/walk.png" title="Old (ifAddr)" onClick="walk('1.3.6.1.2.1.4.20.1.2');">
<img src="img/16/walk.png" title="IPv4/6 (ifAddress)" onClick="walk('1.3.6.1.2.1.4.34.1.5');">
<img src="img/16/walk.png" title="IPv6 (ipv6Addr)" onClick="walk('1.3.6.1.2.1.55.1.8.1.2');">
<img src="img/16/walk.png" title="Cisco (IetfIpMIB)" onClick="walk('1.3.6.1.4.1.9.10.86.1.1.2.1.3');">
</td></tr>
<tr><th align="right">
IF Alias</th><td>
<input type="text" name="ial" value="<?= $ial ?>" size="30" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.ial.value);">
</td><th align="right">
Alias <?= $idxlbl ?></th><td>
<input type="text" name="iax" value="<?= $iax ?>" size="30" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.iax.value);">
</td></tr>
<tr><th align="right">
IF Duplex</th><td>
<input type="text" name="idu" value="<?= $idu ?>" size="30" title="dot3StatsDuplexStatus or MAU(1.3.6.1.2.1.26.2.1.1.11) or enterprise" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.idu.value);">
</td><th align="right">
Duplex <?= $idxlbl ?></th><td>
<input type="text" name="idx" value="<?= $idx ?>" size="30" title="Only set, if index is not the same as MIB2 IFindex" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.idx.value);">
</td></tr>
<tr><th align="right">
Duplex <?= substr($vallbl,0,3) ?></th><td>
<input type="text" name="hdv" value="<?= $hdv ?>" size="2" title="half-duplex <?= $vallbl ?>" onfocus="select();" onchange="update();"> Half &nbsp;
<input type="text" name="fdv" value="<?= $fdv ?>" size="2" title="full-duplex <?= $vallbl ?>" onfocus="select();" onchange="update();"> Full
</td><th align="right">
Broadcast <?= substr($inblbl,0,3) ?></th><td>
<input type="text" name="brc" value="<?= $brc ?>" size="30" title="Broadcasts entering device" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.brc.value);">
</td></tr>
<tr><th align="right">
<?= $dcalbl ?> <?= substr($inblbl,0,3) ?></th><td>
<input type="text" name="idi" value="<?= $idi ?>" size="30" title="In discard is usually in the standard IF-mib..." onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.idi.value);">
</td><th align="right">
<?= $dcalbl ?> <?= substr($oublbl,0,3) ?></th><td>
<input type="text" name="odi" value="<?= $odi ?>" size="30" title="...out as well, but this supports the exotic implementations" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.odi.value);">
</td></tr>
<tr><th align="right">
IF Vlan</th><td>
<input type="text" name="ivl" value="<?= $ivl ?>" size="30" title="dot1qPvid or enterprise" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.ivl.value);">
</td><th align="right">
Vlan <?= $idxlbl ?></th><td>
<input type="text" name="ivx" value="<?= $ivx ?>" size="30" title="Only set, if index is not the same as MIB2 IFindex" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.ivx.value);">
</td></tr>
<tr><th align="right">
IF Power</th><td>
<input type="text" name="ipw" value="<?= $ipw ?>" size="30" title="PoE switch should reveal actual power delivery here" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.ipw.value);">
</td><th align="right">
Power <?= $idxlbl ?></th><td>
<input type="text" name="ipx" value="<?= $ipx ?>" size="30" title="Alternatively use a # (e.g. 1000) to map last 2 keys to indexes like 1001" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.ipx.value);">
</td></tr>

<tr class="<?= $modgroup[$self] ?>1"><th colspan="4">
<img src="img/16/bcnl.png" align="right" onClick="setmod('0');" title="<?= $reslbl ?>">
<img src="img/16/idea.png" align="right" onClick="setmod('1');" title="Standard Entity-MIB">
<img src="img/16/print.png" align="right" onClick="setmod('2');" title="Printsupplies MIB">
<img src="img/16/cubs.png" align="left">
Modules</th></tr>

<tr><th align="right">
Slot</th><td>
<input type="text" name="msl" value="<?= $msl ?>" size="30" title="This OID is required, if you want to track modules" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.msl.value);">
</td><th align="right">
</th><td>
</td></tr>
<tr><th align="right">
<?= $clalbl ?> OID</th><td>
<input type="text" name="mcl" value="<?= $mcl ?>" size="30" title="Classes identify, what an actual module is" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.mcl.value);">
</td><th align="right">
<?= $clalbl ?> <?= $vallbl ?></th><td>
<input type="text" name="mcv" value="<?= $mcv ?>" size="10" title="The actual value (e.g. Entity-MIB modules use 9" onfocus="select();" onchange="update();">
</td></tr>
<tr><th align="right">
<?= $deslbl ?></th><td>
<input type="text" name="mde" value="<?= $mde ?>" size="30" title="Module description" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.mde.value);">
</td><th align="right">
Hardware</th><td>
<input type="text" name="mhw" value="<?= $mhw ?>" size="30" title="Module hardware version" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.mhw.value);">
</td></tr>
<tr><th align="right">
Software</th><td>
<input type="text" name="msw" value="<?= $msw ?>" size="30" title="Module software version" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.msw.value);">
</td><th align="right">
Firmware</th><td>
<input type="text" name="mfw" value="<?= $mfw ?>" size="30" title="Module firmware version" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.mfw.value);">
</td></tr>
<tr><th align="right">
<?= $serlbl ?></th><td>
<input type="text" name="msn" value="<?= $msn ?>" size="30" title="Module serial numbers" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.msn.value);">
</td><th align="right">
Model</th><td>
<input type="text" name="mmo" value="<?= $mmo ?>" size="30" title="Sometimes an additional model# can be fetched" onfocus="select();" onchange="update();">
<img src="img/16/walk.png" onClick="walk(document.bld.mmo.value);">
</td></tr>

<tr class="<?= $modgroup[$self] ?>1"><th colspan="4">
<img src="img/16/bcnl.png" align="right" onClick="setrrd('0');" title="<?= $reslbl ?>">
<img src="img/16/idea.png" align="right" onClick="setrrd('1');" title="Possible Standard OIDs">
<img src="img/16/grph.png" align="left">
RRD Graphing</th></tr>

<tr><th align="right">
% CPU</th><td>
<input type="text" name="cpu" value="<?= $cpu ?>" size="22" title="Try to use a long average (e.g. 5min)" onfocus="select();" onchange="update();">
 * <input type="text" name="cmu" value="<?= $cmu ?>" size="4" title="x10, x0.1..." onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.cpu.value);">
</td><th align="right">
Mem <?= $frelbl ?></th><td>
<input type="text" name="mcp" value="<?= $mcp ?>" size="22" title="Available memory" onfocus="select();" onchange="update();">
 * <input type="text" name="mmu" value="<?= $mmu ?>" size="4" title="x10, x0.1..." onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.mcp.value);">
</td></tr>
<tr><th align="right">
<?= $tmplbl ?></th><td>
<input type="text" name="tmp" value="<?= $tmp ?>" size="22" title="Could be used for other values, if temperature is not supported" onfocus="select();" onchange="update();">
 * <input type="text" name="tmu" value="<?= $tmu ?>" size="4" title="x10, x0.1..." onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.tmp.value);">
</td><th align="right">
<input type="text" name="cul" value="<?= $cul ?>" size="12" title="Custom Label;C(ounter)|G(auge);Unit" onfocus="select();" onchange="update();"></th><td>
<input type="text" name="cuv" value="<?= $cuv ?>" size="30" title="Custom Gauge OID" onfocus="select();" onchange="update();">
<img src="img/16/brgt.png" onClick="get(document.bld.cuv.value);">
</td></tr>

</table>
</form>

</td>
</tr>
<tr class="<?= $modgroup[$self] ?>2"><th>
<?php  if( $isadmin and $ip) { ?>
<div style="float:right;margin:2px 2px">
<form method="post" name="nedi" action="System-NeDi.php">
<input type="hidden" name="mde" value="d">
<input type="hidden" name="sed" value="a">
<input type="hidden" name="opt" value="<?= $ip ?>">
<input type="image" src="img/16/radr.png" value="Submit" title="<?= $dsclbl ?>">
</form>
</div>
<?}?>

<div style="float:right;margin:2px 2px">
<a href="mailto:def@nedi.ch?subject=<?= $so ?>.def&body=<?= $mailbody ?>"><img src="img/16/mail.png"  title="<?= ($verb1)?"$sndlbl NeDi":"NeDi  $sndlbl" ?>"></a>
</div>

<form method="post" name="gen" action="<?= $self ?>.php">
<input type="button" value="<?= $updlbl ?>" name="up" onClick="update();" title="<?= $updlbl ?> .def <?= $tim[n] ?>">
- <input type="text" name="so" value="<?= $so ?>" size="24" title="Filename">.def
<?php  if( $isadmin) { ?>
<input type="submit" value="<?= $wrtlbl ?>" name="wr" title="<?= $wrtlbl ?> .def <?= $fillbl ?>" disabled="true" <?= $wrw ?>>
<?}?>
<p>
<textarea rows="24" name="def" cols="100" onChange="dis='1';document.gen.wr.disabled=false;alert('Controls disabled!');"><?= $def ?></textarea>
</td></tr>
</form>
</th></tr></table>

<?php
include_once ("inc/footer.php");
?>
