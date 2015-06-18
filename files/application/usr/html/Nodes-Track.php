<?php
# Program: Nodes-Track.php
# Programmer: Remo Rickli

# load data local infile '/home/rickli/Downloads/switchprot_delta_report2010-08-18 21-10-33.csv' into table nodetrack fields terminated by ',' enclosed by '"' lines terminated by '\n' IGNORE 1 LINES (device,Ifname,Realname,alias,Name,@dip,preferred,manual,switchport,finalname) SET IP = INET_ATON(@dip)

error_reporting(E_ALL ^ E_NOTICE);

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmon.php");
include_once ("inc/libnod.php");

$_GET = sanitize($_GET);
$in = isset($_GET['in']) ? $_GET['in'] : array();
$op = isset($_GET['op']) ? $_GET['op'] : array();
$st = isset($_GET['st']) ? $_GET['st'] : array();
$co = isset($_GET['co']) ? $_GET['co'] : array();

$ord = isset($_GET['ord']) ? $_GET['ord'] : '';

if( isset($_GET['col']) ){
	$col = $_GET['col'];
	if($_SESSION['opt']){$_SESSION['ntrcol'] = $col;}
}elseif( isset($_SESSION['ntrcol']) ){
	$col = $_SESSION['ntrcol'];
}else{
	$col = array('tgtNS','nodetrack.device','nodetrack.ifname','value','source','alias','name');
}

$del = isset($_GET['del']) ? $_GET['del'] : "";

$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$ifn = isset($_GET['ifn']) ? $_GET['ifn'] : "";
$val = isset($_GET['val']) ? $_GET['val'] : "";
$src = isset($_GET['src']) ? $_GET['src'] : "";

$cols = array(	"tgtNS"=>"$tgtlbl",
		"nodetrack.device"=>"Device",
		"nodetrack.ifname"=>"IF $namlbl",
		"value"=>"$vallbl",
		"source"=>$srclbl,
		"alias"=>"IF Alias",
		"comment"=>"IF $cmtlbl",
		"name"=>$namlbl,
		"nodes.mac"=>"MAC $adrlbl",
		"nodes.vlanid"=>"Vlan",
		"oui"=>"OUI $venlbl",
		"usrname"=>$usrlbl,
		"time"=>$timlbl,
		"cfgNS"=>"$cfglbl"
		);

?>
<h1>Node Tracker</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" action="<?= $self ?>.php" name="track">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>

<td>
<?PHP Filters(); ?>

</td>
<th>

<select multiple name="col[]" size="6">
<?php
foreach ($cols as $k => $v){
       echo "<option value=\"$k\"".((in_array($k,$col))?" selected":"").">$v\n";
}
?>
</select>

</th>
<th width="80">

<input type="submit" value="<?= $sholbl ?>">
<p>
<input type="submit" name="del" value="<?= $dellbl ?>" onclick="return confirm('Tracker <?= $dellbl ?>?')" >

