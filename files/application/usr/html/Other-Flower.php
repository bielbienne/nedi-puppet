<?php
# Program: Other-Flower.php
# Programmer: Remo Rickli
# curl -d '{"switch": "00403c4a92facd80", "name":"flow1", "cookie":"0", "priority":"32768", "ingress-port":"1","active":"true", "actions":"output=2"}' http://10.10.10.171:8080/wm/staticflowentrypusher/json

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
include_once ("inc/libdev.php");

$_GET = sanitize($_GET);
$flc = isset($_GET['flc']) ? $_GET['flc'] : "floodlight";

$nam = isset($_GET['nam']) ? $_GET['nam'] : "";
$pri = isset($_GET['pri']) ? $_GET['pri'] : "";

$fvl = isset($_GET['fvl']) ? $_GET['fvl'] : "";
$fif = isset($_GET['fif']) ? $_GET['fif'] : "";
$fsm = isset($_GET['fsm']) ? $_GET['fsm'] : "";
$fdm = isset($_GET['fdm']) ? $_GET['fdm'] : "";
$fsi = isset($_GET['fsi']) ? $_GET['fsi'] : "";
$fdi = isset($_GET['fdi']) ? $_GET['fdi'] : "";
$fsp = isset($_GET['fsp']) ? $_GET['fsp'] : "";
$fdp = isset($_GET['fdp']) ? $_GET['fdp'] : "";
$fet = isset($_GET['fet']) ? $_GET['fet'] : "";
$fpr = isset($_GET['fpr']) ? $_GET['fpr'] : "";

$avl = isset($_GET['avl']) ? $_GET['avl'] : "";
$aif = isset($_GET['aif']) ? $_GET['aif'] : "";
$asm = isset($_GET['asm']) ? $_GET['asm'] : "";
$adm = isset($_GET['adm']) ? $_GET['adm'] : "";
$asi = isset($_GET['asi']) ? $_GET['asi'] : "";
$adi = isset($_GET['adi']) ? $_GET['adi'] : "";
$asp = isset($_GET['asp']) ? $_GET['asp'] : "";
$adp = isset($_GET['adp']) ? $_GET['adp'] : "";

$del = isset($_GET['del']) ? $_GET['del'] : "";
$clr = isset($_GET['clr']) ? $_GET['clr'] : "";
$dv = isset($_GET['dv']) ? $_GET['dv'] : array();

$link  = DbConnect($dbhost,$dbuser,$dbpass,$dbname);

?>
<h1>Flower Openflows</h1>

<form method="get" name="ofrm" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a>

</th>
<td valign="top" align="center"><h3>Flow <?= $sumlbl ?></h3>
<table>
<tr><td>
<?= $namlbl ?>
</td><td>
<input type="text" name="nam" value="<?= $nam ?>" size="15">
</td></tr>
<tr><td>
<?= $prilbl ?>
</td><td>
<input type="number" name="pri" value="<?= $pri ?>" size="5">
</td></tr>
</table>

</td>
<td valign="top" align="center"><h3><?= $fltlbl ?></h3>

<table>
<tr><td>
<img src="img/16/port.png" title="<?= $inblbl ?>">
</td><td>
<input type="number" name="fvl" value="<?= $fvl ?>" size="4" title="Vlan">
<input type="text" name="fif" value="<?= $fif ?>" size="8" title="IF #">
<input type="text" name="fet" value="<?= $fet ?>" size="8" title="Ether <?= $typlbl ?>">
<input type="text" name="fpr" value="<?= $fpr ?>" size="2" title="<?= $prolbl ?>">
</td></tr>
<tr><td>
<img src="img/16/node.png" title="MAC">
</td><td>
<input type="text" name="fsm" value="<?= $fsm ?>" size="18" title="<?= $srclbl ?> <?= $adrlbl ?>">
<input type="text" name="fdm" value="<?= $fdm ?>" size="18" title="<?= $dstlbl ?> <?= $adrlbl ?>">
</td></tr>
<tr><td>
<img src="img/16/net.png" title="IP">
</td><td>
<input type="text" name="fsi" value="<?= $fsi ?>" size="14" title="<?= $srclbl ?> <?= $adrlbl ?>">
<input type="text" name="fsp" value="<?= $fsp ?>" size="4" title="<?= $srclbl ?>  <?= $porlbl ?>">
<input type="text" name="fdi" value="<?= $fdi ?>" size="14" title="<?= $dstlbl ?> <?= $adrlbl ?>">
<input type="text" name="fdp" value="<?= $fdp ?>" size="4" title="<?= $dstlbl ?> <?= $porlbl ?>">
</td></tr>
</table>

</th>
<td valign="top" align="center"><h3><?= $actlbl ?></h3>

