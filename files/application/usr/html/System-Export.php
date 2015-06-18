<?php
# Program: System-Export.php
# Programmer: Pascal Voegeli, Remo Rickli (resorting to system calls for performance, avoid NULL on empty chars aroun 466)
#
# NOTE: For security reasons only admins can use the export function now. Remove "$isadmin AND " on line 160, if you don't care!
#
$printable = 1;
$exportxls = 0;

// Header.php contains the navigation and general settings for the UI
include_once("inc/header.php");

$sqltbl = isset($_GET['sqltbl']) ? $_GET['sqltbl'] : array();
$act    = isset($_GET['act']) ? $_GET['act'] : "";
$exptbl = isset($_GET['exptbl']) ? $_GET['exptbl'] : "";
$query  = isset($_GET['query']) ? $_GET['query'] : "";
$sep    = isset($_GET['sep']) ? $_GET['sep'] : "";
$quotes = isset($_GET['quotes']) ? "checked" : "";
$colhdr = isset($_GET['colhdr']) ? "checked" : "";
$type   = isset($_GET['type']) ? $_GET['type'] : "htm";
$timest = isset($_GET['timest']) ? "checked" : "";
$conv   = isset($_GET['conv']) ? "checked" : "";

// A connection to the database has to be made
$dblink = DbConnect($dbhost, $dbuser, $dbpass, $dbname);
?>

<!-- Begin of the HTML part -->

<h1>Export</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" name="export" action="<?= $self ?>.php">

