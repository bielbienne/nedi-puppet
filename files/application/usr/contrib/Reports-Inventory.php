<?php
//===============================
// Report Contract Inventory.
// [b]Export in PDF format with html2pdf library from http://html2pdf.fr/[/b]
//===============================
session_start(); 
if( isset($_GET['export']) and $_GET['export']=='pdf'){
	//like header.php
	ini_set("memory_limit","128M");									# Enterprise network support
	$self = preg_replace("/.*\/(.+).php/","$1",$_SERVER['SCRIPT_NAME']);
	require_once ('inc/libmisc.php');
	if(isset ($_SESSION['group']) ){
		ReadConf($_SESSION['group']);
	}else{
		echo "<script>document.location.href='index.php?goto=".rawurlencode($_SERVER["REQUEST_URI"])."';</script>\n";
		die;
	}
	require_once ("inc/libdb-msq.php");
	//start capture for pdf conversion
	ob_start();
	echo "<link href=\"themes/{$_SESSION['theme']}.css\" type=\"text/css\" rel=\"stylesheet\">";
	// special tag for html2pdf
	echo "<page orientation=\"paysage\" footer=\"\" style=\"font-size: 8px\">";
}else{
	//stabdard header
	include_once ("inc/header.php");
}
if (isset($_GET['os']) and is_array($_GET['os'])) $os = $_GET['os']; 
// Connect Database
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if( !isset($_GET['export']) and $_GET['export']!='pdf'){
?>
<h1>Contract Review</h1>
<form method="get" action="<?= $self ?>.php">
	<table class="content">
		<tr class="<?= $modgroup[$self] ?>1">
			<th>
				<center><table>
				<tr><td>Operating System</td>
				<td><select multiple name="os[]" size=2>
					<option value="ALL">ALL</option>
					<?php
					$query = "select distinct os from devices where os!='-'";
					$res	= @DbQuery($query,$link);
					while( $dev = @DbFetchArray($res) ){
						echo "<option value=\"{$dev['os']}\" ".((in_array($dev['os'], $os))? "selected" : "") .">{$dev['os']}</option>\n";
					}
					@DbFreeResult($res);
					?>
				</select>
				</td></tr>
				</table></center>
			</th>
			<th width=80>
				<input type="submit" name="submit" value="<?= $shobtn ?>">
				<input type="submit" name="export" value="pdf">
			</th>
		</tr>
	</table>
</form>
<?php
}
if (!isset($_GET['export']) and !isset($_GET['submit'])){
	include_once ("inc/footer.php");
	exit;
}
// make sql filter for each OS
$osfilter = '';
if (isset($_GET['os'])){
	foreach ($os as $selectedos){
		if ($selectedos != 'ALL') $osfilter .= "and d.os = '$selectedos' ";
	}
 }
 
// Query number of devices ( count devices that have serial number and devices serial like stock serial and device state active )
$query = "select count(*) as cpt 
			from devices d, stock s 
			where
			d.serial !='-' 
			and d.serial regexp s.serial 
			and s.state = 100 $osfilter
			order by d.services,d.name";
$res	= @DbQuery($query,$link);
$cpt 	= @DbFetchArray($res);
// Query data from active devices
$query = "select d.name,d.serial,d.type,d.bootimage,d.location,d.description, inet_ntoa(d.ip) as ip  
			from devices d, stock s 
			where
			d.serial !='-' 
			and 
			d.serial regexp s.serial 
			and s.state = 100 $osfilter
			order by d.services,d.name";
