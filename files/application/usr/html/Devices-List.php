<?php
# Program: Devices-List.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 1;

error_reporting(1);
snmp_set_quick_print(1);
snmp_set_oid_numeric_print(1);
snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");
include_once ("inc/libsnmp.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
if($_SESSION['opt'] and !$ord and $in[0]) $ord = $in[0];

$map = isset($_GET['map']) ? "checked" : "";
$lim = isset($_GET['lim']) ? preg_replace('/\D+/','',$_GET['lim']) : $listlim;

$mon = isset($_GET['mon']) ? 1 : 0;
$del = isset($_GET['del']) ? $_GET['del'] : "";

if( isset($_GET['col']) ){
	$col = $_GET['col'];
	if($_SESSION['opt']) $_SESSION['devcol'] = $col;
}elseif( isset($_SESSION['devcol']) ){
	$col = $_SESSION['devcol'];
}else{
	$col = array('device','devip','serial','location','contact','lastdis');
}

$cols = array(	"device"=>"Device $namlbl",
		"imgNS"=>$imglbl,
		"devip"=>"$manlbl IP",
		"origip"=>"$orilbl IP",
		"serial"=>"$serlbl",
		"type"=>"Device $typlbl",
		"services"=>$srvlbl,
		"description"=>$deslbl,
		"devos"=>"Device OS",
		"bootimage"=>"Bootimage",
		"location"=>$loclbl,
		"contact"=>$conlbl,
		"devgroup"=>$grplbl,
		"devmode"=>$modlbl,
		"snmpversion"=>"SNMP $verlbl",
		"readcomm"=>"$realbl Community",
		"writecomm"=>"$wrtlbl Community",
		"login"=>"Login",
		"icon"=>"Icon",
		"cliport"=>"CLI $porlbl",
		"firstdis"=>"$fislbl $dsclbl",
		"lastdis"=>"$laslbl $dsclbl",
		"cpu"=>"CPU $lodlbl",
		"memcpu"=>"$memlbl $frelbl",
		"temp"=>$tmplbl,
		"cusvalue"=>"$cuslbl $vallbl",
		"cuslabel"=>"$cuslbl $titlbl",
		"sysobjid"=>"SysObjID",
		"devopts"=>$opolbl,
		"size"=>$sizlbl,
		"stack"=>"Stack",
		"maxpoe"=>"$maxlbl PoE",
		"totpoe"=>"$totlbl PoE",
		"cfgchange"=>"$cfglbl $chglbl",
		"cfgstatus"=>"$cfglbl $stalbl",
		"time"=>"$cfglbl $buplbl",
		"test"=>"Monitoring $tstlbl",
		"poNS"=>$poplbl,
		"logNS"=>$loglbl,
		"iiNS"=>"$acslbl $porlbl $frelbl",
		"stpNS"=>"$rltlbl STP",
		"gfNS"=>"$gralbl"
		);

$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);							# Above print-header!
?>
<h1>Device <?= $lstlbl ?></h1>