<table class="content" >
	<tr class="<?= $modgroup[$self] ?>1">
		<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>

		<!-- This <th> contains the export part of the form -->
		<td valign="top" align="center">

			<!-- If the module is loaded without any GET variables the selected action is "Export" -->
			<h3><input type="radio" name="act" value="c" <?= $act=="c"?"checked":"" ?>><?= $cmdlbl ?></input></h3>
			<table><tr><td><?= $sellbl ?>:</td>
			<!-- There are 3 different types of things that can be selected in this box: -->
			<!-- If a database table is selected, a "SELECT * FROM..." query is automatically written to the text box -->
			<!-- If the "Device Config Files" entry is selected, the separator and quotes fields are disabled and a specific -->
			<!-- query is written to the text box -->
			<!-- If one of the meaningless entiries is selected nothing's changed in the text box -->
			<td><select size="1" name="exptbl"  size="1" onchange="
				if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='none') {
					document.forms['export'].sep.disabled=false;
					document.forms['export'].quotes.disabled=false;
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='cfgfiles') {
					document.forms['export'].query.value='SELECT device, config, time FROM configs';
					document.forms['export'].sep.disabled=true;
					document.forms['export'].quotes.disabled=true;
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='eventret') {
					document.forms['export'].query.value='DELETE FROM events where time < <?= (time() - $retire * 86400) ?>';
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='iftrkret') {
					document.forms['export'].query.value='DELETE FROM iftrack where ifupdate < <?= (time() - $retire * 86400) ?>';
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='iptrkret') {
					document.forms['export'].query.value='DELETE FROM iptrack where ipupdate < <?= (time() - $retire * 86400) ?>';
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='lnkret') {
					document.forms['export'].query.value='DELETE FROM links where lastdis < <?= (time() - $retire * 86400) ?>';
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='uprcom') {
					document.forms['export'].query.value='UPDATE devices set readcomm=<new> where readcomm=<old>';
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='flush') {
					document.forms['export'].query.value='FLUSH LOGS';
				}
				else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='reset') {
					document.forms['export'].query.value='RESET MASTER';
				}
				else {
					document.forms['export'].query.value='SELECT * FROM '+document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value;
					document.forms['export'].sep.disabled=false;
					document.forms['export'].quotes.disabled=false;
				}
			">
				<option value="none" class="warn">--- DB <?= $lstlbl ?> ---</option>
			<?php  // Some PHP code
				// All the names of the database tables are collected and put into the select box
				$res = DbQuery(GenQuery("", "h"), $dblink);
				while($n = DbFetchRow($res)){
					echo "<option value=\"".$n[0]."\"".($n[0]==$exptbl?" selected":"").">$sholbl ".$n[0]."</option>\n";
				}
				echo "<option value=\"none\" class=\"warn\">--- ".(($verb1)?"$cmdlbl $igrp[31]":"$igrp[31] $cmdlbl")." ---</option>";
				echo "<option value=\"cfgfiles\"".($exptbl=="cfgfiles"?" selected":"").">$cfglbl $buplbl</option>\n";
				echo "<option value=\"eventret\"".($exptbl=="eventret"?" selected":"").">$dellbl $msglbl $agelbl > $retire $tim[d]</option>\n";
				echo "<option value=\"iftrkret\"".($exptbl=="iftrkret"?" selected":"").">$dellbl IFtrack $agelbl > $retire $tim[d]</option>\n";
				echo "<option value=\"iptrkret\"".($exptbl=="iptrkret"?" selected":"").">$dellbl IPtrack $agelbl > $retire $tim[d]</option>\n";
				echo "<option value=\"lnkret\"".($exptbl=="lnkret"?" selected":"").">$dellbl Links $laslbl $updlbl > $retire $tim[d]</option>\n";
				echo "<option value=\"uprcom\"".($exptbl=="uprcom"?" selected":"").">$updlbl SNMP $realbl Community</option>\n";
				echo "<option value=\"flush\"".($exptbl=="flush"?" selected":"").">$dellbl bin-logs</option>\n";
				echo "<option value=\"reset\"".($exptbl=="reset"?" selected":"").">$reslbl DB</option>\n";
			?>
			</select>
			Separator:
			<select size="1" name="sep">
			<?php  // Some PHP code
				$separators = array(";", ";;", ":", "::", ",", "/");
				foreach($separators as $s){
					echo "<option value=\"$s\"".($s==$sep?" selected":"").">".$s."</option>\n";
					#echo "<option value=\"".$sep."\"".($s==$sep?" selected":"").">".$s."</option>\n";		<-- Pascals Kaese ;-)
				}
			?>
			</select>
			&nbsp;Quotes <input type="checkbox" name="quotes" <?= $quotes ?>>
			Header <input type="checkbox" name="colhdr" <?= $colhdr ?>></td></tr>
			<tr><td>Query:</td>
			<td>
			<textarea rows="3" name="query" cols="80"><?= $query ?></textarea>
			</table>
		</td>
	
		<!-- This <th> contains the SQL dump part of the form -->
		<td valign="top" align="center">
			<h3><input type="radio" name="act" value="e" <?= $act=="e"?"checked":"" ?>><?= $explbl ?></input></h3>
			<p>
				<select multiple size="6" name="sqltbl[]">
				<?php  // Some PHP code
					$res = DbQuery(GenQuery("", "h"), $dblink);
					while($n = DbFetchRow($res)){
						echo "<option value=\"".$n[0]."\"".(in_array($n[0], $sqltbl)?" selected":"").">".$n[0]."</option>\n";
					}
				?>
				</select>
			</p>
		</td>

		<!-- This <th> contains the archive settings -->
		<th width="80" valign="top" align="center">
			<h3><?= $dstlbl ?></h3>
			<p>
			<select size="1" name="type">
				<option value="htm" <?= ($type=="htm")?" selected":"" ?>>html</option>
				<option value="plain" <?= ($type=="plain")?" selected":"" ?>>plain</option>
				<option value="gz" <?= ($type=="gz")?" selected":"" ?>>Gzip</option>
				<option value="bz2" <?= ($type=="bz2")?" selected":"" ?>>Bzip2</option>
			</select>
			<p>
			<img src="img/16/abc.png" title="<?= (($verb1)?"$addlbl $timlbl":"$timlbl $addlbl") ?>/<?= $frmlbl ?> IP">
			<input type="checkbox" name="timest" <?= $timest ?>>
<?php
if( 0 and $backend == 'mysql'){// doesn't work properly and ipv6-bin need converting...
?>
			<br><img src="img/16/db.png" title="<?= $frmlbl ?>: Postgres">
			<input type="checkbox" name="conv" <?= $conv ?>>
<?php
}
?>
			<p>
			<input type="submit" value="<?= $cmdlbl ?>">
		</th>
	</tr>
</table>

</form>