<table>
<tr><td>
<img src="img/16/port.png" title="<?= $oublbl ?>">
</td><td>
<input type="text" name="avl" value="<?= $avl ?>" size="5" title="Vlan (<?= (($verb1)?"$dellbl Tag":"Tag $dellbl") ?>: -)">
<input type="text" name="aif" value="<?= $aif ?>" size="15" title="IF #, all, controller, local, ingress-port, normal, flood">
</td></tr>
<tr><td>
<img src="img/16/node.png" title="MAC">
</td><td>
<input type="text" name="asm" value="<?= $asm ?>" size="15" title="<?= $srclbl ?> <?= $adrlbl ?>">
<input type="text" name="adm" value="<?= $adm ?>" size="15" title="<?= $dstlbl ?> <?= $adrlbl ?>">
</td></tr>
<tr><td>
<img src="img/16/net.png" title="IP">
</td><td>
<input type="text" name="asi" value="<?= $asi ?>" size="15" title="<?= $srclbl ?> <?= $adrlbl ?>">
<input type="text" name="asp" value="<?= $asp ?>" size="5" title="<?= $srclbl ?> <?= $porlbl ?>">
<input type="text" name="adi" value="<?= $adi ?>" size="15" title="<?= $dstlbl ?> <?= $adrlbl ?>">
<input type="text" name="adp" value="<?= $adp ?>" size="5" title="<?= $dstlbl ?> <?= $porlbl ?>">
</td></tr>
</table>

</th>
<th width="80">

<?= (Devcli($flc, 22, 2)) ?><p>
<input type="hidden" name="flc" value="<?= $flc ?>">
<input type="submit" value="<?= $addlbl ?>">

</th>
</tr></table>
<p>

<h2>Openflow Devices</h2>

<?PHP
if($del){
	$url = "http://$flc:8080/wm/staticflowentrypusher/json";
	$opt = array('http' =>
			array(
				'method'  => 'DELETE',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => "{\"name\":\"$del\"}"
			)
		);

	if($debug){
		echo "<div class=\"textpad code noti\">$url<p>\n";
		print_r($opt);
		echo "</div>";
	}

	$res = file_get_contents($url, false, stream_context_create($opt) );
	if( strstr($res,'Error') ){echo "<h4>$res</h4>";}else{echo "<h5>$dellbl Flow $del OK $res</h5>";}
}elseif($clr){
	$url = "http://$flc:8080/wm/staticflowentrypusher/clear/$clr/json";
	if($debug){
		echo "<div class=\"textpad code noti\">$url<p>\n";
		echo "</div>";
	}

	$res = file_get_contents($clr,0);
	if( strstr($res,'Error') ){echo "<h4>$res</h4>";}else{echo "<h5>$reslbl Flows $clr OK $res</h5>";}
}elseif( count($dv) ){
	$cdv = 0;
	foreach ($dv as $d){
		$cdv++;
		$flt  = ($pri)?",\"priority\":\"$pri\"":"";
		$flt .= ($fvl)?",\"vlan-id\":\"$fvl\"":"";
		$flt .= ($fif)?",\"ingress-port\":\"$fif\"":"";
		$flt .= ($fsm)?",\"src-mac\":\"$fsm\"":"";
		$flt .= ($fdm)?",\"dst-mac\":\"$fdm\"":"";
		$flt .= ($fsi)?",\"src-ip\":\"$fsi\"":"";
		$flt .= ($fdi)?",\"dst-ip\":\"$fdi\"":"";
		$flt .= ($fsp)?",\"src-port\":\"$fsp\"":"";
		$flt .= ($fdp)?",\"dst-port\":\"$fdp\"":"";
		$flt .= ($fet)?",\"ether-type\":\"$fet\"":"";
		$flt .= ($fpr)?",\"protocol\":\"$fpr\"":"";

		$act = "";
		$subact = array();
		if($avl == "-"){
			$subact[] = "strip-vlan";
		}elseif($avl){
			$subact[] = "set-vlan-id=$avl";
		}
		if($asm){$subact[] = "set-src-mac=$asm";}
		if($adm){$subact[] = "set-dst-mac=$adm";}
		if($asi){$subact[] = "set-src-ip=$asi";}
		if($adi){$subact[] = "set-dst-ip=$adi";}
		if($asp){$subact[] = "set-src-port=$asp";}
		if($adp){$subact[] = "set-dst-port=$adp";}
		if($aif){$subact[] = "output=$aif";}
		if( count($subact) ){$act = ",\"actions\":\"" . implode(",", $subact)."\"";}

		$url = "http://$flc:8080/wm/staticflowentrypusher/json";
		$opt = array('http' =>
				array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => "{\"switch\":\"$d\",\"name\":\"$nam-$cdv\",\"cookie\":\"0\",\"active\":\"true\" $flt $act}"
				)
			);
		if($debug){
			echo "<div class=\"textpad code noti\">$url<p>\n";
			print_r($opt);
			echo "</div>";
		}
		$res = file_get_contents($url, false, stream_context_create($opt) );
		if( strstr($res,'Error') ){echo "<h4>$res</h4>";}else{echo "<h5>$wrtlbl Flow $nam-$cdv OK $res</h5>";}

	}
}
$url = "http://$flc:8080/wm/core/controller/switches/json";
if($debug){
	echo "<div class=\"textpad code noti\">$url<p>\n";
	echo "</div>";
}