</th>
</tr></table></form><p>
<?php
}
$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if($del){
	$query	= GenQuery('nodetrack','d','','','',array($ina),array($opa),array($sta) );
	if( !DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$dellbl $ina $opa $sta OK</h5>";}
}

if( count($in) ){
	Condition($in,$op,$st,$co);

	TblHead("$modgroup[$self]2",1);

	$query	= GenQuery('nodetrack','s','nodetrack.device as device,nodetrack.ifname as ifname,value,source,alias,comment,name,nodes.mac as mac,oui,nodes.vlanid as vlanid,usrname,time',$ord,$lim,$in,$op,$st,$co, 'JOIN interfaces USING (device,ifname) LEFT JOIN nodes USING (device,ifname)');
	$res	= DbQuery($query,$link);
	if($res){
		$usta = urlencode($sta);
		$uopa = urlencode($opa);
		$row = 0;
		while( ($trk = DbFetchArray($res)) ){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			$cfgst	= '';
			list($cc,$lc) = Agecol($trk['time'],$trk['time'],$row % 2);

			if($dev and $dev ==  $trk['device'] and $ifn and $ifn == $trk['ifname']){
				$time = time();
				if($src){
					if($src == '-'){
						$setd = '';
					}elseif($src == 'comment'){
						$trk[$src] = preg_replace('/.+DP:(.+),.+/','$1',$trk[$src]);
						$setd = "value='$trk[$src]',";
					}else{
						$setd = "value='$trk[$src]',";
					}
					if( !DbQuery("UPDATE nodetrack SET ${setd}source='$src',usrname='$_SESSION[user]',time=$time WHERE device = '$dev' AND ifname = '$ifn';",$link) ){
						$cfgst = "<img src=\"img/16/bcnl.png\" title=\"" .DbError($link)."\">";
					}else{
						$cfgst = "<img src=\"img/16/bchk.png\" title=\"$srclbl = $src OK\">";
						$trk['source'] = $src;
						if($src != '-'){
							$trk['value'] = $trk[$src];
						}
					}
				}elseif($val){
					if( !DbQuery("UPDATE nodetrack SET value='$val',usrname='$_SESSION[user]',time=$time WHERE device = $dev' AND ifname = '$ifn';",$link) ){
						$cfgst = "<img src=\"img/16/bcnl.png\" title=\"" .DbError($link)."\">";
					}else{
						$cfgst = "<img src=\"img/16/bchk.png\" title=\"$vallbl = $val OK\">";
						$trk['value'] = $val;
					}
				}
				$trk['time'] = $time;
				$trk['user'] = $_SESSION['user'];
			}
			$bst = 'good';
			if($trk['source'] == '-' or $trk['source'] == ''){
				$bst = $bi;
			}elseif($trk['source'] == 'comment'){
				if($trk['value'] != preg_replace('/.+DP:(.+),.+/','$1',$trk['comment']) ){$bst = 'warn';}
			}else{
				if($trk['value'] != $trk[$trk['source']]){$bst = 'warn';}
			}
			TblRow($bg);
			foreach ($col as $c){
				if( $p = strpos($c,".") ){$c = substr($c,$p+1);}
				if($c == 'tgtNS'){
					echo "<th class=\"$bst\" width=\"50\">";
					if($trk['mac']){
						$img = Nimg("$trk[mac];$trk[oui]");
?>
<a href="Nodes-Status.php?mac=<?= $trk['mac'] ?>&vid=<?= $trk['vlanid'] ?>"><img src="img/oui/<?= $img ?>.png" title="<?= $trk['mac'] ?> (<?= $trk['oui'] ?>)"></a>
<?php
					}else{
						echo "<img src=\"img/p45.png\">";
					}
					echo "</th>";
				}elseif($c == 'value'){
					echo "<td class=\"blu\"><b>$trk[$c]</b></td>";
				}elseif($c == 'device'){
					echo "<td nowrap>\n";
					if( !isset($_GET['print']) and strpos($_SESSION['group'],$modgroup['Devices-Status']) !== false ){
						echo "<a href=\"Devices-Status.php?dev=".urlencode($trk[$c])."\"><img src=\"img/16/sys.png\"></a>\n";
					}
					echo "<a href=\"?in[]=nodetrack.device&op[]==&st[]=".urlencode($trk[$c])."\">$trk[$c]</a></td>";
				}elseif($c == $trk['source']){
					echo "<td class=\"blu\">$trk[$c]</td>";
				}elseif($c == "time"){
					echo "<td bgcolor=\"#$cc\">".date($datfmt, $trk[$c])."</td>";
				}elseif($c == 'cfgNS'){
?>
<td>
<form method="get">
<input type="hidden" name="in[]" value="<?= $in[0] ?>">
<input type="hidden" name="op[]" value="<?= $op[0] ?>">
<input type="hidden" name="st[]" value="<?= $st[0] ?>">
<input type="hidden" name="co[]" value="<?= $co[0] ?>">
<input type="hidden" name="in[]" value="<?= $in[1] ?>">
<input type="hidden" name="op[]" value="<?= $op[1] ?>">
<input type="hidden" name="st[]" value="<?= $st[1] ?>">
<input type="hidden" name="co[]" value="<?= $co[1] ?>">
<input type="hidden" name="in[]" value="<?= $in[2] ?>">
<input type="hidden" name="op[]" value="<?= $op[2] ?>">
<input type="hidden" name="st[]" value="<?= $st[2] ?>">
<input type="hidden" name="co[]" value="<?= $co[2] ?>">
<input type="hidden" name="in[]" value="<?= $in[2] ?>">
<input type="hidden" name="op[]" value="<?= $op[2] ?>">
<input type="hidden" name="st[]" value="<?= $st[2] ?>">
<input type="hidden" name="co[]" value="<?= $co[2] ?>">

<input type="hidden" name="dev" value="<?= $trk['device'] ?>">
<input type="hidden" name="ifn" value="<?= $trk['ifname'] ?>">
<input type="text" name="val" size="15" value="<?= $trk['value'] ?>" onfocus="select();"  onchange="this.form.submit();" title="<?= $wrtlbl ?> <?= $namlbl ?>">
<select size="1" name="src" onchange="this.form.submit();" title="<?= $namlbl ?> <?= $srclbl ?>">
<option value=""><?= $sellbl ?>
<option value="-">-
<option value="name"><?= $namlbl ?>
<option value="mac">MAC <?= $adrlbl ?>
<option value="alias">IF Alias
<option value="comment">IF <?= $cmtlbl ?>
</select> <?= $cfgst ?>
</form>
</td>
<?php
				}else{
					echo "<td>$trk[$c]</td>";
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
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?></td></tr>
</table>
<?php
}
include_once ("inc/footer.php");
?>