<?php
}
if($isadmin and $act == "c") {
	$start = microtime(1);
	// An empty query produces an error message
	if($query == "") {
		echo "<h4>Query $emplbl!</h4>";
	}
	// Execute and return status, if the query is not an SELECT query
	elseif(!preg_match ('/^(SELECT|EXPLAIN)/i',$query) ) {
		if( !$res = DbQuery($query, $dblink) ) {
			echo "<h4>$query $errlbl</h4>";
		}else{
			echo "<h5>$query OK</h5>";
		}
	}
	// And finally, if the query is invalid for any other reasons, an error message is printed
	elseif(!($res = DbQuery($query, $dblink))) {
		echo "<h4>".DbError($dblink)."</h4>";
	}
	// If the query starts with "SELECT device, config, time FROM configs " a config export is made
	// instead of a CSV export
	elseif(strtoupper(substr($query, 0, 43)) == "SELECT DEVICE, CONFIG, TIME FROM CONFIGS") {

		$row = array();
		$configs = array();

		echo "<h3>DB $explbl $cfglbl</h3>\n<div class=\"textpad txta\">\n";

		while($row = DbFetchArray($res)) {
			$filename = rawurlencode($row['device'])."_".date("Ymd_Hi", $row['time']).".conf";

			$cfgfile = fopen("log/$filename", "w");
			fwrite($cfgfile, $row['config']);
			fclose($cfgfile);
			$configs[] = $filename;

			echo "$wrtlbl log/$filename<br>\n";
			flush();
		}

		$dbf = "log/configs_$_SESSION[user]".(($timest)?'_'.date("Ymd_Hi"):'');
		$cfg = join(' ', $configs);
		if($type == "bz2"){
			$dbf .= '.tbz';
			system("tar jcf $dbf -C log $cfg");
		}else{
			$dbf .= '.tgz';
			system("tar zcf $dbf -C log $cfg");
		}
		echo "<p>$wrtlbl log/$dbf<p>\n";

		foreach($configs as $cfg) {
			unlink("log/$cfg");
			echo "$dellbl log/$cfg<br>\n";
		}
		echo "<b>$buplbl $fillbl <a href=\"$dbf\">$dbf</a> <a href=\"System-Files.php?del=".urlencode($dbf)."\"><img src=\"img/16/bcnl.png\" title=\"$dellbl\"></a></b>\n";		
	}
	// HTML Override
	elseif($type == "htm") {
		echo "<h2>$query</h2>";
		echo "<table class=\"content\"><tr class=\"$modgroup[$self]2\">";
		for ($i = 0; $i < DbNumFields($res); ++$i) {
			$field = DbFieldName($res, $i);
			echo  "<th>$i $field</th>\n";
		}
		echo "</tr>\n";
		$row = 0;
		while($l = DbFetchArray($res)) {
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			$row++;
			TblRow($bg);
			foreach($l as $id => $field) {
				if( $field and preg_match("/^(if|nod|mon)ip6$/",$id) ){
					echo "<td>".(( $backend == 'Pg')?$field:inet_ntop($field))."</td>";
				}elseif($field and $timest and  preg_match("/^(orig|dev|if|nod|mon)ip$/",$id) ){
					echo "<td>".long2ip($field)."</td>";
				}elseif($timest and preg_match("/^(first|last|time|(if|ip|os)?update)/",$id) ){
					echo "<td>".date($_SESSION['date'],$field)."</td>";
				}else{
					echo "<td>$field</td>";
				}
			}
			echo  "</tr>\n";
		}
?>
</table>
<table class="content" >
<tr class="<?= $modgroup[$self] ?>2"><td><?= $row ?> <?= $vallbl ?>, <?= round( microtime(1) - $start,2 ) ?> <?= $tim['s'] ?></td></tr>
</table>
		<?php
	}else {
		echo "<h3>$collbl $vallbl $explbl</h3>\n<div class=\"textpad txta\">\n";

		$tbl = join(' ',$sqltbl);
		$dbf = "log/nedi-$_SESSION[user]".(($timest)?'_'.date("Ymd_Hi"):'').".csv";
		$csv = DbCsv($res, $sep, ($quotes=="checked"?"on":""), $dbf, $colhdr);
		echo "Created file $dbf from table ".$exptbl.($quotes=="checked"?" with surrounding quotes":"");
		echo " using separator '".$sep."'<br>\n";
		flush();

		if($type == "gz"){
			system("gzip -f $dbf");
			$dbf .= '.gz';
		}elseif($type == "bz2"){
			system("bzip2 -f $dbf");
			$dbf .= '.bz2';
		}
		flush();
		echo "<p><b>$fillbl <a href=\"$dbf\">$dbf</a> <a href=\"System-Files.php?del=".urlencode($dbf)."\"><img src=\"img/16/bcnl.png\" title=\"$dellbl\"></a></b>\n";

	}
}elseif($isadmin and $act == "e") {

	echo "<h3>DB $explbl</h3>\n<div class=\"textpad txta\">\n";

	$dok = 2;
	if( $backend == 'mysql'){
		$tbl = join(' ',$sqltbl);
		$cnv = ($conv)?"--compatible=postgresql":"";
		$dbf = "log/$dbname-$_SESSION[user]".(($timest)?'_'.date("Ymd_Hi"):'').".msq";
		$dok = system("mysqldump $cnv -u$dbuser -p$dbpass $dbname $tbl > $dbf");
	}elseif( $backend == 'Pg'){
		$tbl = '-t'.join(' -t',$sqltbl);
		$dbf = "log/$dbname-$_SESSION[user]".(($timest)?'_'.date("Ymd_Hi"):'').".psq";
		$dok = system("export PGPASSWORD=$dbpass;pg_dump -c -U$dbuser $tbl $dbname > $dbf");
	}

	if($dok){
		echo "<h4>$errlbl ($dok) dump $dbname > $dbf</h4>";
		flush();
	}else{
		echo "$wrtlbl $dbf<br>";
		if($type == "gz"){
			system("gzip -f $dbf");
			$dbf .= '.gz';
		}elseif($type == "bz2"){
			system("bzip2 -f $dbf");
			$dbf .= '.bz2';
		}
		flush();
		echo "<p><b>$fillbl <a href=\"$dbf\">$dbf</a> <a href=\"System-Files.php?del=".urlencode($dbf)."\"><img src=\"img/16/bcnl.png\" title=\"$dellbl\"></a></b>\n";
	};

	echo "</div>\n";
}elseif($isadmin and $act == "trunc") {
	$query = GenQuery($sqltbl[0],"t");
	if( !DbQuery($query,$dblink) ){echo "<h4>".DbError($dblink)."</h4>";}else{echo "<h5>".(($verb1)?"$sqltbl[0] $dellbl $vallbl":"$sqltbl[0] $vallbl $dellbl")." OK</h5>";}
}elseif($isadmin and $act == "opt") {
	$query = GenQuery($sqltbl[0],"o");
	if( !DbQuery($query,$dblink) ){echo "<h4>".DbError($dblink)."</h4>";}else{echo "<h5>".(($verb1)?"$optlbl $sqltbl[0]":"$sqltbl[0] $optlbl")." OK</h5>";}
}elseif($isadmin and $act == "rep") {
	$query = GenQuery($sqltbl[0],"r");
	if( !DbQuery($query,$dblink) ){echo "<h4>".DbError($dblink)."</h4>";}else{echo "<h5>".(($verb1)?"$replbl $sqltbl[0]":"$sqltbl[0] $replbl")." OK</h5>";}
}else {
	echo "<h2>DB $dbname $sumlbl</h2>\n";

	$res = DbQuery(GenQuery("", "v"), $dblink);
	while($l = DbFetchRow($res)) {
		echo "<h3>$l[0]</h3>";
	}
	DbFreeResult($res);

	$res = DbQuery(GenQuery("", "x"), $dblink);
	echo "<table class=\"content\"><tr class=\"$modgroup[$self]2\">";
	for ($i = 0; $i < DbNumFields($res); ++$i) {
		$field = DbFieldName($res, $i);
		echo  "<th>$field</th>\n";
	}
	echo "</tr>\n";
	$row = 0;
	while($l = DbFetchArray($res)) {
		if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
		$row++;
		TblRow($bg);
		foreach($l as $id => $field) {
			echo "<td>$field</td>";
		}
		echo  "</tr>\n";
	}
	echo  "</table><p>\n";
	DbFreeResult($res);

	$res = DbQuery(GenQuery("", "h"), $dblink);
	$col = 0;
	echo "<table class=\"full fixed\"><tr>\n";
	while($tab = DbFetchRow($res)){
		if($col == intval($_SESSION['col']/2)){echo "</tr><tr>";$col=0;}
		echo "<td class=\"helper\">\n\n<table class=\"content\" ><tr class=\"$modgroup[$self]2\">\n";
		echo "<th colspan=\"3\">$tab[0]</th><th>NULL</th><th>KEY</th><th>DEF</th></tr>\n";
		$cres = DbQuery(GenQuery($tab[0], "c"), $dblink);
		$row = 0;
		while($c = DbFetchRow($cres)){
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			echo "<tr class=\"$bg\"><th class=\"$bi\">\n";
			echo "$row</th><td class=\"drd\">$c[0]</td><td>$c[1]</td><td class=\"prp\">$c[2]</td><td class=\"blu\">$c[3]</td><td class=\"grn\">$c[4]</td></tr>\n";
			$row++;
		}
		$recs = DbFetchRow(DbQuery(GenQuery($tab[0], 's','count(*)'), $dblink));
		DbFreeResult($cres);
	?>
</table>
<table class="content" >
<tr class="<?= $modgroup[$self] ?>2"><td>
<div style="float:right">

<?php  if($recs[0]) { ?>
<a href="?act=c&exptbl=links&sep=%3B&query=SELECT+*+FROM+<?= $tab[0] ?> limit <?= $listlim ?>"><img src="img/16/eyes.png" title="<?= $sholbl ?>"></a>
<?}
if($isadmin) { ?>
<a href="?act=opt&sqltbl[]=<?= $tab[0] ?>"><img src="img/16/hat2.png" title="<?= $optlbl ?>"></a>
<a href="?act=rep&sqltbl[]=<?= $tab[0] ?>"><img src="img/16/dril.png" title="<?= $replbl ?>"></a>
<a href="?act=trunc&sqltbl[]=<?= $tab[0] ?>"><img src="img/16/bcnl.png" onclick="return confirm('<?= (($verb1)?"$dellbl $vallbl":"$vallbl $dellbl") ?>, <?= $cfmmsg ?>')" title="<?= (($verb1)?"$dellbl $vallbl":"$vallbl $dellbl") ?>"></a>
<?}?>
</div>

<?= $recs[0] ?> <?= $vallbl ?></td></tr>
</table>

</td><?php
		$col++;
	}
	DbFreeResult($res);
?>
</tr></table>
<?php
}
// Now the database connection can be closed
DbClose($dblink);