$ofd = array();
$jdv = file_get_contents($url,0);
if($jdv){
	$ofdarr = json_decode($jdv,1);
	if($debug){
		echo "<div class=\"textpad code good\">";
		print_r($ofdarr);
		echo "</div>";
	}
	foreach (array_keys($ofdarr) as $k) {
		$ifmac = substr(str_replace(":","",$ofdarr[$k]['dpid']),4);
		$query = GenQuery('interfaces','s','devices.*','','1',array('ifmac'),array('='),array($ifmac),array(),'LEFT JOIN devices USING (device)');
		$res   = DbQuery($query,$link);
		if( DbNumRows($res) ){
			$d = DbFetchRow($res);
			$ofd[$d[0]]['id'] = str_replace("","",$ofdarr[$k]['dpid']);
			$ofd[$d[0]]['cn'] = $ofdarr[$k]['inetAddress'];
			$ofd[$d[0]]['ma'] = $ofdarr[$k]['attributes']['DescriptionData']['manufacturerDescription'];
			$ofd[$d[0]]['hw'] = $ofdarr[$k]['attributes']['DescriptionData']['hardwareDescription'];

			$ofd[$d[0]]['ip'] = long2ip($d[1]);
			$ofd[$d[0]]['typ'] = $d[3];
			$ofd[$d[0]]['po'] = $d[16];
			$ofd[$d[0]]['ico'] = $d[18];
			$ofd[$d[0]]['stk'] = ($d[29] > 1)?"<img src=\"img/$d[29].png\" title=\"Stack\">":"";
		}else{
			echo "<h4>$dsclbl ".$ofdarr[$k]['inetAddress']."!</h4>";
		}
		DbFreeResult($res);
	}
}
?>