$res	= @DbQuery($query,$link);
if($res){
	echo "<table class=\"content\" CELLSPACING=2 COLS=4 RULES=NONE BORDER=0>";
	echo "<tr><td colspan=5 align=center style='font-size: 20px; color: white'>Inventaire des Mat&eacute;riels:  {$cpt['cpt']} unit&egrave;s au ".date("d-m-Y").".</td></tr>";
		while( $dev = @DbFetchArray($res) ){
			// retreive links of device
			$liens = '';
			$query2 = "SELECT concat('<b style=\'color: green\'>',ifname, '</b> --> [', neighbour, ' - ', nbrifname, ']') as link 
						FROM links 
						where 
						device='{$dev['name']}' 
						order by device,ifname";
			$res2	= DbQuery($query2,$link);
			while($links = DbFetchArray($res2)){
				$liens .= ",  {$links['link']}";
			}
			$liens = substr($liens, 3);
echo <<<TABLE
		<tr>
			<th class="imga" ROWSPAN=3 VALIGN=MIDDLE width=130 nowrap>
				{$dev['name']}
			</th>
			<td class="imgb" width=100 nowrap>
				<b>Type|</b>&nbsp;{$dev['type']}
			</td>
			<td class="imgb" width=150 nowrap>
				<b>Serial|</b>&nbsp;{$dev['serial']}
			</td>
			<td class="imgb" width=160 nowrap>
				<b>Boot Image|</b>&nbsp;{$dev['bootimage']}
			</td>
			<td class="imgb" width=300 nowrap>
				<b>Location|</b>&nbsp;{$dev['location']}
			</td>
		</tr>
		<tr>
			<td class="imgb" colspan=4 align=left width=730 >
				<b>Description|&nbsp;</b>{$dev['description']}
			</td>
		</tr>
		<tr>
			<td class="imgb" >
				<b>Adresse|&nbsp;</b> <i style="color: red">{$dev['ip']}</i>
			</td>
			<td class="imgb" colspan=3 align=left width=730 >
				<b>Liens|&nbsp;</b>{$liens}
			</td>
		</tr>
		<tr><td height="0px"></td></tr>
		
TABLE;
	}
}	
// if pdf export
if ($_GET['export']=='pdf'){
	// special tag for html2pdf
	echo "</table></page><br><br><page orientation=\"paysage\" footer=\"\" style=\"font-size: 8px\">";
}else{
	echo "</table><br><br>";
}
//quey number of module : count modules that is not present in devices list and have a serial number and slot not a number
//				for exemple in devices list appear one switch for a stack with a serial number in modules appear other switches that compose the stack
$query = "select count(*) as cpt 
			from devices d, stock s, modules m 
			where
			d.serial = s.serial
			and d.name = m.device
			and d.serial != m.serial
			and m.serial !='-'
			and m.serial !=''
			and s.state=100
			and m.slot not regexp '^[:digit:]'
			and m.model != '-'  $osfilter 
			order by d.name, m.description";
$res	= @DbQuery($query,$link);
$cpt = @DbFetchArray($res);

//query data for modules
$query="select m.device as name, m.model as type, m.serial as serial, m.hw as hw, m.fw as fw, m.description as description
			from devices d, stock s, modules m 
			where
			d.serial = s.serial
			and d.name = m.device
			and d.serial != m.serial
			and m.serial !='-'
			and m.serial !=''
			and s.state=100
			and m.slot not regexp '^[:digit:]'
			and m.model != '-'  $osfilter 
			order by d.name, m.description";

$res	= @DbQuery($query,$link);
if($res){
	echo "<table class=\"content\" CELLSPACING=2 COLS=4 RULES=NONE BORDER=0>";
	echo "<tr><td colspan=5 align=center style='font-size: 20px; color: white'>Inventaire des Modules:  {$cpt['cpt']} unit&egrave;s au ".date("d-m-Y").".</td></tr>";
	while( $dev = @DbFetchArray($res) ){
echo <<<TABLE
		<tr>
			<th class="imga" ROWSPAN=2 VALIGN=MIDDLE width=130 nowrap>
				{$dev['name']}
			</th>
			<td class="imgb" width=100 nowrap>
				<b>Type|</b>&nbsp;{$dev['type']}
			</td>
			<td class="imgb" width=150 nowrap>
				<b>Serial|</b>&nbsp;{$dev['serial']}
			</td>
			<td class="imgb" width=160 nowrap>
				<b>Hardware Version|</b>&nbsp;{$dev['hw']}
			</td>
			<td class="imgb" width=300 nowrap>
				<b>Firmware Version|</b>&nbsp;{$dev['fw']}
			</td>
		</tr>
		<tr>
			<td class="imgb" colspan=4 align=left width=730 >
				<b>Description|&nbsp;</b>{$dev['description']}
			</td>
		</tr>
		<tr><td height="0px"></td></tr>
		
TABLE;
	}
}	
echo "</table>";

if ($_GET['export']!='pdf'){
	include_once ("inc/footer.php");
}else{
	echo "</page>";
	//end of capture
	$content = ob_get_clean();
	// conversion HTML => PDF
	//use your own path
	require_once("/var/www/html2pdf/html2pdf.class.php");
	$html2pdf = new HTML2PDF('P','A4','fr');
//	$html2pdf->setModeDebug();
	$html2pdf->WriteHTML($content, isset($_GET['vuehtml']));
	$html2pdf->Output('Devices_Inventory.pdf');
}
?>