<?php  if( !isset($_GET['print']) and !isset($_GET['xls']) ) { ?>

<form method="get" name="list" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td>

<?PHP Filters(); ?>

</td>
<th valign="top">

<h3><?= $fltlbl ?></h3>
<a href="?in[]=snmpversion&op[]=>&st[]=0&lim=<?= $listlim ?>"><img src="img/16/dev.png" title="SNMP Devices"></a>
<a href="?in[]=cliport&op[]=%3D&st[]=1&co[]=&in[]=lastdis&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&co[]=&in[]=device&op[]=~&st[]=&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/kons.png" title="CLI <?= $errlbl ?>"></a>
<a href="?in[]=cfgstatus&op[]=~&st[]=[ECO]&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=lastdis&col[]=cfgstatus&col[]=time&ord=time"><img src="img/16/conf.png" title="<?= $cfglbl ?> <?= $errlbl ?>"></a>
<a href="?in[]=test&op[]==&st[]=NULL&co[]=&in[]=device&op[]=~&st[]=&col[]=device&col[]=devip&col[]=serial&col[]=location&col[]=contact&col[]=lastdis&col[]=test"><img src="img/16/bino.png" title="<?= $notlbl ?> Monitor"></a>
<br>
<a href="?in[]=firstdis&op[]=~&st[]=&co[]=%3D&in[]=lastdis&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/flas.png" title="<?= $faslbl ?> Devices"></a>
<a href="?in[]=lastdis&op[]=<&st[]=<?= time()-2*$rrdstep ?>&col[]=device&col[]=devip&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&ord=lastdis+desc"><img src="img/16/date.png" title="Devices <?= $undlbl ?>"></a>
<a href="?in[]=cpu&op[]=>&st[]=0&col[]=device&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&col[]=cpu&col[]=gfNS&lim=10&ord=cpu+desc"><img src="img/16/cpu.png" title="<?= $toplbl ?> 10 CPU <?= $lodlbl ?>"></a>
<a href="?in[]=temp&op[]=>&st[]=0&col[]=device&col[]=location&col[]=contact&col[]=firstdis&col[]=lastdis&col[]=temp&col[]=gfNS&lim=10&ord=temp+desc"><img src="img/16/temp.png" title="<?= $toplbl ?> 10 <?= $tmplbl ?>"></a>

</th>
<th>

<select multiple name="col[]" size="6" title="<?= $collbl ?>">
<?php
foreach ($cols as $k => $v){
       echo "<option value=\"$k\"".((in_array($k,$col))?" selected":"").">$v\n";
}
?>
</select>

</th>
<td>
	
<img src="img/16/paint.png" title="<?= (($verb1)?"$sholbl $laslbl Map":"Map $laslbl $sholbl") ?>"> 
<input type="checkbox" name="map" <?= $map ?>><br>
<img src="img/16/form.png" title="<?= $limlbl ?>"> 
<select size="1" name="lim">
<?php selectbox("limit",$lim) ?>
</select>
</td>
<th width="80">

<input type="submit" value="<?= $sholbl ?>">

<?php  if($isadmin) { ?>
<p>
<input type="submit" name="mon" value="<?= $monlbl ?>" onclick="return confirm('<?= $monlbl ?> <?= $addlbl ?>?')" >
<p>
<input type="submit" name="del" value="<?= $dellbl ?>" onclick="return confirm('<?= $dellbl ?>, <?= $cfmmsg ?>')" >
<?}?>
</th></tr></table></form>
<p>
<?php
}

