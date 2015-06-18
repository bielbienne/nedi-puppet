<?PHP

//===============================
// Monitoring related functions (and variables)
//===============================

// Event icons & colors based on level
$mico['10']  = "fogy";
$mico['50']  = "fogr";
$mico['100'] = "fobl";
$mico['150'] = "foye";
$mico['200'] = "foor";
$mico['250'] = "ford";

$mbak['10']  = "txta";
$mbak['50']  = "good";
$mbak['100'] = "noti";
$mbak['150'] = "warn";
$mbak['200'] = "alrm";
$mbak['250'] = "crit";

//===================================================================
// Return icon and title for an event class
function EvClass($c){
	
	global $cfglbl,$chglbl,$memlbl,$msglbl,$notlbl,$dsclbl,$gralbl,$usrlbl,$mlvl,$trflbl,$tmplbl,$errlbl,$stco;

	if($c == 'dev'){
		return array('dev','Device Syslog');
	}elseif($c == 'nedc'){
		return array('cpu','CPU');
	}elseif($c == 'nede'){
		return array('kons',"CLI $errlbl");
	}elseif($c == 'nedn'){
		return array('bcls',"$notlbl $dsclbl");
	}elseif($c == 'nedd'){
		return array('radr',"$dsclbl");
	}elseif($c == 'nedl'){
		return array('link','Link');
	}elseif($c == 'nedm'){
		return array('mem',$memlbl);
	}elseif($c == 'nedo'){
		return array('pcm','Module');
	}elseif($c == 'nedi'){
		return array('port',"IF $msglbl");
	}elseif($c == 'nedj'){
		return array('ncfg',"IP $msglbl");
	}elseif($c == 'neds'){
		return array('sys',"System $chglbl");
	}elseif($c == 'nedt'){
		return array('temp',$tmplbl);
	}elseif($c == 'nedu'){
		return array('mark','Supplies');
	}elseif($c == 'nedp'){
		return array('batt','PoE');
	}elseif($c == 'node'){
		return array('node','Node Syslog');
	}elseif($c == 'trap'){
		return array('warn','SNMP Trap');
	}elseif($c == 'secf'){
		return array('nods',"MAC Flood");
	}elseif($c == 'secj'){
		return array('net',"IP $chglbl");
	}elseif($c == 'secn'){
		return array('add',"$stco[10] Node");
	}elseif($c == 'secp'){
		return array('drop','ARP Poison');
	}elseif($c == 'secs'){
		return array('step','Stolen');
	}elseif($c == 'mast'){
		return array('hat3','Master');
	}elseif($c == 'trfw'){
		return array('grph',"$trflbl $mlvl[150]");
	}elseif($c == 'trfa'){
		return array('grph',"$trflbl $mlvl[200]");
	}elseif($c == 'trfe'){
		return array('brup',"IF $errlbl");
	}elseif($c == 'bugn'){
		return array('bug','NeDi Debug');
	}elseif($c == 'bugx'){
		return array('bug','Xtra Debug');
	}elseif(strpos($c,'cfg') !== false){
		return array('conf',$cfglbl);
	}elseif(strpos($c,'mon') !== false){
		return array('bino','Monitoring');
	}elseif(strpos($c,'usr') !== false){
		return array('user',$usrlbl);
	}else{
		return array('say',$mlvl['10']);
	}
}

//===================================================================
// Return icon for an incident group
function IncImg($cat){

	if($cat == 1)		{return "add";}
	elseif($cat == 11)	{return "flas";}
	elseif($cat == 12)	{return "dril";}
	elseif($cat == 13)	{return "star";}
	elseif($cat == 14)	{return "ncon";}
	elseif($cat == 15)	{return "ele";}
	elseif($cat == 16)	{return "wthr";}
	elseif($cat < 20)	{return "home";}
	elseif($cat == 21)	{return "batt";}
	elseif($cat == 22)	{return "dev";}
	elseif($cat == 23)	{return "cubs";}
	elseif($cat == 24)	{return "cbox";}
	elseif($cat == 25)	{return "grph";}
	elseif($cat < 30)	{return "cinf";}
	elseif($cat == 31)	{return "ncfg";}
	elseif($cat == 32)	{return "conf";}
	elseif($cat == 33)	{return "eyes";}
	elseif($cat == 34)	{return "hat";}
	elseif($cat < 40)	{return "user";}
	else			{return "bbox";}
}

//===================================================================
// Return bg color based on monitoring status
function StatusBg($nd,$mn,$a,$bg="imga"){

	global $pause,$tim,$errlbl,$mullbl,$alllbl,$stco;

	$partial = ($nd == $mn)?"":" part";
	if ($mn == 1){
		$onetgt = ($nd == 1)?"":", 1 $stco[200] ";
		$out = $a * $pause;
		if( $out > 86400){
			return array("crit$partial",(intval($out/8640)/10)." $tim[d]");
		}elseif( $out > 3600){
			return array("crit$partial",(intval($out/360)/10)." $tim[h]");
		}elseif( $out > 600){
			return array("alrm$partial",(intval($out/6)/10)." $tim[i]");
		}elseif( $out ){
			return array("warn$partial","$out $tim[s]");
		}else{
			return array("good$partial","OK");
		}
	}elseif ($mn > 1){
		if($a > 1){
			return array("crit$partial","$mullbl $errlbl");
		}elseif($a){
			return array("alrm$partial","$errlbl $tim[n]");
		}else{
			return array("good$partial","$alllbl OK");
		}
	}else{
		return array ($bg,"");
	}
}