// This is the footer on the very bottom of the page
include_once("inc/footer.php");


//================================================================================
// Name: DbCsv()
// 
// Description: Creates a CSV file of a given SQL query result.
//              When calling the function you can choose if you want
//              to have quotes around the elements of the CSV file.
//              The separator between the elements has to be provided when
//              calling DbCsv()
//
// Parameters:
//     $res		- A valid SQL result identifier
//     $sep		- The separator to put between the elements
//         		  This can also be longer than one character
//     $quotes	- "on" to have quotes around the elements
//     $outfile	- The name of the file that should be created
//
// Return value:
//     none
//

function DbCsv($res, $sep, $quotes, $outfile, $head) {

	global $datfmt;
	// The CSV file is created and opened
	$csvfile = fopen($outfile, "w");

	// Add column header, if desired
	if($head){
		$csv = "";
		for ($i = 0; $i < DbNumFields($res); ++$i) {
			if($quotes == "on") $csv .= "\"";
			$csv .= DbFieldName($res, $i);
			echo "$csv ";
			if($quotes == "on") $csv .= "\"";
			$csv .= $sep;
		}
		// The last separator of a line is always cut off
		$csv = trim($csv, $sep);

		// For each row a single line of the file is used
		$csv .= "\n";

		// After having prepared the CSV row, it is written to the file
		fwrite($csvfile, $csv);
	}

	// The rows of the given result are processed one after the other
	while($row = DbFetchArray($res)) {
		$csv = "";
		// Each element is added to the string individually
		foreach($row as $id => $field) {
			if(preg_match("/^(origip|ip)$/",$id) ){$field = long2ip($field);}
			if(preg_match("/^(firstseen|lastseen|time|i[fp]update)$/",$id) ){$field = date($datfmt,$field);}
			// If quotes are wished, they are put around the element
			if($quotes == "on") $csv .= "\"";
			$csv .= $field;
			if($quotes == "on") $csv .= "\"";
			$csv .= $sep;
		}
		// The last separator of a line is always cut off
		$csv = trim($csv, $sep);

		// For each row a single line of the file is used
		$csv .= "\r\n";

		// After having prepared the CSV row, it is written to the file
		fwrite($csvfile, $csv);
	}

	// When finished, the CSV file is closed
	fclose($csvfile);
}

?>