if( count($in) ){
	if ($map and !isset($_GET['xls']) and file_exists("map/map_$_SESSION[user].php")) {
		echo "<center><h2>$netlbl Map</h2>\n";
		echo "<img src=\"map/map_$_SESSION[user].php\" style=\"border:1px solid black\"></center><p>\n";
	}

	$mma = explode('/', $mema);
	if( in_array('time',$in) or in_array('time',$col) ){
		if(($key = array_search('test', $col)) !== false) {
			unset($col[$key]);
			echo "<h4>$cfglbl $buplbl ".(($verb1)?"$dcalbl Monitoring $collbl":"Monitoring $collbl $dcalbl")."!</h4>";
		}
		$query	= GenQuery('devices','s','devices.*,length(config),length(changes),time',$ord,$lim,$in,$op,$st,$co,'LEFT JOIN configs USING (device)' );
	}elseif(  in_array('test',$in) or in_array('test',$col) ){
		$moq = 1;
		$in = array_map("AddDevs", $in);
		$query	= GenQuery('devices','s','devices.*,test,status,lost,ok',$ord,$lim,$in,$op,$st,$co,'LEFT JOIN monitoring on (devices.device = monitoring.name)' );
	}else{
		$query	= GenQuery('devices','s','*',$ord,$lim,$in,$op,$st,$co);
	}

	Condition($in,$op,$st,$co);
	if( $del ){
		if($isadmin){
			echo "<table class=\"content\"><th class=\"$modgroup[$self]2\"><img src=\"img/16/dev.png\"><br>Device</th>\n";
			echo "<th class=\"$modgroup[$self]2\"><img src=\"img/16/bcnl.png\"><br>$dellbl $stalbl</th>\n";
			$mon = 0;
		}else{
			echo $nokmsg;
			$del = 0;
		}
	}
	if( !$del ) TblHead("$modgroup[$self]2",1);

	$res	= DbQuery($query,$link);
	if($res){
		$row   = 0;
		$most = '';
		while( ($dev = DbFetchRow($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			TblRow($bg);
			$ip  = long2ip($dev[1]);
			if( $del ){
				echo "<th class=\"$bi\"><img src=\"img/dev/$dev[18].png\" title=\"$dev[3]\"><br>$dev[0]</th><td>";
				DevDelete($dev[0]," with IP $ip and SN $dev[2]");
				echo "</td>";
			}else{
				if($isadmin and $mon and $dev[1]){
					if($dev[14] & 3){
						$myma = ($dev[21] > 100)?$mma[0]:$mma[1];
						$most = AddRecord('monitoring',"name='$dev[0]'","name,monip,test,device,memalert","'$dev[0]','$dev[1]','uptime','$dev[0]','$myma'");
					}else{
						$most = AddRecord('monitoring',"name='$dev[0]'","name,monip,test,device","'$dev[0]','$dev[1]','ping','$dev[0]'");
					}
				}
				$oi  = long2ip($dev[19]);
				$ud  = urlencode($dev[0]);
				$stk = ($dev[29] > 1)?"<img src=\"img/$dev[29].png\" title=\"Stack\">":"";
				list($fc,$lc) = Agecol($dev[4],$dev[5],$row % 2);

				if( in_array("device",$col) ){
					if( $moq and $dev[34] and $dev[34] != 'none' ){
						list($statbg,$stat) = StatusBg(1,1,$dev[35],$bi);
					}else{
						$statbg = $bi;
						$stat = '';
					}
					TblCell($dev[0],'',"class=\"$statbg\" width=\"100px\"","<a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/dev/$dev[18].png\" title=\"$dev[3] $stat\"></a>$stk $most<br>","th-img");
				}
				if( in_array("imgNS",$col) ){
					TblCell('','',"bgcolor=\"white\"","<a href=\"Devices-Status.php?dev=$ud\"><img width=\"".(preg_match('/^ph|^wa|^ca/',$dev[18])?40:100)."\" src=\"".DevPanel($dev[3],$dev[18],$dev[28])."\" title=\"$dev[3]\"></a>$stk $most","th-img");
				}
				if(in_array("devip",$col)){
					$dvip = Devcli( $ip, $dev[16] );
					if( !in_array("device",$col) ){$dvip .= " ($dev[0])";}
					TblCell($dvip);
				}
				if(in_array("origip",$col)){
					TblCell( Devcli($oi,$dev[16]) );
				}
				if(in_array("serial",$col)){
					TblCell($dev[2]);
				}
				if(in_array("type",$col)){
					list($vn,$ic) = DevVendor($dev[25],substr($dev[18],2,1) );
					TblCell( $dev[3],"?in[]=type&op[]==&st[]=".urlencode($dev[3]),'',"<a href=\"http://www.google.com/search?q=".urlencode($dev[3])."&btnI=1\" target=\"window\"><img src=\"img/oui/$ic.png\" title=\"$vn\"></a> ");
				}
				if(in_array("services",$col)){
					TblCell( Syssrv($dev[6])." ($dev[6])","?in[]=services&op[]==&st[]=$dev[6]");
				}
				if(in_array("description",$col)){
					TblCell($dev[7]);
				}
				if(in_array("devos",$col)){
					TblCell( $dev[8],"?in[]=devos&op[]==&st[]=".urlencode($dev[8]) );
				}
				if(in_array("bootimage",$col)){
					TblCell( $dev[9],"?in[]=bootimage&op[]==&st[]=".urlencode($dev[9]) );
				}
				if(in_array("location",$col)){
					TblCell( $dev[10],"?in[]=location&op[]==&st[]=".urlencode($dev[10]) );
				}
				if(in_array("contact",$col)){
					TblCell( $dev[11],"?in[]=contact&op[]==&st[]=".urlencode($dev[11]) );
				}
				if(in_array("devgroup",$col)){
					TblCell( $dev[12],"?in[]=devgroup&op[]==&st[]=".urlencode($dev[12]) );
				}
				if(in_array("devmode",$col)){
					TblCell( DevMode($dev[13]),"?in[]=devmode&op[]==&st[]=".urlencode($dev[13]) );
				}
				if(in_array("snmpversion",$col)){
					TblCell( "Read:". ($dev[14] & 3) . (($dev[14] & 128)?"-HC ":" ") . (($dev[14] & 12)?" Write:".($dev[14] & 12 >> 2):"") );
				}
				if(in_array("readcomm",$col)){
					TblCell( (($guiauth != 'none')?$dev[15]:"***") );
				}
				if(in_array("writecomm",$col)){
					TblCell( (($isadmin and $guiauth != 'none')?$dev[26]:"***") );
				}
				if(in_array("cliport",$col)){
					TblCell( $dev[16],"?in[]=cliport&op[]==&st[]=".urlencode($dev[16]) );
				}
				if(in_array("login",$col)){
					TblCell( $dev[17],"?in[]=login&op[]==&st[]=".urlencode($dev[17]) );
				}
				if(in_array("icon",$col)){
					TblCell( $dev[18],"?in[]=icon&op[]==&st[]=".urlencode($dev[18]) );
				}
				if( in_array("firstdis",$col) ){
					TblCell( date($datfmt,$dev[4]),"?in[]=firstdis&op[]==&st[]=$dev[4]","bgcolor=\"#$fc\"" );
				}
				if( in_array("lastdis",$col) ){
					TblCell( date($datfmt,$dev[5]),"?in[]=lastdis&op[]==&st[]=$dev[5]","bgcolor=\"#$lc\"" );
				}
				if(in_array("cpu",$col)){
					if(substr($dev[27],1,1) == "C"){
						TblCell($dev[20].'%','','',Bar($dev[20].' ',$cpua/2,'si'),'td-img');
					}else{
						TblCell();
					}
				}
				if(in_array("memcpu",$col)){
					if($dev[21] > 100){
						TblCell( DecFix($dev[21]).'B','','align="right"' );
					}elseif($dev[21] > 0){
						TblCell( $dev[21].'%','','align="right"' );
					}else{
						TblCell();
					}
				}
				if(in_array("temp",$col)){
					if($dev[22]){
						TblCell( ($_SESSION['far'])?($dev[22]*1.8+32)."F":"$dev[22]C",'','',Bar($dev[22].' ',$tmpa/2,'si'),'td-img' );
					}else{
						TblCell();
					}
				}
				if(in_array("cusvalue",$col)){
					TblCell( $dev[23],"",'align="right"' );
				}
				if(in_array("cuslabel",$col)){
					TblCell( $dev[24],"",'align="right"' );
				}
				if(in_array("sysobjid",$col)){
					if( strstr($dev[25],'1.3.6.1.4.1.') ){
						TblCell($dev[25],"Other-Defgen.php?so=$dev[25]&ip=$ip&co=$dev[15]");
					}else{
						TblCell($dev[25],"?in[]=sysobjid&op[]==&st[]=$dev[25]");
					}
				}
				if(in_array("devopts",$col)){
					TblCell($dev[27]);
				}
				if(in_array("size",$col)){
					TblCell($dev[28],"?in[]=size&op[]==&st[]=$dev[28]",'align="right"');
				}
				if(in_array("stack",$col)){
					TblCell($dev[29],"?in[]=stack&op[]==&st[]=$dev[29]",'align="right"');
				}
				if(in_array("maxpoe",$col)){
					TblCell($dev[30].'W',"?in[]=maxpoe&op[]==&st[]=$dev[30]",'align="right"');
				}
				if(in_array("totpoe",$col)){
					TblCell($dev[31].'W','','align="right"');
				}
				if( in_array("cfgchange",$col) ){
					TblCell( $dev[32],"?in[]=cfgchange&op[]==&st[]=".urlencode($dev[32]) );
				}
				if( in_array("cfgstatus",$col) ){
					TblCell( $dev[33],"?in[]=cfgstatus&op[]==&st[]=".urlencode($dev[33]),'',DevCfg($dev[33]),'td-img' );
				}
				if( in_array("time",$col) ){
					$cbup = ($dev[36])?"$buplbl:".date($datfmt,$dev[36]).", $sizlbl:".DecFix($dev[34])."B".", $chglbl:".DecFix($dev[35])."B":'';
					TblCell($cbup,"Devices-Config.php?shc=$ud");
				}
				if( in_array("test",$col) ){
					TblCell("$loslbl:$dev[36] OK:$dev[37]","Monitoring-Setup.php?in[]=name&op[]==&st[]=$ud","class=\"$bi\"",TestImg($dev[34]),'td-img');
				}
				if(in_array("poNS",$col)){
					$pop = NodPop( array('device'),array('='),array($dev[0]),array() );
					if($pop){
						TblCell($pop,"Nodes-List.php?in[]=device&op[]==&st[]=$ud",'',Bar($pop,100,'si').' ','td-img');
					}else{
						TblCell();
					}
				}
				if( in_array("logNS",$col) and !isset($_GET['xls']) ){
					$log = array();
					$log[$dev[4]] = "bblf;$fislbl $dsclbl";
					$log[$dev[5]] = "bbrt;$laslbl $dsclbl";
#TODO add if only timestamps are used in DB?					$log[$dev[32]] = 'dril';
					$lqry = GenQuery('configs','s','time','','',array('device'),array('='),array($dev[0]) );
					$lres = DbQuery($lqry,$link);
					if($lres){
						$lro = DbFetchRow($lres);
						DbFreeResult($lres);
						if($lro[0]){
							$log[$lro[0]] = "conf;$cfglbl $buplbl";
						}
					}
					$lqry = GenQuery('monitoring','s','uptime','','',array('name','uptime'),array('=','!='),array($dev[0],0),array('AND') );
					$lres = DbQuery($lqry,$link);
					if($lres){
						$lro = DbFetchRow($lres);
						$t = time()-$lro[0]/100;
						DbFreeResult($lres);
						if($lro[0]){
							$log[$t] = "exit;$reslbl";
						}
					}
					ksort($log);
					echo "<td align=\"right\">\n";
					$pt = 0;
					foreach ($log as $t => $v){
						if($pt){
							$d = round(($t-$pt)/86400);
							if($d){
								echo Bar( $d,0,'mi',"$d $tim[d]");
							}
						}
						$pt = $t;
						$lb = explode(";", $v);
						echo " <img src=\"img/16/$lb[0].png\" title=\"$lb[1] ".date($datfmt,$t)."\">";
					}
					echo "</td>\n";
				}
				if( in_array("iiNS",$col) ){
					$ii = IfFree($dev[0]);
					TblCell($ii,"Devices-Interfaces.php?in[]=device&op[]==&st[]=$ud&co[]=AND&in[]=ifstat&op[]=<&st[]=3&co[]=AND&in[]=iftype&op[]=~&st[]=^(6|7|117)$&col[]=imBL&col[]=ifname&col[]=device&col[]=linktype&col[]=ifdesc&col[]=alias&col[]=lastchg&col[]=inoct&col[]=outoct&ord=lastchg",'',Bar($ii,-5,'si').' ','td-img');
				}
				if( in_array("stpNS",$col) and !isset($_GET['xls']) ){
					if($dev[14] and $dev[5] > time() - $rrdstep*2){
						$stppri	= str_replace('"','', Get($ip, $dev[14] & 3, $dev[15], "1.3.6.1.2.1.17.2.2.0") );
						if( preg_match("/^No Such|^$/",$stppri) ){
							TblCell("?");
						}else{
							$numchg	= str_replace('"','', Get($ip, $dev[14] & 3, $dev[15], "1.3.6.1.2.1.17.2.4.0") );
							if( preg_match("/^No Such|^$/",$numchg) ){
								TblCell("TC:?");
							}else{
								$laschg	= str_replace('"','', Get($ip, $dev[14] & 3, $dev[15], "1.3.6.1.2.1.17.2.3.0") );
								sscanf($laschg, "%d:%d:%0d:%0d.%d",$tcd,$tch,$tcm,$tcs,$ticks);
								$tcstr  = sprintf("%dD-%d:%02d:%02d",$tcd,$tch,$tcm,$tcs);
								$rport	= str_replace('"','', Get($ip, $dev[14] & 3, $dev[15], "1.3.6.1.2.1.17.2.7.0") );
								if($rport){
									$rootif	 = str_replace('"','', Get($ip, $dev[14] & 3, $dev[15], "1.3.6.1.2.1.17.1.4.1.2.$rport") );
									$ifquery = GenQuery('interfaces','s','*','','',array('device','ifidx'),array('=','='),array($dev[0],$rootif),array('AND') );
									$ifres	 = DbQuery($ifquery,$link);
									if(DbNumRows($ifres) == 1){
										$if = DbFetchRow($ifres);
										$it = "RP:<span class=\"grn\">$if[1] <i>$if[7]</i></span>";
									}else{
										$it = "Rootport n/a!";
									}
								}else{
									$it = "<span class=\"drd\">Root</span>";
								}
								TblCell("$prilbl:<span class=\"prp\">$stppri</span> $it TC:<span class=\"blu\">$numchg</span> $tcstr","","","<a href=\"Topology-Spanningtree.php?dev=$ud\"><img src=\"img/16/traf.png\" title=\"Topology-Spanningtree\"></a>");
							}
						}
					}else{
						TblCell("-");
					}
				}
				if( in_array("gfNS",$col) and !isset($_GET['xls']) ){
					echo "<td>";
					$gsiz = ($_SESSION['gsiz'] == 4)?2:1;
					if( substr($dev[27],1,1) == "C" ) echo "<a href=\"Devices-Graph.php?dv=$ud&if[]=cpu\"><img src=\"inc/drawrrd.php?dv=$ud&t=cpu&s=$gsiz\" title=\"$dev[20]% CPU $lodlbl\">\n";
					if($dev[21]) echo "<a href=\"Devices-Graph.php?dv=$ud&if[]=mem\"><img src=\"inc/drawrrd.php?dv=$ud&t=mem&s=$gsiz\" title=\"".(($dev[21] > 100)?DecFix($dev[21]).'B':$dev[21].'%')." $memlbl $frelbl\">\n";
					if($dev[22]) echo "<a href=\"Devices-Graph.php?dv=$ud&if[]=tmp\"><img src=\"inc/drawrrd.php?dv=$ud&t=tmp&s=$gsiz\" title=\"$tmplbl ".(($_SESSION['far'])?($dev[22]*1.8+32)."F":"$dev[22]C")."\">\n";
					if($dev[23]){
						if($dev[24] and $dev[24] != 'MemIO'){
							list($ct,$cy,$cu) = explode(";", $dev[24]);
						}else{
							$ct = "$memlbl IO";
							$cu = "Bytes $frelbl";
						}
						echo "<a href=\"Devices-Graph.php?dv=$ud&if[]=cuv\"><img src=\"inc/drawrrd.php?dv=$ud&if[]=".urlencode($ct)."&if[]=".urlencode($cu)."&s=$gsiz&t=cuv\" title=\"$ct ".DecFix($dev[23])." $cu\">";
					}
					echo "</td>";
				}
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
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Devices<?= ($ord)?", $srtlbl: $ord":"" ?><?= ($lim)?", $limlbl: $lim":"" ?></td></tr>
</table>
<?php
}
include_once ("inc/footer.php");
?>
