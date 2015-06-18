<?php
# Program: Devices-Doctor
# Programmer: Remo Rickli

$printable = 0;
$exportxls = 0;

if( isset($_GET['dev']) ){$printable = 1;}

$getif = 0;
$setif = 0;
$iscfg = 0;
$devln = 0;
$lstyl = "";

$ipadd = array();
$ifdsc = array();
$iphlp = array();
$ifmod = array();

$snmp  = array();
$srv   = array();
$log   = array();

include_once ("inc/header.php");
include_once ("inc/libdev.php");

$_GET  = sanitize($_GET);
$dev = isset( $_GET['dev']) ? $_GET['dev'] : "";
if(!$dev){
	$_POST = sanitize($_POST);
	$sln = isset( $_POST['sln']) ? $_POST['sln'] : "";
	$bcw = isset( $_POST['bcw']) ? $_POST['bcw'] : 10;
	$dev = isset( $_POST['dev']) ? $_POST['dev'] : "";
}

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
?>
<h1>Device Doctor</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="POST" action="<?= $self ?>.php" enctype="multipart/form-data">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>Cfg #<br>
<input type="checkbox" name="sln" <?= ($sln)?"checked":"" ?> ></td>

<th>Bcast <?= $mlvl[150] ?><br>
<input type="text" name="bcw" value="<?= $bcw ?>" size="2"> %</th>

<th>
Tech file<br>
<input name="tef" type="file" size="30" accept="text/*">
</td>

