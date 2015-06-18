<?php
# Program: Nodes-Create.php
# Programmer: Remo Rickli

error_reporting(E_ALL ^ E_NOTICE);

$printable = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");

$_GET = sanitize($_GET);
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$svm = isset($_GET['svm']) ? $_GET['svm'] : "";
$chw = isset($_GET['chw']) ?"checked" : "";
$nvm = isset($_GET['nvm']) ? $_GET['nvm'] : "";

$cpu = isset($_GET['cpu']) ? $_GET['cpu'] : 1;
$mem = isset($_GET['mem']) ? $_GET['mem'] : 256;
$hdd = isset($_GET['hdd']) ? $_GET['hdd'] : 8;

$dly = isset($_GET['dly']) ? $_GET['dly'] : 0;

$vnc = isset($_GET['vnc']) ? $_GET['vnc'] : 0;
$vnp = isset($_GET['vnp']) ? $_GET['vnp'] : "";
$sxg = isset($_GET['sxg']) ?"checked" : "";

$iso = isset($_GET['iso']) ? $_GET['iso'] : "";

?>
<h1>Nodes Create</h1>

<form method="get" action="<?= $self ?>.php" name="mkvm">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top">

<h3><?= $dstlbl ?></h3>
<img src="img/16/dev.png" title="Hypervisor">
<select size="1" name="dev" onchange="this.form.submit();">
<option value=""><?= $sellbl ?> ->
<?php
$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','device,devip,cliport,login','','',array('devos','cliport'),array('=','>'),array('ESX','0'), array('AND') );
$res	= DbQuery($query,$link);
if($res){
	while( $d = DbFetchRow($res) ){
		if($dev == $d[0]){
			$sel = "selected";
			$dip = long2ip($d[1]);
			$dpo = $d[2];
			$dlg = $d[3];
		}else{
			$sel = "";
		}
		echo "<option value=\"$d[0]\" $sel>$d[0]\n";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
	die ( mysql_error() );
}
?>
</select>
<p>
<img src="img/16/cubs.png" title="<?= $srclbl ?> VM">
<select size="1" name="svm" onchange="this.form.submit();">
<option value=""><?= $coplbl ?> ->
<?php
if($dev){
	$query	= GenQuery('modules','s','*','','',array('device'),array('='),array($dev) );
	$res	= DbQuery($query,$link);
	if($res){
		while( $m = DbFetchRow($res) ){
			if($svm == $m[8]){
				$sel = "selected";
				$sna = $m[1];
				$svx = $m[3];
				$cpu = ($chw and $m[4])?$m[4]:$cpu;
				$mem = ($chw and $m[6])?$m[6]:$mem;
				$nvm = ($nvm)?$nvm:"$m[1]-new";
			}else{
				$sel = "";
			}
			echo "<option value=\"$m[8]\" $sel>$m[1]\n";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
		die ( mysql_error() );
	}
}
?>
</select>
<input type="checkbox" name="chw" <?= $chw ?> title="<?= $coplbl ?> #CPU & Mem" onchange="this.form.submit();">
<p>
<img src="img/16/trgt.png" title="<?= $tgtlbl ?> VM">
<input type="text" name="nvm" size="20" value="<?= $nvm ?>" >

</td>
<td valign="top">

<h3>Node HW</h3>
<img src="img/16/cpu.png" title="# CPUs">
<input type="number" min="1" max="8" name="cpu" value="<?= $cpu ?>" size="3">

<p>
<img src="img/16/mem.png" title="Mem <?= $sizlbl ?>">
<input type="number" min="256" max="65535" step="256" name="mem" value="<?= $mem ?>" size="6">
<p>
<img src="img/16/db.png" title="HDD <?= $sizlbl ?>">
<select size="1" name="hdd">
<option value="1">1Gb
<option value="4"<?= ( ($hdd == "4")?" selected":"") ?>>4Gb
<option value="40"<?= ( ($hdd == "40")?" selected":"") ?>>40Gb
<option value="80"<?= ( ($hdd == "80")?" selected":"") ?>>80Gb
<option value="160"<?= ( ($hdd == "160")?" selected":"") ?>>160Gb
<option value="250"<?= ( ($hdd == "250")?" selected":"") ?>>250Gb
<option value="500"<?= ( ($hdd == "500")?" selected":"") ?>>500Gb
</select>

</td>
<td valign="top">

<h3><?= $srvlbl ?></h3>
<img src="img/16/cog.png" title="Boot <?= $latlbl ?>">
<input type="number" min="0" max="8" name="dly" value="<?= $dly ?>" size="3">
<p>
<img src="img/16/node.png" title="VNC Port/Password">
<input type="number" min="0" max="99" name="vnc" value="<?= $vnc ?>" size="3">
<input type="text" name="vnp" size="12" value="<?= $vnp ?>" >
<input type="checkbox" name="sxg" <?= $sxg ?> title="SXGA Screen">
<p>
<img src="img/16/cbox.png" title="ISO <?= $fillbl ?>">
<input type="text" name="iso" size="32" value="<?= $iso ?>" >

</td>
<th width="80">

<input type="submit" name="sho" value="<?= $sholbl ?>">
<p>
<input type="submit" name="add" value="<?= $addlbl ?>">

</th>
</tr></table></form>

<p>
<?php
if($dev and $sna){
	$parr = explode('/', $svx);
	array_pop($parr);
	array_pop($parr);
	$vmpath = implode('/',$parr)."/".$nvm;

	$cmds  = "<PHP? exit; ?>\n";

	$cmds .= "mkdir $vmpath\n";
	$cmds .= "vmkfstools -c ${hdd}g -d thin $vmpath/$nvm.vmdk\n";

	$cmds .= "echo config.version = \\\"8\\\" > $vmpath/$nvm.vmx\n";
	$cmds .= "echo virtualHW.version = \\\"7\\\" >> $vmpath/$nvm.vmx\n";

	$cmds .= "echo scsi0.present = \\\"TRUE\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo scsi0.sharedBus = \\\"none\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo scsi0.virtualDev = \\\"lsilogic\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo scsi0:0.present = \\\"TRUE\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo scsi0:0.fileName = \\\"$nvm.vmdk\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo scsi0:0.deviceType = \\\"scsi-hardDisk\\\" >> $vmpath/$nvm.vmx\n";

	$cmds .= "echo ethernet0.present = \\\"TRUE\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo ethernet0.allowGuestConnectionControl = \\\"FALSE\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo ethernet0.networkName = \\\"VM Network\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo ethernet0.addressType = \\\"generated\\\" >> $vmpath/$nvm.vmx\n";

	$cmds .= "echo guestOS = \\\"other\\\" >> $vmpath/$nvm.vmx\n";

	$cmds .= "echo memsize = \\\"$mem\\\" >> $vmpath/$nvm.vmx\n";
	$cmds .= "echo numvcpus = \\\"$cpu\\\" >> $vmpath/$nvm.vmx\n";

	if($sxg){
		$cmds .= "echo svga.maxWidth =  \\\"1280\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo svga.maxHeight =  \\\"1024\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo svga.vramSize =  \\\"5242880\\\" >> $vmpath/$nvm.vmx\n";
	}

	if($iso){
		$cmds .= "echo ide1:0.present = \\\"TRUE\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo ide1:0.fileName = \\\"$iso\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo ide1:0.deviceType = \\\"cdrom-image\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo ide1:0.startConnected = \\\"TRUE\\\" >> $vmpath/$nvm.vmx\n";
	}

	if($vnc){
		$cmds .= "echo remotedisplay.vnc.port = \\\"".($vnc+5900)."\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo remotedisplay.vnc.enabled = \\\"TRUE\\\" >> $vmpath/$nvm.vmx\n";
		$cmds .= "echo remotedisplay.vnc.password = \\\"$vnp\\\" >> $vmpath/$nvm.vmx\n";
	}

	if($dly){
		$cmds .= "echo bios.bootDelay = \\\"".($dly*1000)."\\\" >> $vmpath/$nvm.vmx\n";
	}

	$cmds .= "vim-cmd solo/registervm $vmpath/$nvm.vmx $nvm\n";

	echo "<h3>$dev ".DevCli($dip,$dpo,1)."</h3>\n";

	$cred = ( strstr($guiauth,'-pass') )?"$_SESSION[user] $pwd":"$dlg dummy";
	$cred = addcslashes($cred,';$!');
	if($_GET['add']){
		$fd =  @fopen("log/cmd_$_SESSION[user].php","w") or die ("$errlbl $wrtlbl log/cmd_$_SESSION[user]");
		fwrite($fd, $cmds);
		fclose($fd);

		$out  = system("perl $nedipath/inc/devwrite.pl $nedipath $dip $dpo $cred ESX log/cmd_$_SESSION[user]", $err);
		echo "<iframe style=\"display:block;\" class=\"textpad warn code\" src=\"log/cmd_$_SESSION[user]-$dip.log\"></iframe>";

		$cstr = preg_replace('/\n|"|\'/',' ',$cmds);
		if( strlen($cstr) > 40 ){$cstr = substr( $cstr,0,40)."...";}
		$msg  = "User $_SESSION[user] wrote $cstr";
		if($err){
			$lvl = 150;
			$msg = "User $_SESSION[user] created VM $nvm causing errors";
		}else{
			$lvl = 100;
			$msg = "User $_SESSION[user] created VM $nvm successfully";
			
			echo "<p>$dev $stalbl: <a href=\"Devices-Status.php?dev=$dev\"><img src=\"img/16/dev.png\" title=\"Devices-Status\"></a>";
		}
		$query = GenQuery('events','i','','','',array('level','time','source','info','class','device'),array(),array($lvl,time(),$dev,$msg,'usrd',$dev) );
		if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}
	}elseif($_GET['sho']){
		echo "<div class=\"textpad txtb code good\">$cmds</div>\n";
	}else{
		echo "<div class=\"textpad txtb\">$tim[n] $sellbl: $sholbl||$addlbl</div>";
	}
}else{
	echo "<div class=\"textpad alrm drd\">This is work in progress and intended for my ESXi! It may not work properly in other environments yet...</div>\n";
}

include_once ("inc/footer.php");

?>