//===================================================================
// Generate Target status table
function StatusMon($nmon,$lastok,$nalr,$graf=0){

	global $link,$alarr,$rrdstep,$mlvl,$stco,$tgtlbl,$laslbl,$dsclbl,$nonlbl,$modgroup,$self;

	$query	= GenQuery('devices','s','count(lastdis)','','',array('lastdis'),array('>'),array(time() - $rrdstep));
	$res	= DbQuery($query,$link);
	if($res){
		$ndsc = DbFetchRow($res);
		DbFreeResult($res);
	}else{
		print DbError($link);
	}

	$mtit = "$lastok/$nmon $tgtlbl OK, $ndsc[0] Device $laslbl $dsclbl";

	if($_SESSION['gsiz'] == 6){
		echo "<a href=\"mh.php\"><img src=\"img/32/bino.png\" title=\"$mtit\"></a>\n";
	}elseif(!$graf){
		echo "<img src=\"img/32/bino.png\" title=\"$mtit\">\n";
	}else{
?>
<a href="Devices-Graph.php?dv=Totals&if[]=mon&sho=1"><img src="inc/drawrrd.php?t=mon&s=<?= $graf ?>" title="<?= $avalbl ?> <?= $gralbl ?> (<?= $mtit ?>)"></a>
<a href="Devices-Graph.php?dv=Totals&if[]=msg&sho=1"><img src="inc/drawrrd.php?t=msg&s=<?= $graf ?>" title="<?= $msglbl ?> <?= $sumlbl ?>"></a>
<?php
	}

	if($nalr == 0){
		if($lastok){
			if(!$graf){echo "<img src=\"img/32/bchk.png\" title=\"Monitoring $srvlbl $stco[100]\">";}
		}else{
			if(!$graf){echo "<img src=\"img/32/bcls.png\" title=\"$nonlbl OK\">";}
			if($_SESSION['vol']){echo "<embed src=\"inc/enter2.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
		}
	}else{
		if($nalr == 1){
			if(!$graf){echo "<img src=\"img/32/fobl.png\" title=\"1 $mlvl[200]\">";}
			if($_SESSION['vol']){echo "<embed src=\"inc/alarm1.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
		}elseif($nalr < 10){
			if($ni[0] < 3){
				$ico = "fovi";
			}elseif($ni[0] < 5){
				$ico = "foye";
			}else{
				$ico = "foor";
			}
			if(!$graf){echo "<img src=\"img/32/$ico.png\" title=\"$nalr $mlvl[200]\">";}
			if($_SESSION['vol']){echo "<embed src=\"inc/alarm2.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
		}else{
			if(!$graf){echo "<img src=\"img/32/ford.png\" title=\"$nalr $mlvl[200]!\">";}
			if($_SESSION['vol']){echo "<embed src=\"inc/alarm3.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
		}
?>
<p>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/trgt.png"><br><?= $tgtlbl ?></th><th><img src="img/16/flag.png"><br><?= $mlvl['200'] ?></th>
<?php
		$row = 0;
		foreach(array_keys($alarr) as $t){
			if($alarr[$t]['st']){
				if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
				$row++;
				list($statbg,$stat) = StatusBg(1,1,$alarr[$t]['st'],$bi);
				echo "<tr><th class=\"$bi\" align=\"left\">";
				if($_SESSION['gsiz'] != 6) echo "<a href=\"Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=".urlencode($t)."\"><img src=\"img/16/bino.png\"></a>\n";
				echo substr($t,0,$_SESSION['lsiz'])."</th><th class=\"$statbg\">$stat</th></tr>\n";
			}
		}
?>
</table>
<?php
	}
}

//===================================================================
// Generate slow target table
function StatusSlow($slow){

	global $latw,$tgtlbl,$latlbl,$modgroup,$self;

	if( count($slow) ){
?>
<p>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th><img src="img/16/trgt.png"><br><?= $tgtlbl ?></th><th><img src="img/16/clock.png"><br><?= $latlbl ?></th>
<?php
		$row = 0;
		foreach(array_keys($slow) as $t){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			echo "<tr>";
			if($_SESSION['gsiz'] != 6) echo "<th class=\"$bi\"><a href=\"Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=".urlencode($t)."\"><img src=\"img/16/bino.png\"></a></th>\n";
			echo "<td class=\"$bg\">".substr($t,0,$_SESSION['lsiz'])."</td><td>".Bar($slow[$t]['st'],$latw,'si')." ".$slow[$t]['st']."ms</td></tr>\n";
		}
?>
</table>
<?php
	}
}

//===================================================================
// Generate If status tables
function StatusIf($loc,$mode){

	global $link,$rrdstep,$trfa,$modgroup,$self,$trflbl,$errlbl,$inblbl,$oublbl,$tim,$firstmsg;

	if($mode   == "brup"){
		$label = "$inblbl $errlbl";
		$query = GenQuery('interfaces','s','device,ifname,speed,iftype,dinerr','dinerr desc',$_SESSION['lim'],array('dinerr','iftype','location'),array('>','!=','like'),array("$rrdstep",'71',$loc),array('AND','AND'),'JOIN devices USING (device)');
	}elseif($mode  == "brdn"){
		$label = "$oublbl $errlbl";
		$query = GenQuery('interfaces','s','device,ifname,speed,iftype,douterr','douterr desc',$_SESSION['lim'],array('douterr','iftype','location'),array('>','!=','like'),array("$rrdstep",'71',$loc),array('AND','AND'),'JOIN devices USING (device)');
	}elseif($mode  == "bbup"){
		$label = "$inblbl $trflbl";
		$query = GenQuery('interfaces','s',"device,ifname,speed,iftype,dinoct/speed/$rrdstep*800",'dinoct/speed desc',$_SESSION['lim'],array('speed',"dinoct/speed/$rrdstep*800",'location'),array('>','>','like'),array(0,$trfa,$loc),array('AND','AND'),'JOIN devices USING (device)');
	}elseif($mode  == "bbdn"){
		$label = "$inblbl $trflbl";
		$query = GenQuery('interfaces','s',"device,ifname,speed,iftype,doutoct/speed/$rrdstep*800",'doutoct/speed desc',$_SESSION['lim'],array('speed',"doutoct/speed/$rrdstep*800",'location'),array('>','>','like'),array(0,$trfa,$loc),array('AND','AND'),'JOIN devices USING (device)');
	}elseif($mode  == "bdis"){
		$label = "Disabled $tim[t]";
		$query = GenQuery('interfaces','s','device,ifname,speed,iftype,ifstat,lastchg','lastchg desc',$_SESSION['lim'],array('ifstat','iftype','lastchg','location'),array('=','!=','>','like'),array('0','53',$firstmsg,$loc),array('AND','AND'),'JOIN devices USING (device)');
	}
	$res	= DbQuery($query,$link);
	if($res){
		$nr = DbNumRows($res);
		if($nr){
?>
<p>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/port.png" title="Top <?= $_SESSION['lim'] ?>"><br>Interface</th><th><img src="img/16/<?= $mode ?>.png" title="<?= $label ?>"><br><?= (substr($label,0,3)) ?></th>
<?php
			$row = 0;
			while( ($r = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";$off=200;}else{$bg = "txtb"; $bi = "imgb";$off=185;}
				$row++;
				$bg3= sprintf("%02x",$off);
				$tb = ($type)?$r[4]*5:($r[4]-$trfa)*2;
				if ($tb > 55){$tb = 55;}
				$rb = sprintf("%02x",$tb + $off);
				$t  = substr($r[0],0,strpos($r[0],'.') );
				$t  = (strlen($t) < 4)?$r[0]:$t;
				$ud = urlencode($r[0]);
				$ui = urlencode($r[1]);
				if($mode == "bdis"){
					$rb = $bg3;
					$stat = date($_SESSION['date'],$r[5]);
				}elseif($mode == "brup" or $mode == "brdn"){
					$stat = DecFix($r[4]);
				}else{
					$stat = sprintf("%1.1f",$r[4])." %";
				}
				list($ifimg,$iftit) = Iftype($r[3]);
				echo "<tr class=\"$bg\"><th class=\"$bi\" width=\"25\"><img src=img/$ifimg title=\"$iftit\">";
				if($_SESSION['gsiz'] == 6){
					echo "</th><td>$t $r[1]</td><th bgcolor=\"#$rb$rb$bg3\">";
				}else{
					echo "</th><td><a href=Devices-Status.php?dev=$ud&pop=on>$t</a> ";
					echo "<a href=Nodes-List.php?in[]=device&op[]==&st[]=$ud&co[]=AND&in[]=ifname&op[]==&st[]=$ui>$r[1]</a> ".DecFix($r[2])."</td><th bgcolor=\"#$rb$rb$bg3\">\n";
				}
				echo "$stat</th></tr>\n";
			}
			echo "</table>\n";
		}elseif(!$_SESSION['gsiz'] or $_SESSION['gsiz'] == 6){
?>
<p><img src="img/32/<?= $mode ?>.png" title="<?= $label ?>" hspace="8"><img src="img/32/bchk.png" title="OK">
<?php
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
}

//===================================================================
// Generate cpu status table
function StatusCpu($loc){

	global $link,$tgtlbl,$lodlbl,$limlbl,$modgroup,$self;

	$query = GenQuery('monitoring','s','name,cpu,cpualert','cpu desc',$_SESSION['lim'],array('cpu','location'),array('COL >','like'),array('cpualert',$loc),array('AND'),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nr = DbNumRows($res);
		if($nr){
?>
<p><table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="2" nowrap><img src="img/16/trgt.png" title="Top <?= $_SESSION['lim'] ?> CPU <?= $lodlbl ?>"><br><?= $tgtlbl ?></th>
<th nowrap><img src="img/16/cpu.png"><br><?= $lodlbl ?></th>
<?php
			$row = 0;
			while( ($t = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";$off="b8";}else{$bg = "txtb"; $bi = "imgb";$off="c8";}
				$row++;
				$lv  = $t[1]-$r[2];
				$hi  = sprintf("%02x",(($lv > 55)?55:$lv) + 200);
				$na  = substr($t[0],0,$_SESSION['lsiz']);
				$ud  = urlencode($t[0]);
				if($_SESSION['gsiz'] == 6){
					echo "<tr bgcolor=\"#$hi$off$off\"><th class=\"$bi\">$row</th><td>$na</td><th nowrap>$t[1]%</th></tr>\n";
				}else{
					echo "<tr bgcolor=\"#$hi$off$off\"><th class=\"$bi\">$row</th><td><a href=Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$ud>$na</a></td>\n";
					echo "<th nowrap title=\"$limlbl $t[2]%\">$t[1]%</th></tr>\n";
				}
			}
			echo "</table>\n";
		}else{
			$isiz = ($_SESSION['gsiz'] == 2)?"16":"32";
?>
<p><img src="img/<?= $isiz ?>/cpu.png" title="CPU <?= $lodlbl ?>" hspace="8"> <img src="img/<?= $isiz ?>/bchk.png" title="OK">
<?php
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
}

//===================================================================
// Generate mem availabilty table
function StatusMem($loc){#TODO like cpu

	global $link,$tgtlbl,$limlbl,$frelbl,$memlbl,$modgroup,$self;

	$aquery = GenQuery('monitoring','s','name,memcpu,memalert','memcpu desc',$_SESSION['lim'],array('memcpu/1024','memcpu','location'),array('COL <','>','like'),array('memalert',100,$loc),array('AND','AND'),'LEFT JOIN devices USING (device)');
	$ares	= DbQuery($aquery,$link);
	$nar    = DbNumRows($ares);

	$pquery = GenQuery('monitoring','s','name,memcpu,memalert','memcpu desc',$_SESSION['lim'],array('memcpu','memcpu','memcpu','location'),array('COL <','>','<','like'),array('memalert',0,100,$loc),array('AND','AND','AND'),'LEFT JOIN devices USING (device)');
	$pres	= DbQuery($pquery,$link);
	$npr    = DbNumRows($pres);

	if($nar or $npr){
?>
<p><table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/trgt.png" title="Top <?= $_SESSION['lim'] ?> <?= $memlbl ?> <?= $frelbl ?>"><br><?= $tgtlbl ?></th>
<th nowrap><img src="img/16/mem.png"><br><?= $frelbl ?></th>
<?php
		$row = 0;
		while( ($t = DbFetchRow($ares)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";$off="b8";}else{$bg = "txtb"; $bi = "imgb";$off="c8";}
			$row++;
			$lv  = pow($ma[0]*1024/$t[1],8);
			$hi  = sprintf("%02x",(($lv > 55)?55:$lv) + 200);
			$na  = substr($t[0],0,$_SESSION['lsiz']);
			$ud  = urlencode($t[0]);
			if($_SESSION['gsiz'] == 6){
				echo "<tr bgcolor=\"#$hi$hi$off\"><th class=\"$bi\">$row</th><td>$na</td><th nowrap>".DecFix($t[1])."B</th></tr>\n";
			}else{
				echo "<tr bgcolor=\"#$hi$hi$off\"><th class=\"$bi\">$row</th><td><a href=Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$ud>$na</a></td>\n";
				echo "<th nowrap title=\"$limlbl ".DecFix($t[2]*1024)."B\">".DecFix($t[1])."B</th></tr>\n";
			}
		}
		while( ($t = DbFetchRow($pres)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";$off="b8";}else{$bg = "txtb"; $bi = "imgb";$off="c8";}
			$row++;
			$lv  = $t[1]-$m[1];
			$hi  = sprintf("%02x",(($lv > 55)?55:$lv) + 200);
			$na  = substr($t[0],0,$_SESSION['lsiz']);
			$ud  = urlencode($t[0]);
			if($_SESSION['gsiz'] == 6){
				echo "<tr bgcolor=\"#$hi$hi$off\"><th class=\"$bi\">$row</th><td>$na</td><th nowrap>$t[1]%</th></tr>\n";
			}else{
				echo "<tr bgcolor=\"#$hi$hi$off\"><th class=\"$bi\">$row</th><td><a href=Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$ud>$na</a></td>\n";
				echo "<th nowrap title=\"$limlbl $t[2]%\">$t[1]%</th></tr>\n";
			}
		}
		echo "</table>\n";
	}else{
		$isiz = ($_SESSION['gsiz'] == 2)?"16":"32";
?>
<p><img src="img/<?= $isiz ?>/mem.png" title="<?= $memlbl ?> <?= $frelbl ?>" hspace="8"> <img src="img/<?= $isiz ?>/bchk.png" title="OK">
<?php
	}
	DbFreeResult($ares);
	DbFreeResult($pres);
}

//===================================================================
// Generate temperature status table
function StatusTmp($loc){

	global $link,$tmpa,$tgtlbl,$tmplbl,$limlbl,$modgroup,$self;

	$query = GenQuery('monitoring','s','name,temp,tempalert','temp desc',$_SESSION['lim'],array('temp','location'),array('COL >','like'),array('tempalert',$loc),array('AND'),'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nr = DbNumRows($res);
		if($nr){
?>
<p><table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th colspan="2"><img src="img/16/trgt.png" title="Top <?= $_SESSION['lim'] ?> <?= $tmplbl ?>"><br><?= $tgtlbl ?></th>
<th><img src="img/16/temp.png"><br>Temp</th>
<?php
			$row = 0;
			while( ($t = DbFetchRow($res)) ){
				if ($row % 2){$bg = "txta"; $bi = "imga";$off="b8";}else{$bg = "txtb"; $bi = "imgb";$off="c8";}
				$row++;
				$lv  = pow(($t[1]-$tmpa),2);
				$hi  = sprintf("%02x",(($lv > 55)?55:$lv) + 200);
				$na  = substr($t[0],0,$_SESSION['lsiz']);
				$ud  = urlencode($t[0]);
				if($_SESSION['gsiz'] == 6){
					echo "<tr bgcolor=\"#$hi$off$hi\"><th class=\"$bi\">$row</th><td>$na</td><th nowrap>$t[1]C</th></tr>\n";
				}else{
					echo "<tr bgcolor=\"#$hi$off$hi\"><th class=\"$bi\">$row</th><td><a href=\"Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$ud\">$na</a></td>\n";
					echo "<th nowrap title=\"$limlbl $t[2]C\">$t[1]C</th></tr>\n";
				}
			}
			echo "</table>\n";
		}else{
			$isiz = ($_SESSION['gsiz'] == 2)?"16":"32";
?>
<p><img src="img/<?= $isiz ?>/temp.png" title="<?= $tmplbl ?>" hspace="8"> <img src="img/<?= $isiz ?>/bchk.png" title="OK">
<?php
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
}

//===================================================================
// Show unacknowledged incidents
function StatusIncidents($loc,$opt=0){

	global $link,$modgroup,$self,$levlbl,$inclbl,$sttlbl,$endlbl,$tgtlbl,$loclbl,$conlbl,$acklbl,$nonlbl,$mbak,$mico,$locsep;

	$ilnk = ($_SESSION['gsiz'] == 6)?"":"Monitoring-Incidents.php?grp=1";
	$isiz = ($_SESSION['gsiz'] == 2)?"16":"32";

	if($opt){
		$query	= GenQuery('incidents','s','level,name,start,end,device,location,contact,type,readcomm','id desc',$_SESSION['lim'],array('time','location'),array('=','like'),array(0,$loc),array('AND'),'LEFT JOIN devices USING (device)');
		$res	= DbQuery($query,$link);
		if($res){
			$nr = DbNumRows($res);
			if($nr){
?>
<p><table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th width="50"><img src="img/16/idea.png"><br><?= $levlbl ?></th>
<th><img src="img/16/trgt.png"><br><?= $tgtlbl ?></th>
<th><img src="img/16/bblf.png"><br><?= $sttlbl ?></th>
<th><img src="img/16/bbrt.png"><br><?= $endlbl ?></th>
<th><img src="img/16/dev.png"><br>Device</th>
<th><img src="img/16/home.png"><br><?= $loclbl ?></th>
<th><img src="img/16/umgr.png"><br><?= $conlbl ?></th>
</tr>
<?php
				$row = 0;
				while( ($i = DbFetchRow($res)) ){
					if ($row % 2){$bg = "txta"; $bi = "imga";$off="b8";}else{$bg = "txtb"; $bi = "imgb";$off="c8";}
					$row++;
					$ut  = urlencode($i[1]);
					$ud  = urlencode($i[4]);
					echo "<tr class=\"$bg\">\n";
					echo "<th class=\"".$mbak[$i[0]]."\"><img src=\"img/16/" . $mico[$i[0]] . ".png\" title=\"" . $mlvl[$i[0]] . "\"></th>";
					echo "<td><a href=\"$i[8]://$ud/Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$ut\">".substr($i[1],0,$_SESSION['lsiz'])."</a></td>\n";
					echo "<td>".date($_SESSION['date'],$i[2])."</td><td ".(($i[3])?">".date($_SESSION['date'],$i[3]):"class=\"warn\">-")."</td>\n";
					echo "<td><a href=\"$i[8]://$ud/Monitoring-Incidents.php?grp=1\">".substr($i[4],0,$_SESSION['lsiz'])."</a></td>\n";
					$l = explode($locsep, $i[5]);
					echo "<td>".substr("$l[1], $l[0]",0,$_SESSION['lsiz'])."</td><td>$i[6]</td></tr>\n";
				}
				echo "</table>\n";
				if($nr == 1){
					if($_SESSION['vol']){echo "<embed src=\"inc/alarm1.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
				}elseif($nr < 10){
					if($_SESSION['vol']){echo "<embed src=\"inc/alarm2.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
				}else{
					if($_SESSION['vol']){echo "<embed src=\"inc/alarm2.mp3\" volume=\"$_SESSION[vol]\" hidden=\"true\">\n";}
				}
			}else{
?>
<p><img src="img/<?= $isiz ?>/bomb.png" title="<?= $inclbl ?>" hspace="8"> <img src="img/<?= $isiz ?>/bchk.png" title="<?= $nonlbl ?>">
<?php
			}
			DbFreeResult($res);
		}else{
			print DbError($link);
		}
	}else{
		$ico = "fogy";
		$inctit = "?";
		$query	= GenQuery('incidents','s','count(*)','','',array('time','location'),array('=','like'),array(0,$loc),array('AND'),'LEFT JOIN devices USING (device)');
		$res	= DbQuery($query,$link);
		if($res){
			$ni = DbFetchRow($res);
			$inctit = $ni[0];
			if($ni[0] == 0){
				$ico = "bchk";
				$inctit = $nonlbl;
			}elseif($ni[0] == 1){
				$ico = "fobl";
			}elseif($ni[0] < 3){
				$ico = "fovi";
			}elseif($ni[0] < 5){
				$ico = "foye";
			}elseif($ni[0] < 10){
				$ico = "foor";
			}else{
				$ico = "ford";
			}
		}else{
			print DbError($link);
		}
?>
<p>
<a href="<?= $ilnk ?>"><img src="img/<?= $isiz ?>/bomb.png" title="<?= $inclbl ?>" hspace="8">
<img src="img/<?= $isiz ?>/<?= $ico ?>.png" title="<?= $acklbl ?>: <?= $inctit ?>"></a>
<?php
	}
}

//===================================================================
// Displays Events based on query (mod 0=full, 1=full-master 2=small, 3=mobile)
function Events($lim,$in,$op,$st,$co,$mod=0){

	global $link,$modgroup,$self,$bg,$bi,$mico,$mbak,$mlvl,$noiplink;
	global $gralbl,$lstlbl,$levlbl,$timlbl,$tgtlbl,$srclbl,$monlbl,$msglbl,$stalbl,$cfglbl,$cmdlbl,$nonlbl,$clalbl,$limlbl;

	$query = GenQuery('events','s','id,level,time,source,info,class,device,type,readcomm','id desc',$lim,$in,$op,$st,$co,'LEFT JOIN devices USING (device)');
	$res	= DbQuery($query,$link);
	if($res){
		$nmsg = DbNumRows($res);
		if($nmsg){
			$row  = 0;
			if($mod > 1){
				if($mod == 2){
?>
<table class="content"><tr>
<th width="50" class="<?= $modgroup[$self] ?>2"><img src="img/16/idea.png"><br><?= $levlbl ?></th>
<th class="<?= $modgroup[$self] ?>2"><img src="img/16/clock.png"><br><?= $timlbl ?></th>
<th class="<?= $modgroup[$self] ?>2"><img src="img/16/say.png"><br><?= $srclbl ?></th>
<th class="<?= $modgroup[$self] ?>2"><img src="img/16/find.png"><br>Info</th>
</tr>
<?php
				}
				while( ($m = DbFetchRow($res)) ){
					if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
					$row++;
					$time = date($_SESSION['date'],$m[2]);
					$fd   = urlencode(date("m/d/Y H:i:s",$m[2]));
					$usrc = urlencode($m[3]);
					$ssrc = substr($m[3],0,$_SESSION['lsiz']);
					$sinf = (strlen($m[4]) > 60)?substr($m[4],0,60)."...":$m[4];
					if($mod == 2){
						TblRow($bg);
						echo "<th class=\"".$mbak[$m[1]]."\"><a href=Monitoring-Events.php?lvl=$m[1]>\n";
						echo "<img src=\"img/16/". $mico[$m[1]] .".png\" title=\"". $mlvl[$m[1]] ."\"></a></th>\n";
						echo "<td nowrap><a href=Monitoring-Events.php?in[]=time&op[]==&st[]=$fd>$time</a></td><td nowrap>\n";
						echo "<a href=Monitoring-Events.php?in[]=source&op[]==&st[]=$usrc>$ssrc</a></td><td>$sinf</td></tr>\n";
					}else{							# Mobile mode, mh.php
						echo "<tr class=\"".$mbak[$m[1]]."\"><th nowrap>$ssrc</th><td nowrap>$time</td><td>$sinf</td></tr>\n";
					}
				}
				echo "</table>\n";
			}else{
?>
<table class="content"><tr>
<th width="50" class="<?= $modgroup[$self] ?>2"><img src="img/16/key.png"><br>Id</th>
<th width="50" class="<?= $modgroup[$self] ?>2"><img src="img/16/idea.png" title="10=<?= $mlvl['10'] ?>,50=<?= $mlvl['50'] ?>, 100=<?= $mlvl['100'] ?>, 150=<?= $mlvl['150'] ?>, 200=<?= $mlvl['200'] ?>, 250=<?= $mlvl['250'] ?>"><br><?= $levlbl ?></th>
<th width="120" class="<?= $modgroup[$self] ?>2"><img src="img/16/clock.png"><br><?= $timlbl ?></th>
<th class="<?= $modgroup[$self] ?>2"><img src="img/16/say.png" title="<?= $monlbl ?> <?= $tgtlbl ?> || IP (<?= $msglbl ?> <?= $levlbl ?> < 50)"><br><?= $srclbl ?></th>
<th width="50" class="<?= $modgroup[$self] ?>2"><img src="img/16/abc.png" title="<?= $msglbl ?> <?= $clalbl ?>:<?= $cmdlbl ?>"><br><?= $clalbl ?></th>
<th class="<?= $modgroup[$self] ?>2"><img src="img/16/find.png"><br>Info</th>
</tr>
<?php
				while( ($m = DbFetchRow($res)) ){
					if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
					$row++;
					$time = date($_SESSION['date'],$m[2]);
					$fd   = urlencode(date("m/d/Y H:i:s",$m[2]));
					$usrc = urlencode($m[3]);
					$utgt = urlencode($m[6]);
					list($ei,$et)   = EvClass($m[5]);
					TblRow($bg);
					echo "<th><a href=\"Monitoring-Events.php?in[]=id&op[]==&st[]=$m[0]\">$m[0]</a></th>\n";
					echo "<th class=\"".$mbak[$m[1]]."\"><a href=\"Monitoring-Events.php?in[]=level&op[]==&st[]=$m[1]&co[]=$co[0]&in[]=$in[1]&op[]=$op[1]&st[]=".urlencode($st[1])."\"><img src=\"img/16/". $mico[$m[1]] .".png\" title=\"". $mlvl[$m[1]] ."\"></a></th>\n";
					echo "<td nowrap><a href=\"Monitoring-Events.php?in[]=time&op[]==&st[]=$fd\">$time</a></td>\n";
					if($m[7] == 'NeDi Agent'){
						$agnt = "$m[8]://$utgt/";
						$alnk = "on <a href=\"Devices-Status.php?dev=$utgt\">$utgt</a> ";
					}else{
						$agnt  = "";
						$alnk  = "";
					}
					echo "<td><a href=\"Monitoring-Events.php?in[]=source&op[]==&st[]=$usrc\"><b>$m[3]</b></a> $alnk</td>\n";

					$action = "<a href=\"${agnt}Devices-Status.php?dev=$usrc&pop=1\"><img src=\"img/16/$ei.png\" title=\"$et, Device $stalbl\"></a>";
					if($m[5] == "node"){			# Syslog from a node
						$action = "<a href=\"${agnt}Nodes-List.php?in[]=name&op[]==&st[]=$m[3]\"><img src=\"img/16/$ei.png\" title=\"$et, Node $lstlbl\"></a>";
					}elseif($m[3] == "NeDi"){		# Not related to a dev or node!
						$action = "<a href=\"${agnt}System-Files.php\"><img src=\"img/16/$ei.png\" title=\"$et, NeDi $cfglbl\"></a>";
					}elseif($m[5] == "moni"){		# Monitoring events
						$action = "<a href=\"${agnt}Monitoring-Setup.php?in[]=name&op[]=%3D&st[]=$usrc\"><img src=\"img/16/$ei.png\" title=\"$et, Monitoring Setup\"></a>";
					}elseif($m[5] == "cfgn" or $m[5] == "cfgc"){	# New config or changes
						$action =  "<a href=\"Devices-Config.php?shc=$usrc\"><img src=\"img/16/$ei.png\" title=\"$et, Device $cfglbl\"></a>";
					}elseif(strpos($m[5],"trf") !== FALSE){	# Traffic warnings or alerts
						$action =  "<a href=\"${agnt}Devices-Status.php?dev=$usrc&trg=1&erg=1\"><img src=\"img/16/$ei.png\" title=\"$et, Device $stalbl\"></a>";
					}elseif($m[1] < 50){
						$action = "<a href=\"${agnt}Nodes-List.php?in[]=nodip&op[]==&st[]=$m[3]\"><img src=\"img/16/$ei.png\" title=\"$et, Node $lstlbl\"></a>";
					}elseif($m[5] == "ip"){			# syslog from unmonitored source
						$action = "<img src=\"img/16/$ei.png\" title=\"$et ->$msglbl $clalbl $m[5]\">";
					}elseif($m[5] == "secs"){		# Security Stolen
						$action = "<a href=\"${agnt}Nodes-Stolen.php\"><img src=\"img/16/$ei.png\" title=\"$et, Nodes Stolen\"></a>";
					}
					echo "<th class=\"$bi\">$action</th><td>";
					if($noiplink){
						$info = preg_replace('/[\s:]([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(\s|:|,|$)/', " <span class=\"blu\">$1</span> ", $m[4]);
						echo preg_replace('/[\s:]([0-9a-f]{4}[\.-]?[0-9a-f]{4}[\.-]?[0-9a-f]{4}|[0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2})(\s|$)/', " <span class=\"mrn\">$1</span> ", $info);
					}else{
						$info = preg_replace('/[\s:]([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(\s|:|,|$)/', " <span class=\"blu\">$1</span>
						<a href=\"Nodes-Toolbox.php?Dest=$1\"><img src=\"img/16/dril.png\" title=\"Lookup\"></a>
						<a href=\"Nodes-List.php?in[]=nodip&op[]=%3D&st[]=$1\"><img src=\"img/16/nods.png\" title=\"Nodes $lstlbl\"></a>
						<a href=\"?in[]=info&op[]=~&st[]=$1\"><img src=\"img/16/bell.png\" title=\"Monitoring-Events\"></a>", $m[4]);
						echo preg_replace('/[\s:]([0-9a-f]{4}[\.-]?[0-9a-f]{4}[\.-]?[0-9a-f]{4}|[0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2})(\s|$)/', " <span class=\"mrn\">$1</span> <a href=\"Nodes-Status.php?mac=$1\"><img src=\"img/16/node.png\" title=\"Node $stalbl\"></a> ", $info);
					}
					echo "</td></tr>\n";
				}
?>
</table>
<table class="content">
<tr><td class="<?= $modgroup[$self] ?>2"><?= $row ?> <?= $msglbl ?><?= ($lim)?", $limlbl: $lim":"" ?></td></tr>
</table>
<?php

			}
		}else{
			echo "<p><h5>$nonlbl</h5>";
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
}

//===================================================================
// Generate topology based monitoring metainfo
function TopoMon($loc){

	global $link,$lastok,$pause,$latw,$alarr,$slarr;

	$query	= GenQuery('monitoring','s','name,class,lastok,status,latency','name','',array('test','location'),array('!=','like'),array('none',$loc),array('AND'),'LEFT JOIN devices USING (device)');# Need every monitored target, thus no further optimization!
	$res	= DbQuery($query,$link);
	if($res){
		$nmo = 0;
		$lok = 0;
		$tal = 0;
		$alarr = array();
		$slarr = array();
		while( ($m = DbFetchRow($res)) ){
			$nmo++;
			if($m[2] > (time() - 2*$pause) ){$lok++;}
			$alarr[$m[0]]['st'] = $m[3];
			$alarr[$m[0]]['cl'] = $m[1];
			if($m[3]) $tal++;
			if($m[4] > $latw){
				$slarr[$m[0]]['st'] = $m[4];
				$slarr[$m[0]]['cl'] = $m[1];
			}
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
	
	return array($nmo,$lok,$tal);
}

//===================================================================
// Generate device metainfo for topology based device tables
function TopoTable($reg="",$cty="",$bld="",$flr="",$rom="",$nsd=0){

	global $link,$dev,$noloc,$alarr,$dreg,$dcity,$dbuild,$locsep,$now,$retire;

	if($nsd){
		$query	= GenQuery('devices','s','*','','',array('location'),array('like'),array( TopoLoc($reg,$cty,$bld,$flr,$rom) ) );
	}else{
		$query	= GenQuery('devices','s','*','','',array('snmpversion','location'),array('>','like'),array('0',TopoLoc($reg,$cty,$bld,$flr,$rom)),array('AND') );
	}
	$res	= DbQuery($query,$link);
	if($res){
		while( ($d = DbFetchRow($res)) ){
			$mn = array_key_exists($d[0],$alarr)?1:0;
			if( preg_match("/.+$locsep.+$locsep.+/",$d[10]) ){
				$l = explode($locsep, $d[10]);
				if($mn){
					$dreg[$l[0]]['mn']++;
					$dreg[$l[0]]['al'] += $alarr[$d[0]]['st'];
					$dcity[$l[0]][$l[1]]['mn']++;
					$dcity[$l[0]][$l[1]]['al'] += $alarr[$d[0]]['st'];
					$dbuild[$l[0]][$l[1]][$l[2]]['mn']++;
					$dbuild[$l[0]][$l[1]][$l[2]]['al'] += $alarr[$d[0]]['st'];
				}
				$dreg[$l[0]]['nd']++;
				$dcity[$l[0]][$l[1]]['nd']++;
				$dbuild[$l[0]][$l[1]][$l[2]]['nd']++;
				if($d[6] > 3) $dbuild[$l[0]][$l[1]][$l[2]]['nr']++;
				if($reg and $cty){
					$dev[$l[3]][$l[4]][$d[0]]['rk'] = $l[5];
					$dev[$l[3]][$l[4]][$d[0]]['ru'] = $l[6];
					$dev[$l[3]][$l[4]][$d[0]]['ip'] = long2ip($d[1]);
					$dev[$l[3]][$l[4]][$d[0]]['ty'] = $d[3];
					$dev[$l[3]][$l[4]][$d[0]]['co'] = $d[11];
					$dev[$l[3]][$l[4]][$d[0]]['po'] = $d[16];
					$dev[$l[3]][$l[4]][$d[0]]['ic'] = $d[18];
					$dev[$l[3]][$l[4]][$d[0]]['mn'] = $mn;
					$dev[$l[3]][$l[4]][$d[0]]['al'] = $alarr[$d[0]]['st'];
					$dev[$l[3]][$l[4]][$d[0]]['sz'] = $d[28]; 
					$dev[$l[3]][$l[4]][$d[0]]['sk'] = ($d[29])?$d[29]:1;
				}
			}else{
				$noloc[$d[0]]['ip'] = long2ip($d[1]);
				$noloc[$d[0]]['ty'] = $d[3];
				$noloc[$d[0]]['lo'] = $d[10];
				$noloc[$d[0]]['co'] = $d[11];
				$noloc[$d[0]]['po'] = $d[16];
				$noloc[$d[0]]['ic'] = $d[18];
				$noloc[$d[0]]['mn'] = $mn;
				if($mn) $noloc[$d[0]]['al'] = $alarr[$d[0]]['st'];
			}
		}
		DbFreeResult($res);
	}else{
		print DbError($link);
	}
}

//===================================================================
// Generate world table
function TopoRegs($siz=0){

	global $link,$debug,$map,$manlbl,$dreg,$locsep,$bg2,$netlbl,$addlbl,$loclbl,$poplbl;

	echo "<h2>$manlbl $netlbl</h2>\n";
	echo "<table class=\"content fixed\"><tr>\n";

	$col = 0;
	ksort($dreg);
	foreach (array_keys($dreg) as $r){
		$ur = urlencode($r);
		$nd = $dreg[$r]['nd'];
		$mn = isset( $dreg[$r]['mn']) ? $dreg[$r]['mn'] : 0;
		$al = isset( $dreg[$r]['al']) ? $dreg[$r]['al'] : 0;
		list($statbg,$stat) = StatusBg($nd,$mn,$al,'imga');
		if ($col == $_SESSION['col']){
			$col = 0;
			echo "</tr><tr>";
		}
	        echo "<td valign=\"bottom\" class=\"$statbg\"><center>\n";
	        $mstat = ($mn)?"$mn Monitored $stat":"";
		if($siz){
			echo "<a href=?reg=$ur><img src=\"img/32/glob.png\" title=\"$nd Devices $mstat\"></a><br>".substr($r,0,$_SESSION['lsiz'])."\n";
		}else{
			$qmap = $ur;
			$s    = ($_SESSION['gsiz'] < 3)?"160x120":"240x160";
			$rp   = preg_replace('/\W/','', $r);
			if($rp and $map){
				if( !file_exists("topo/$rp")  and !$_SESSION['snap'] ) mkdir("topo/$rp");
				if($map > 1){
					$loced = '';
					$ns = $ew = 0;
					$query	= GenQuery('locations','s','id,x,y,ns,ew,locdesc','','',array('region','city','building'),array('=','=','='),array($r,'',''),array('AND','AND'));
					$res	= DbQuery($query,$link);
					if (DbNumRows($res)){
						list($id,$x,$y,$ns,$ew,$des) = DbFetchRow($res);
						echo "$des<br>";
						$loced = '';
					}else{
						$loced = "<a href=\"Topology-Loced.php?reg=$ur\"><img src=\"img/16/ncfg.png\" title=\"$addlbl\"><a/>";
					}
					if($ns and $ew){
						$ns /= 10000000;
						$ew /= 10000000;
						$qmap= "$ns,$ew";
					}
					if($_SESSION['map']){
						echo "<a href=\"?reg=$ur&map=$map\"><img src=\"http://maps.google.com/maps/api/staticmap?zoom=5&size=$s&maptype=roadmap&sensor=false&markers=color:blue%7C$qmap\" title=\"$nd Devices $mstat, $com\" style=\"border:1px solid black\"></a><br>\n";
						echo "$loced<a href=\"http://maps.google.com/maps?q=$qmap\" target=\"window\"><img src=\"img/16/map.png\" title=\"Googlemaps\"></a>\n";
					}else{
						if( $_SESSION['snap'] ){
							$cache = "img/glob.png";
						}else{
							$cache = "topo/$rp/osm-$s.png";
							if( !file_exists($cache) and ini_get('allow_url_fopen') ){
								if(!$ns and !$ew){
									$url = "http://nominatim.openstreetmap.org/search?format=json&limit=1&q=$qmap";
									$geo = json_decode( file_get_contents($url), TRUE);
									if($debug){echo "<div class=\"textpad code good\"><b>$url</b><p>";print_r($geo); echo '</div>';}
									if($geo){
										$qmap= $geo[0][lat].",".$geo[0][lon];
									}
								}
								file_put_contents($cache, file_get_contents("http://staticmap.openstreetmap.de/staticmap.php?center=$qmap&zoom=5&size=$s") );
							}
						}
						echo "<a href=\"?reg=$ur&map=$map\"><img src=\"$cache\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
						echo "$loced<a href=\"http://nominatim.openstreetmap.org/search.php?q=$qmap\" target=\"window\"><img src=\"img/16/osm.png\" title=\"Openstreetmap\"></a>\n";
					}
				}else{
					if( file_exists("topo/$rp/map-$s.png") ){
						echo "<a href=\"?reg=$ur&map=$map\"><img src=\"topo/$rp/map-$s.png\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
					}else{
						echo "<a href=\"?reg=$ur&map=$map\"><img src=\"inc/drawmap.php?st[]=^$ur&dim=$s&lev=2&pos=s\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
					}
					$pop = NodPop( array('location'),array('like'),array("$r$locsep%"),array() );
				}
			}else{
				echo "<a href=\"?reg=$ur\"><img src=\"img/32/glob.png\" title=\"$nd Devices $mstat\"></a><br>\n";
			}
			echo "<a href=\"Topology-Map.php?st[]=$ur$locsep%&lev=2&fmt=png\"><img src=\"img/16/paint.png\" title=\"Topology-Map\"></a>\n";				
			echo "<a href=\"Devices-List.php?in[]=location&op[]=like&st[]=$ur$locsep%\">".substr($r,0,$_SESSION['lsiz'])."</a>\n";
		}
		$locp = ($pop)?" <a href=\"Nodes-List.php?in[]=location&op[]=like&st[]=$ur$locsep%\"><img src=\"img/16/nods.png\" title=\"$loclbl $poplbl\">$pop</a>":'';
		echo "$locp</center></td>\n";
	        $col++;
	}
	echo "</tr></table>\n";
}

//===================================================================
// Generate region table
function TopoCities($r,$siz=0){

	global $link,$map,$debug,$dcity,$locsep,$bg2,$netlbl,$errlbl,$tmplbl,$loclbl,$addlbl,$poplbl,$igrp,$notlbl,$rcvlbl;

	$ur  = urlencode($r);

	$query	= GenQuery('locations','s','id,x,y,ns,ew,locdesc','','',array('region','city','building'),array('=','=','='),array($r,'',''),array('AND','AND'));
	$res	= DbQuery($query,$link);
	if (DbNumRows($res)){
		list($id,$x,$y,$ns,$ew,$des) = DbFetchRow($res);
		echo "<h2>$r - $des</h2>\n";
	}else{
		echo "<h2>$r $netlbl</h2>\n";
	}
	echo "<table class=\"content fixed\"><tr>\n";

	$col = 0;
	ksort($dcity[$r]);
	foreach (array_keys($dcity[$r]) as $c){
		$nd = $dcity[$r][$c]['nd'];
		$ci = CtyImg($dcity[$r][$c]['nd']);
		$mn = isset( $dcity[$r][$c]['mn']) ? $dcity[$r][$c]['mn'] : 0;
		$al = isset( $dcity[$r][$c]['al']) ? $dcity[$r][$c]['al'] : 0;
		list($statbg,$stat) = StatusBg($nd,$mn,$al,'imga');
		$uc = urlencode($c);
		if ($col == $_SESSION['col']){
			$col = 0;
			echo "</tr><tr>";
		}
		echo "<td valign=\"bottom\" class=\"$statbg\"><center>\n";
		$mstat = ($mn)?"$mn Monitored $stat":"";
		if($siz){
			echo "<a href=\"?reg=$ur&cty=$uc\"><img src=\"img/$ci.png\" title=\"$nd Devices $mstat\"></a><br>".substr($c,0,$_SESSION['lsiz'])."\n";
		}else{
			$qmap = "$uc,$ur";
			$s    = ($_SESSION['gsiz'] < 3)?"160x120":"240x160";
			$cp   = preg_replace('/\W/','', $r).'/'.preg_replace('/\W/','', $c);
			if($cp and $map){
				if($map > 1){
					if( !file_exists("topo/$cp") and !$_SESSION['snap'] ) mkdir("topo/$cp", 0755, true);
					$ns = $ew = 0;
					$query	= GenQuery('locations','s','id,x,y,ns,ew,locdesc','','',array('region','city','building'),array('=','=','='),array($r,$c,''),array('AND','AND'));
					$res	= DbQuery($query,$link);

					if (DbNumRows($res)){
						list($id,$x,$y,$ns,$ew,$des) = DbFetchRow($res);
						echo "$des<br>";
						$loced = '';
					}else{
						$loced = "<a href=\"Topology-Loced.php?reg=$ur&cty=$uc\"><img src=\"img/16/ncfg.png\" title=\"$addlbl\"><a/>";
					}
					if($_SESSION['map']){
						$cachd = 2;							# Google maps mustn't be cached
					}else{
						$cache = "topo/$cp/osm-$s.png";
						$cachd = file_exists($cache);					# OSM is cached
					}
					if($ns and $ew){
						$ns /= 10000000;
						$ew /= 10000000;
						$qmap= "$ns,$ew";
					}elseif( ($map == 3 or !$cachd) and ini_get('allow_url_fopen') ){	# Weather and OSM only works with coordinates...
						$url = "http://nominatim.openstreetmap.org/search?format=json&limit=1&q=$qmap";
						$geo = json_decode( file_get_contents($url), TRUE);
						if($debug){echo "<div class=\"textpad code good\"><b>$url</b><p>";print_r($geo); echo '</div>';}
						if($geo){
							$ns = $geo[0][lat];
							$ew = $geo[0][lon];
							$qmap= "$ns,$ew";
						}
					}

					if($map == 3 and ini_get('allow_url_fopen') ){
						if($_SESSION['far']){
							$mod = 'imperial';
							$teu = 'F';
							$wiu = 'mph';
						}else{
							$mod = 'metric';
							$teu = 'C';
							$wiu = 'm/s';
						}
						$url = "http://api.openweathermap.org/data/2.5/weather?lat=$ns&lon=$ew&units=$mod";
						$wtr = json_decode( file_get_contents($url), TRUE);
						if($debug){echo "<div class=\"textpad code good\"><b>$url</b><p>";print_r($wtr); echo '</div>';}
						if( is_array($wtr) ){
							echo "<a href=\"http://openweathermap.org/city/".$wtr[id]."\" target=\"window\"><img src=\"http://openweathermap.org/img/w/".$wtr[weather][0][icon].".png\" title=\"".$wtr[weather][0][description]."\"></a>"; 
							echo "<img src=\"img/16/temp.png\" title=\"$tmplbl\">".round($wtr[main][temp])."$teu <img src=\"img/16/drop.png\" title=\"Humidity\">".$wtr[main][humidity]."% <img src=\"img/16/fan.png\" title=\"Wind\">".round($wtr[wind][speed])."$wiu<br>";
						}else{
							echo "$igrp[16] $notlbl $rcvlbl<br>";
						}
					}else{
						if($debug){echo "<div class=\"textpad code alrm\">Skip $igrp[16]: map=$map, allow_url_fopen ".ini_get('allow_url_fopen').'</div>';}
					}

					if($_SESSION['map']){
						echo "<a href=\"?reg=$ur&cty=$uc&map=$map\"><img src=\"http://maps.google.com/maps/api/staticmap?center=$qmap&zoom=11&size=$s&maptype=roadmap&sensor=false\" title=\"$nd Devices $mstat, $com\" style=\"border:1px solid black\"></a><br>\n";
						echo "$loced<a href=\"http://maps.google.com/maps?q=$qmap\" target=\"window\"><img src=\"img/16/map.png\" title=\"Googlemap\"></a>\n";
					}else{
						if( $_SESSION['snap'] ){
							$cache = "img/$ci.png";
						}elseif( !$cachd and ini_get('allow_url_fopen') ){
								 file_put_contents($cache, file_get_contents("http://staticmap.openstreetmap.de/staticmap.php?center=$qmap&zoom=11&size=$s") );
						}
						echo "<a href=\"?reg=$ur&cty=$uc&map=$map\"><img src=\"$cache\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
						echo "$loced<a href=\"http://nominatim.openstreetmap.org/search.php?q=$qmap\" target=\"window\"><img src=\"img/16/osm.png\" title=\"Openstreetmap\"></a>\n";
					}
				}else{
					echo "<center><a href=\"?reg=$ur&cty=$uc&map=$map\"><img src=\"inc/drawmap.php?st[]=^$ur$locsep$uc&dim=$s&lev=3&pos=s\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
					//too slow $pop = NodPop( array('location'),array('like'),array("$r$locsep$c$locsep%"),array() );
				}
			}else{
				echo "<a href=\"?reg=$ur&cty=$uc\"><img src=\"img/$ci.png\" title=\"$nd Devices $mstat\"></a><br>\n";
			}
			echo "<a href=\"Topology-Map.php?st[]=$ur$locsep$uc$locsep%&lev=3&fmt=png\"><img src=\"img/16/paint.png\" title=\"Topology-Map\"></a>\n";
			echo "<a href=\"Devices-List.php?in[]=location&op[]=like&st[]=$ur$locsep$uc$locsep%\"><b>".substr($c,0,$_SESSION['lsiz'])."</b></a>";
		}
		$locp = ($pop)?" <a href=\"Nodes-List.php?in[]=location&op[]=like&st[]=$ur$locsep$uc$locsep%\"><img src=\"img/16/nods.png\" title=\"$loclbl $poplbl\">$pop</a>":'';
		echo " $locp</center></td>\n";
		$col++;
	}
	echo "</tr></table>\n";
}

//===================================================================
// Generate city table
function TopoBuilds($r,$c,$siz=0){

	global $link,$map,$debug,$dbuild,$locsep,$bg2,$netlbl,$loclbl,$poplbl;

	$ur = urlencode($r);
	$uc = urlencode($c);

	$query	= GenQuery('locations','s','id,x,y,ns,ew,locdesc','','',array('region','city','building'),array('=','=','='),array($r,$c,''),array('AND','AND'));
	$res	= DbQuery($query,$link);
	if (DbNumRows($res)){
		list($id,$x,$y,$ns,$ew,$des) = DbFetchRow($res);
		echo "<h2>$c - $des</h2>\n";
	}else{
		echo "<h2>$c, $r $netlbl</h2>\n";
	}
	echo "<table class=\"content fixed\"><tr>\n";

	$col = 0;
	ksort($dbuild[$r][$c]);
	foreach (array_keys($dbuild[$r][$c]) as $b){
		$nr =  $dbuild[$r][$c][$b]['nr'];
		$nd =  $dbuild[$r][$c][$b]['nd'];
		$mn = isset( $dbuild[$r][$c][$b]['mn']) ? $dbuild[$r][$c][$b]['mn'] : 0;
		$al = isset( $dbuild[$r][$c][$b]['al']) ? $dbuild[$r][$c][$b]['al'] : 0;
		$bi = BldImg($nd,$b);
		list($statbg,$stat) = StatusBg($nd,$mn,$al,"imga");
		$ub = urlencode($b);
		if ($col == $_SESSION['col']){
			$col = 0;
			echo "</tr><tr>";
		}
	        echo "<td valign=\"bottom\" class=\"$statbg\"><center>\n";
	        $mstat = ($mn)?"$mn Monitored $stat":"";
		if($siz){
			echo "<a href=\"?reg=$ur&cty=$uc&bld=$ub\"><img src=\"img/$bi.png\" title=\"$nd Devices $mstat\"></a><br>".substr($b,0,$_SESSION['lsiz'])."\n";
		}else{
			$qmap = "$ub $uc,$ur";
			$s    = ($_SESSION['gsiz'] < 3)?"160x120":"240x160";
			$cp   = preg_replace('/\W/','', $r).'/'.preg_replace('/\W/','', $c);
			if($cp and $map){
				if($map > 1){
					if( !file_exists("topo/$cp") and !$_SESSION['snap'] ) mkdir("topo/$cp", 0755, true);
					$ns = $ew = 0;
					$query	= GenQuery('locations','s','id,x,y,ns,ew,locdesc','','',array('region','city','building'),array('=','=','='),array($r,$c,$b),array('AND','AND'));
					$res	= DbQuery($query,$link);
					if (DbNumRows($res)){
						list($id,$x,$y,$ns,$ew,$des) = DbFetchRow($res);
						echo "$des<br>";
						$loced = '';
					}else{
						$loced = "<a href=\"Topology-Loced.php?reg=$ur&cty=$uc&bld=$ub\"><img src=\"img/16/ncfg.png\" title=\"$addlbl\"><a/>";
					}
					if($ns and $ew){
						$ns /= 10000000;
						$ew /= 10000000;
						$qmap= "$ns,$ew";
					}
					if($_SESSION['map']){
						echo "<a href=\"?reg=$ur&cty=$uc&bld=$ub&map=$map\"><img src=\"http://maps.google.com/maps/api/staticmap?center=$qmap&zoom=16&size=$s&maptype=roadmap&sensor=false\" title=\"$nd Devices $mstat $com\" style=\"border:1px solid black\"></a><br>\n";
						echo "$loced<a href=\"http://maps.google.com/maps?q=$qmap\" target=\"window\"><img src=\"img/16/map.png\" title=\"Googlemap\"></a>\n";
					}else{
						if( $_SESSION['snap'] ){
							$cache = "img/$bi.png";
						}else{
							$cache = "topo/$cp/osm-".preg_replace('/\W/','',$b)."-$s.png";
							if( !file_exists($cache) and ini_get('allow_url_fopen') ){
								if(!$ns and !$ew){
									$url = "http://nominatim.openstreetmap.org/search?format=json&limit=1&q=$qmap";
									$geo = json_decode( file_get_contents($url), TRUE);
									if($debug){echo "<div class=\"textpad code good\"><b>$url</b><p>";print_r($geo); echo '</div>';}
									if($geo){
										$qmap= $geo[0][lat].",".$geo[0][lon];
									}
								}
								file_put_contents($cache, file_get_contents("http://staticmap.openstreetmap.de/staticmap.php?center=$qmap&zoom=12&size=$s") );
							}
						}
						echo "<a href=\"?reg=$ur&cty=$uc&bld=$ub&map=$map\"><img src=\"$cache\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
						echo "$loced<a href=\"http://nominatim.openstreetmap.org/search.php?q=$qmap\" target=\"window\"><img src=\"img/16/osm.png\" title=\"Openstreetmap\"></a>\n";
					}
				}else{
					echo "<a href=\"?reg=$ur&cty=$uc&bld=$ub&map=$map\"><img src=\"inc/drawmap.php?st[]=^$ur$locsep$uc$locsep$ub&dim=$s&lev=4&pos=d&xo=-20\" title=\"$nd Devices $mstat\" style=\"border:1px solid black\"></a><br>\n";
					//too slow $pop = NodPop( array('location'),array('like'),array("$r$locsep$c$locsep$b$locsep%"),array() );
				}
			}else{
				echo "<a href=\"?reg=$ur&cty=$uc&bld=$ub\"><img src=\"img/$bi.png\" title=\"$nd Devices $mstat\"></a>\n";
				if($nr > 1){
					echo "<img src=\"img/rtr2.png\" title=\"$nr routers\">";
				}elseif($nr == 1){
					echo "<img src=\"img/rtr1.png\" title=\"1 router\">";
				}
				echo "<br>";
			}
			echo "<a href=\"Topology-Map.php?st[]=$ur$locsep$uc$locsep$ub$locsep%&lev=4&fmt=png\"><img src=\"img/16/paint.png\" title=\"Topology-Map\"></a>\n";				
			echo "<a href=\"Devices-List.php?in[]=location&op[]=like&st[]=$ur$locsep$uc$locsep$ub$locsep%\" valign=\"bottom\"><b>".substr($b,0,$_SESSION['lsiz'])."</b></a>";
		}
		$locp = ($pop)?" <a href=\"Nodes-List.php?in[]=location&op[]=like&st[]=$ur$locsep$uc$locsep$ub$locsep%\"><img src=\"img/16/nods.png\" title=\"$loclbl $poplbl\">$pop</a>":'';
		echo "$locp</center></td>\n";
		$col++;
	}
	echo "</tr></table>\n";
}

//===================================================================
// Generate building table
function TopoFloors($r,$c,$b,$siz=0){

	global $link,$dev,$img,$modgroup,$self,$v,$place,$netlbl,$acslbl,$porlbl,$frelbl,$refresh;

	$query	= GenQuery('locations','s','id,x,y,ns,ew,locdesc','','',array('region','city','building'),array('=','=','='),array($r,$c,$b),array('AND','AND'));
	$res	= DbQuery($query,$link);
	if (DbNumRows($res)){
		list($id,$x,$y,$ns,$ew,$des) = DbFetchRow($res);
		echo "<h2>$b - $des</h2>\n";
	}else{
		echo "<h2>$b $place[b]</h2>\n";
	}
	echo "<table class=\"content fixed\">\n";

	uksort($dev, "floorsort");
	foreach (array_keys($dev) as $fl){
		echo "<tr>\n\t<td class=\"$modgroup[$self]2\" width=\"80\"><h3>\n";
		if(!$siz){echo "<img src=\"img/stair.png\"><br>\n";}
		echo "$fl</h3>\n";
		if(!$siz){
			$bas = "topo/".preg_replace('/\W/','', $r).'/'.preg_replace('/\W/','', $c).'/'.preg_replace('/\W/','', $b).'-'.preg_replace('/\W/','', $fl);
			foreach(glob("$bas*") as $f){
				list($ico,$ed) = FileImg($f);
				echo "$ico ";
			}
		}
		echo "</td>\n";
		$col = 0;
		$prm = "";
		ksort( $dev[$fl] );
		foreach(array_keys($dev[$fl]) as $rm){
			if($prm != $rm){
				$bi = ($bi == "imga")?"imgb":"imga";
			}
			$prm = $rm;
			foreach (array_keys($dev[$fl][$rm]) as $d){
				$ip = $dev[$fl][$rm][$d]['ip'];
				$po = $dev[$fl][$rm][$d]['po'];
				$ty = $dev[$fl][$rm][$d]['ty'];
				$di = $dev[$fl][$rm][$d]['ic'];
				$co = $dev[$fl][$rm][$d]['co'];
				$rk = $dev[$fl][$rm][$d]['rk'];
				$mn = $dev[$fl][$rm][$d]['mn'];
				$al = $dev[$fl][$rm][$d]['al'];
				$sz = $dev[$fl][$rm][$d]['sz'];
				$sk = ($dev[$fl][$rm][$d]['sk'] > 1)?"<img src=\"img/".$dev[$fl][$rm][$d]['sk'].".png\" title=\"Stack\">":"";
				list($statbg,$stat) = StatusBg(1,$mn,$al,$bi);
				$tit = ($stat)?$stat:$ty;
				$ud = urlencode($d);
				$ur = urlencode($r);
				$uc = urlencode($c);
				$ub = urlencode($b);
				$uf = urlencode($fl);
				$um = urlencode($rm);
				if ($col == $_SESSION['col']){
					$col = 0;
					echo "</tr><tr><td>&nbsp;</td>\n";
				}
				if($siz){
					echo "<td class=\"$statbg\" valign=\"top\"><center><img src=\"img/dev/$di.png\" title=\"$ip\"><br>$d</center></td>\n";
				}else{
					$ii = ($refresh)?0:IfFree($d);
					$inif = ($ii)?"<div style=\"float:right\"><a href=\"Devices-Interfaces.php?in[]=device&op[]==&st[]=$ud&co[]=AND&in[]=ifstat&op[]=<&st[]=3&co[]=AND&in[]=iftype&op[]=~&st[]=^(6|7|117)$&col[]=imBL&col[]=ifname&col[]=device&col[]=linktype&col[]=ifdesc&col[]=alias&col[]=lastchg&col[]=inoct&col[]=outoct&ord=lastchg\"><img src=\"img/p45.png\" title=\"$acslbl $porlbl $frelbl\">$ii</a></div>":'';
					$rkv = ($dev[$fl][$rm][$d]['ru'])?"<a href=\"Topology-Table.php?reg=$ur&cty=$uc&bld=$ub&fl=$uf&rm=$um\">$rm</a>":$rm;
					echo "<td class=\"$statbg\" valign=\"top\"><b>$rkv</b> $rk $inif<p><div style=\"text-align:center;\">\n";
					echo "<a href=\"Devices-Status.php?dev=$ud\">";
					echo "<img src=\"".(($img)?DevPanel($ty,$di,$sz)."\" width=\"".(preg_match('/^ph|^wa|^ca/',$di)?40:100)."\"":"img/dev/$di.png\"")." title=\"$tit\"></a>$sk<br><b>$d</b><br>\n";
					echo Devcli($ip,$po);
					echo"<p>$co</div></td>\n";
				}
				$col++;
			}
		}
	}
	echo "</tr></table>\n";
}

//===================================================================
// Generate room with a rackview
function TopoRoom($r,$c,$b,$f,$m){

	global $dev,$locsep,$bg2,$stalbl,$lstlbl,$debug;

	$ur = urlencode($r);
	$uc = urlencode($c);

	echo "<h2>$b $m</h2>\n";
	echo "<table class=\"fixed\"><tr>\n";

	$col = 0;
	if($debug){echo '<div class="textpad code noti">';}
	$rsiz[$dev[$f][$m][$d]['rk']] = 0;
	foreach( array_keys($dev[$f][$m]) as $d ){
		if( $dev[$f][$m][$d]['ru'] ){
			if($dev[$f][$m][$d]['sz'] < 1){
				$dev[$f][$m][$d]['wdh'] = 125;
				$dev[$f][$m][$d]['hgt'] = $dev[$f][$m][$d]['sk'];
				$dev[$f][$m][$d]['lwd'] = 8;
			}else{
				$dev[$f][$m][$d]['wdh'] = 250;
				$dev[$f][$m][$d]['hgt'] = $dev[$f][$m][$d]['sk'] * $dev[$f][$m][$d]['sz'];
				$dev[$f][$m][$d]['lwd'] = 24;
			}
			if( is_array($rack[$dev[$f][$m][$d]['rk']]) and array_key_exists($dev[$f][$m][$d]['ru'].";-4",$rack[$dev[$f][$m][$d]['rk']]) ){
				$xpos = 121;
			}else{
				$xpos = -4;
			}
			$rack[$dev[$f][$m][$d]['rk']][$dev[$f][$m][$d]['ru'].";".$xpos] = $d;
			$top = $dev[$f][$m][$d]['ru'] + $dev[$f][$m][$d]['hgt'];
			if( $top > $rsiz[$dev[$f][$m][$d]['rk']] ) $rsiz[$dev[$f][$m][$d]['rk']] = $top;
			if($debug){echo "$d Rack:".$dev[$f][$m][$d]['rk']." Top:$top RU:".$dev[$f][$m][$d]['ru']." H:".$dev[$f][$m][$d]['hgt']."<br>\n";}
		}else{
			if($debug){echo "$d Rack:".$dev[$f][$m][$d]['rk']." no RU<br>\n";}
		}
	}
	if($debug){echo '</div>';}


	ksort( $rack );
	foreach( array_keys($rack) as $rk ){
		if( $col == $_SESSION['col'] ){
			$col = 0;
			echo "</tr>\n<tr>";
		}
		$rupx = 23;
		echo "<td class=\"txta\" valign=\"bottom\"><h3>$rk</h3>";
		echo "<div style=\"height:".($rsiz[$rk]*$rupx-$rupx)."px;width:240px;border:12px solid #444444;background-color:#aaaaaa\">\n";
		echo "<div style=\"position:relative;bottom:0px;left:-6px;height:".($rsiz[$rk]*$rupx-$rupx)."px;width:244px;border-width:1px 4px;border-style:solid dotted;border-color:#888888\">\n";
		$rus = array_keys($rack[$rk]);
		sort($rus);
		foreach ($rus as $rup){
			$p = explode(';', $rup);
			if($debug){echo "$rup ".$rack[$rk][$rup]."<br>\n";}
			$ud = urlencode($rack[$rk][$rup]);
			$bgpanel = DevPanel($dev[$f][$m][$rack[$rk][$rup]]['ty'],$dev[$f][$m][$rack[$rk][$rup]]['ic'],$dev[$f][$m][$rack[$rk][$rup]]['sz']);
			$lbl     = "<span style=\"border: 1px solid black;background-color:#e0e0e0;font-size:80%\" title=\"RU:$p[0]\">".substr($rack[$rk][$rup],0,$dev[$f][$m][$rack[$rk][$rup]]['lwd'])."&nbsp;</span>\n";
			$lbl    .= (($dev[$f][$m][$rack[$rk][$rup]]['sk'] > 1)?"<img src=\"img/".$dev[$f][$m][$rack[$rk][$rup]]['sk'].".png\" style=\"background-color:#999\" title=\"Stack\">":"");
			if( !isset($_GET['print']) ){
				$lbl .= "<div style=\"float:right;background-color:#aaaaaa;border: 1px solid black\">";
				$lbl .= Devcli($dev[$f][$m][$rack[$rk][$rup]]['ip'],$dev[$f][$m][$rack[$rk][$rup]]['po'],2);
				$lbl .= "<a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/16/sys.png\" title=\"Device $stalbl\"></a>";
				$lbl .= "<a href=\"Nodes-List.php?in[]=device&op[]==&st[]=$ud&ord=ifname\"><img src=\"img/16/nods.png\" title=\"Nodes $lstlbl\"></a></div>";
			}
			$rpos = $rsiz[$rk] * $rupx - ($p[0] + $dev[$f][$m][$rack[$rk][$rup]]['hgt']) * $rupx;
			echo "<div style=\"position:absolute;top:${rpos}px;left:$p[1]px;height:".($dev[$f][$m][$rack[$rk][$rup]]['hgt']*23)."px;width:".$dev[$f][$m][$rack[$rk][$rup]]['wdh']."px;border:1px solid black;background-image: URL($bgpanel);\">\n$lbl</div>\n";
		}
		echo "</div></div></td>\n";
		$col++;
	}
	echo "</tr></table>\n";
}

//===================================================================
// Show the misfits
function TopoLocErr($siz=0){

	global $noloc,$img,$debug,$manlbl,$bg2,$loclbl,$errlbl;

	if( !count($noloc) ) return;

	echo "<br><p><h2>$loclbl $errlbl</h2>\n";
	echo "<table class=\"content fixed\"><tr>\n";

	$col = 0;
	foreach (array_keys($noloc) as $d){
		$ip = $noloc[$d]['ip'];
		$ty = $noloc[$d]['ty'];
		$di = $noloc[$d]['ic'];
		$lo = $noloc[$d]['lo'];
		$co = $noloc[$d]['co'];
		$po = $noloc[$d]['po'];
		$mn = $noloc[$d]['mn'];
		$al = $noloc[$d]['al'];
		list($statbg,$stat) = StatusBg(1,$mn,$al,'imga');
		$tit = ($stat)?$stat:$ty;
		$ud = urlencode($d);
		if ($col == $_SESSION['col']){
			$col = 0;
			echo "\n</tr><tr>\n";
		}
		if($siz){
			echo "<th class=\"$statbg\" valign=\"top\"><img src=\"img/dev/$di.png\" title=\"$lo, $co\"><br>$d</th>\n";
		}else{
			echo "<td class=\"$statbg\" valign=\"top\"><div style=\"text-align:center;\">\n";
			echo "<a href=\"Devices-Status.php?dev=$ud\">";
			echo "<img src=\"".(($img)?DevPanel($ty,$di,$sz)."\" width=\"".(preg_match('/^ph|^wa|^ca/',$di)?40:100)."\"":"img/dev/$di.png\"")." title=\"$tit\"></a>$sk<br><b>$d</b><br>\n";
			echo Devcli($ip,$po);
			echo"<br>$lo<br><span class=\"gry\">$co</span></div></td>\n";
		}
		$col++;
	}
	echo "</tr></table>\n";
}

//===================================================================
// Return image for test
function TestImg($srv,$topt="",$tres=""){

	global $nonlbl,$tstlbl,$sndlbl,$rcvlbl;

	if($srv == "ping")	{$img =  "relo";}
	elseif($srv == "uptime"){$img =  "clock";}
	elseif($srv == "dns")	{$img =  "abc";}
	elseif($srv == "ntp")	{$img =  "date";}
	elseif($srv == "http")	{$img =  "glob";}
	elseif($srv == "https")	{$img =  "glok";}
	elseif($srv == "telnet"){$img =  "loko";}
	elseif($srv == "ssh")	{$img =  "lokc";}
	elseif($srv == "mysql")	{$img =  "db";}
	elseif($srv == "cifs")	{$img =  "nwin";}
	elseif($srv == "none")	{$img =  "bcls";}
	else{$img =  "bdis";$srv = "$nonlbl Monitor";}

	return "<img src=\"img/16/$img.png\" title=\"$tstlbl: $srv".(($topt or $tres)?" $sndlbl $topt, $rcvlbl $tres":"")."\">";
}

?>