<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th>Device</th>
<th><?= $inflbl ?></th>
<th width="60%">Flows</th>
<th><?= $cmdlbl ?></th>
</tr>
<?php
	$row   = 0;
	foreach (array_keys($ofd) as $k) {
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		TblRow($bg);
		$ud  = urlencode($k);
		TblCell($k,"","class=\"$bi\" width=\"100px\"","<a href=\"Devices-Status.php?dev=$ud\"><img src=\"img/dev/".$ofd[$k]['ico'].".png\" title=\"".$ofd[$k]['typ']."\"></a>".$ofd[$k]['stk']."<br>","th-img");
		TblCell( $ofd[$k]['id']." ".$ofd[$k]['ma']." ".$ofd[$k]['hw']." ".Devcli($ofd[$k]['ip'],$ofd[$k]['po']) );
		echo "<td>\n";

		$url = "http://$flc:8080/wm/staticflowentrypusher/list/".$ofd[$k]['id']."/json";
		if($debug){
			echo "<div class=\"textpad code noti\">$url<p>\n";
			echo "</div>";
		}

		$jfl = file_get_contents($url);
		if($jfl){
			$flows = json_decode($jfl,1);
			if($debug){
				echo "<div class=\"textpad code good\">";
				print_r($flows);
				echo "</div>";
			}

			echo "<table class=\"full\">\n";
			$flo = 0;
			foreach (array_keys($flows[$ofd[$k]['id']]) as $f) {
				if ($flo % 2){$fb = "txta"; $fi = "imga";}else{$fg = "txtb"; $fi = "imgb";}
				echo "<tr class=\"$fi\"><td class=\"blu\" title=\"Flow $namlbl\">$f</td>\n";
				$nam  = urlencode($f);
				$ffsm = ($flows[$ofd[$k]['id']][$f]['match']['dataLayerSource'] != "00:00:00:00:00:00")?$flows[$ofd[$k]['id']][$f]['match']['dataLayerSource']:"";
				$ffdm = ($flows[$ofd[$k]['id']][$f]['match']['dataLayerDestination'] != "00:00:00:00:00:00")?$flows[$ofd[$k]['id']][$f]['match']['dataLayerDestination']:"";
				$ffsi = ($flows[$ofd[$k]['id']][$f]['match']['networkSource'] != "0.0.0.0")?$flows[$ofd[$k]['id']][$f]['match']['networkSource']:"";
				$ffdi = ($flows[$ofd[$k]['id']][$f]['match']['networkDestination'] != "0.0.0.0")?$flows[$ofd[$k]['id']][$f]['match']['networkDestination']:"";
				$ffsp = ($flows[$ofd[$k]['id']][$f]['match']['transportSource'] != "0")?$flows[$ofd[$k]['id']][$f]['match']['transportSource']:"";
				$ffdp = ($flows[$ofd[$k]['id']][$f]['match']['transportDestination'] != "0")?$flows[$ofd[$k]['id']][$f]['match']['transportDestination']:"";
				$ffet = ($flows[$ofd[$k]['id']][$f]['match']['dataLayerType'] != "0x0000")?$flows[$ofd[$k]['id']][$f]['match']['dataLayerType']:"";
				$ffpr = ($flows[$ofd[$k]['id']][$f]['match']['networkProtocol'] != "0")?$flows[$ofd[$k]['id']][$f]['match']['networkProtocol']:"";
				$ffvl = ($flows[$ofd[$k]['id']][$f]['match']['dataLayerVirtualLan'] != -1)?$flows[$ofd[$k]['id']][$f]['match']['dataLayerVirtualLan']:"";
				$ffif = ($flows[$ofd[$k]['id']][$f]['match']['inputPort'] != 0)?$flows[$ofd[$k]['id']][$f]['match']['inputPort']:"";

				echo "<td title=\"$inblbl $fltlbl\">".(($ffvl)?"Vlan:$ffvl":"")." ".(($ffif)?"IF:$ffif":"")." ".(($ffet)?"Type:$ffet":"")." ".(($ffpr)?"Proto:$ffpr":"")."</td>";
				echo "<td title=\"$srclbl $fltlbl\">$ffsm $ffsi $ffsp</td>";
				echo "<td title=\"$dstlbl $fltlbl\">$ffdm $ffdi $ffdp</td>\n";

				if( count($flows[$ofd[$k]['id']][$f]['actions']) ){
					$favl = $faif = $fadp = $fasm = $fadm = $fasi = $fasp = $fadi = $fadp = '';
					foreach ($flows[$ofd[$k]['id']][$f]['actions'] as $a) {
						if($a['type'] == 'SET_VLAN_ID'){
							$favl = $a['virtualLanIdentifier'];
						}elseif($a['type'] == 'STRIP_VLAN'){
							$favl = '-';
						}elseif($a['type'] == 'OUTPUT'){
							$faif = $a['port'];
						}elseif($a['type'] == 'SET_DL_SRC'){
							$fasm = $a['dataLayerAddress'];
						}elseif($a['type'] == 'SET_DL_DST'){
							$fadm = $a['dataLayerAddress'];
						}elseif($a['type'] == 'SET_NW_SRC'){
							$fasi = long2ip($a['networkAddress']);
						}elseif($a['type'] == 'SET_NW_DST'){
							$fadi =  long2ip($a['networkAddress']);
						}elseif($a['type'] == 'SET_TP_SRC'){
							$fasp = $a['transportPort'];
						}elseif($a['type'] == 'SET_TP_DST'){
							$fadp = $a['transportPort'];
						}
					}
					echo "<td title=\"$actlbl $srclbl\">$fasm $fasi $fasp</td><td title=\"$actlbl $dstlbl\">$fadm $fadi $fadp</td>\n";
					echo "<td title=\"$actlbl $oublbl\">".(($favl)?"Vlan:$favl":"")." ".(($faif)?"IF:$faif":"")."</td>\n";
				}else{
					echo "<td colspan=\"3\"><img src=\"img/16/bdis.png\" title=\"$dellbl\"></td>";
				}
				echo "</td><th width=\"80\"><a href=\"?flc=$flc&nam=$nam&fsm=$ffsm&fdm=$ffdm&fsi=$ffsi&fdi=$ffdi&fvl=$ffvl&fif=$ffif&fet=$ffet&fpr=$ffpr&avl=$favl&aif=$faif&adp=$fadp&asm=$fasm&adm=$fadm&asi=$fasi&asp=$fasp&adi=$fadi&adp=$fadp\">\n";
				echo "<img src=\"img/16/note.png\" title=\"$edilbl\"></a>\n";
				echo "<a href=\"?flc=$flc&del=$nam\"><img src=\"img/16/bcnl.png\" title=\"$dellbl\"></a>\n";
				echo "</th></tr>\n";
				$flo++;
			}
			echo "</table></td><th>\n";
			echo "<input type=\"checkbox\" name=\"dv[]\" value=\"".$ofd[$k]['id']."\"><a href=\"?flc=$flc&clr=".$ofd[$k]['id']."\"><p><img src=\"img/16/bcls.png\" title=\"$reslbl\"></a></th>\n";
		}
	}
?>
</table></form>
<table class="content">
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> Devices</td></tr>
</table>
<?PHP

include_once ("inc/footer.php");
?>