<th><?= $cfglbl ?><br>
<select size="1" name="dev" onchange="this.form.submit();">
<option value=""><?= $sellbl ?> ->
<?php
$query	= GenQuery('configs','s','device','device','',array(),array(),array(),array(),'LEFT JOIN devices USING (device)');
$res	= DbQuery($query,$link);
if($res){
	while( ($c = DbFetchRow($res)) ){
		echo "<option value=\"$c[0]\"";
		if($c[0] == $dev){
			echo "selected";
		}
		echo ">$c[0]\n";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
}

?>
</select>
</th>

<th width="80"><input type="submit" value="<?= $sholbl ?>"></th>
</table>
</form>

<?php
}
if($dev){
	$query	= GenQuery('configs','s','config,devos','','',array('device'),array('='),array($dev),array(),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if (DbNumRows($res) != 1) {
		echo "<h4>$dev: $nonlbl</h4>";
		DbFreeResult($res);
		die;
	}
	$cfg = DbFetchRow($res);
	DbFreeResult($res);
	if($debug){	echo "<div class=\"textpad code warn\">$cfg[0]</div>\n";}
?>
<h2>
<a href="Devices-Config.php?shc=<?= urlencode($dev) ?>"><img src="img/16/conf.png" title="<?= $cfglbl ?>"></a>
<?= $dev ?> <?= $cfglbl ?> <?= $sumlbl ?></h2>
<?php
	foreach ( explode("\n",$cfg[0]) as $l ){
		if( preg_match("/^interface /",$l) or $cfg[1] == "ProCurve" and preg_match("/^vlan /",$l) ){
			$i = preg_replace("/^interface\s*(.*)$/",'$1',$l);
			$if[] = $i;
		}elseif( preg_match("/^(!|#|exit)/",$l) ){
			$i = "";
		}elseif($i and preg_match("/^\s+ip address/",$l) ){
			$ipadd[$i] = preg_replace("/^\s+ip address\s+(.*)$/",'$1',$l);
		}elseif($i and preg_match("/^\s+(description|name)/",$l) ){
			$ifdsc[$i] = preg_replace("/^\s+(description|name)\s+(.*)$/",'$2',$l);
		}elseif($i and preg_match("/^\s+ip helper-address/",$l) ){
			$iphlp[$i] = preg_replace("/^\s+ip helper-address\s+(.*)$/",'$1',$l);
		}elseif($i and preg_match("/^\s+(switchport mode|port link-type)/",$l) ){
			$ifmod[$i] = preg_replace("/^\s+(switchport mode|port link-type)\s+(.*)$/",'$2',$l);
		}elseif($i and preg_match("/vrf forwarding|vpn-instance/",$l) ){
			$l = preg_replace("/.*(vrf forwarding|vpn-instance)\s*(.*)$/",'$2',$l);
			$ifvpn[$i] = "<a href=\"Topology-Networks.php?in[]=vrfname&op[]==&st[]=".urlencode($l)."\">$l</a>";
		}
		
		if( preg_match("/^logging|info-center loghost/",$l) ){
			$log[] = $l;
		}elseif( preg_match("/^\s?snmp-(agent|server)/",$l) ){
			$snmp[] = $l;
		}elseif( preg_match("/^service|server enable/",$l) ){
			$srv[] = $l;
		}elseif( preg_match("/^ip http server/",$l) ){
			$srv[] = $l;
		}elseif( preg_match("/^(no )?spanning-tree|^ stp/",$l) ){
			$stp[] = $l;
		}
	}
	echo "<h3>Interfaces</h3>";
	echo "<table class=\"content\"><tr class=\"$modgroup[$self]2\"><td>$namlbl</td><td>IP $adrlbl</td><td>Alias</td><td>$typlbl</td><td>IP Helper</td><td>VRF</td></tr>";
	foreach($if as $i){
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		TblRow($bg);
		echo "<td>$i</td><td class=\"blu\">$ipadd[$i]</td><td class=\"gry\">$ifdsc[$i]</td>";
		echo "<td class=\"grn\">$ifmod[$i]</td><td class=\"gry\">$ifhlp[$i]</td><td>$ifvpn[$i]</td></tr>";
	}
	echo "</table>";

	echo "<h3>Spanning-Tree</h3>";
	echo "<div class=\"textpad code txta\">\n";
	foreach($stp as $i){
		echo "$i\n";
	}
	echo "</div>\n";

	echo "<h3>SNMP</h3>";
	echo "<div class=\"textpad code txta\">\n";
	foreach($snmp as $i){
		echo "$i\n";
	}
	echo "</div>\n";

	echo "<h3>$srvlbl</h3>";
	echo "<div class=\"textpad code txta\">\n";
	foreach($srv as $i){
		echo "$i\n";
	}
	echo "</div>\n";

	echo "<h3>Logging</h3>";
	echo "<div class=\"textpad code txta\">\n";
	foreach($log as $i){
		echo "$i\n";
	}
	echo "</div>\n";

}elseif (array_key_exists('tef',$_FILES) and file_exists($_FILES['tef']['tmp_name'])) {
	$lines = file($_FILES['tef']['tmp_name']);
	foreach ($lines as $line_num => $l) {
		$line = rtrim($l);
		if( preg_match("/^(-*\s|->\s)?(show|walkmib) /",$line) ){
			echo "<h3 class=\"imga\">$line</h3>\n";
			if( preg_match("/^show interfaces brief$/",$line) ){
				$getif = 1;
			}else{
				$getif = 0;
			}
			if( preg_match("/^show interfaces( config)?$|^show spanning-tree( config)?$/",$line) ){
				$setif = 1;
			}else{
				$setif = 0;
			}
			if( preg_match("/show (config|running-config)/",$line) ){
				$iscfg = 1;
			}else{
				$iscfg = 0;
			}
		}elseif($iscfg){										# Highlight Config
			if(!preg_match("/^$|Building configuration/",$line)){					# Ignore those lines to be consistent with Devices-Config
				if($sln) $devln++;
				echo Shoconf($line,0,$devln);
			}
		}elseif($getif and preg_match("/^\s+[A-L]{0,1}[0-9]{1,2}/",$line,$ifs) ){			# POST IF status on HP from "sh int brief"
			if( preg_match("/\sDown\s/",$line) ){
				$ifstat[$ifs[0]] = 0;
				echo "<span class=\"drd\">$line</span>\n";
			}else{
				$ifstat[$ifs[0]] = 1;
				echo "<span class=\"olv\">$line</span>\n";
			}

		}elseif($setif and preg_match("/^\s+[A-L]{0,1}[0-9]{1,2}/",$line,$ifs) ){			# Set IF status on HP in according sections
			if($ifstat[$ifs[0]]){
				echo "<span class=\"olv\">$line</span>\n";
			}else{
				echo "<span class=\"drd\">$line</span>\n";
			}

		}elseif( preg_match("/^[I] [0-9]/",$line) ){							# HP syslog entries
			$lstyl = "blu";
			echo "<span class=\"blu\" title=\"Info\">$line</span>\n";
		}elseif( preg_match("/^[W] [0-9]/",$line) ){
			$lstyl = "prp";
			echo "<span class=\"prp\" title=\"Warning\">$line</span>\n";
		}elseif( preg_match("/^[M] [0-9]/",$line) ){
			$lstyl = "red warn";
			echo "<span class=\"red warn\" title=\"Major!\">$line</span>\n";

		}elseif( preg_match("/^\s+Bcast\/Mcast Rx/",$line) ){						# Check Excess Bcast on HP
			$bval = preg_split("/\s+/",str_replace(",","",$line) );
			$uval = preg_split("/\s+/",str_replace(",","",$lines[($line_num-1)]) );
			if($uval[4]){
				$relbc = round($bval[4] / $uval[4] * 100);
				if($relbc > $bcw){
					$bcstat = "red warn";
				}else{
					$bcstat = "grn";
				}
			}else{
				$relbc = 0;
				$bcstat = "gry";				
			}
			echo "<span class=\"$bcstat\" title=\"Rx Broadcasts = $relbc%\">$line</span>\n";

		}elseif( preg_match("/^\s+Received ([0-9]+) broadcasts/",$line, $bval) ){			# Check Excess Bcast on Cisco
			preg_match("/^\s+([0-9]+) packets input/",$lines[($line_num-1)],$uval );
			if($uval[1]){
				$relbc = round($bval[1] / $uval[1] * 100);
				if($relbc > $bcw){
					$bcstat = "red warn";
				}else{
					$bcstat = "grn";
				}
			}else{
				$relbc = 0;
				$bcstat = "gry";				
			}
			echo "<span class=\"$bcstat\" title=\"Rx Broadcasts = $relbc%\">$line</span>\n";

		}elseif( preg_match("/^\s+(Link )?Status\s|line protocol/",$line) ){
			if( preg_match("/: Down$| down[, $]/",$line) ){
				echo "<span class=\"drd\">$line</span>\n";
			}elseif( preg_match("/: Up$| up[, $]/",$line) ){
				echo "<span class=\"olv\">$line</span>\n";
			}else{
				echo "<span class=\"blu\">$line</span>\n";
			}

		}elseif( preg_match("/^ifInDiscards/",$line) ){
			$val = preg_split("/\s+/",str_replace(",","",$line) );
			if($val[2] > 1){
				echo "<span class=\"red warn\" title=\"Congestion,FCS?\">$line</span>\n";
			}else{
				echo "<span class=\"grn\">$line</span>\n";
			}

		}elseif( preg_match("/^ifOutDiscards/",$line) ){
			$val = preg_split("/\s+/",str_replace(",","",$line) );
			if($val[2] > 1){
				echo "<span class=\"red warn\" title=\"Bad cable?\">$line</span>\n";
			}else{
				echo "<span class=\"grn\">$line</span>\n";
			}

		}elseif( preg_match("/^MAC moves/",$line) ){
			$val = preg_split("/\s+/",$line);
			if($val[2] > 10 or $val[3] > 10){
				echo "<span class=\"red warn\" title=\"Loop?\">$line</span>\n";
			}else{
				echo "<span class=\"grn\">$line</span>\n";
			}

		}elseif( preg_match("/^\s+Topology Change Count/",$line) ){
			$val = preg_split("/\s+/",$line);
			if($val[5] > 10){
				echo "<span class=\"red warn\" title=\"Eradic spanningtree?\">$line</span>\n";
			}else{
				echo "<span class=\"grn\">$line</span>\n";
			}

		}elseif( preg_match("/^FAILED/",$line) ){
			echo "<span class=\"red\" title=\"HW Failure!\">$line</span>\n";
		}elseif($lstyl){
			echo "<span class=\" $lstyl\">$line</span>\n";
			$lstyl = "";
		}else{
			echo "$line\n";
		}
	}
}
echo "</div><br>";

include_once ("inc/footer.php");
?>
