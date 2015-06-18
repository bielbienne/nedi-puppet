<?php
# Program: Other-Nagios.php
# Programmer: ?, updated Robert Smith

	$nagios_cfg_dir = "/tmp/nagios";
	if (!is_dir("$nagios_cfg_dir")) {
		mkdir("$nagios_cfg_dir");
	}

	include("inc/header.php");
	$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	
	if ($_POST['mkconf']):
		if (!( $_POST['device'] == 0 && $_POST['location'] == 0 && $_POST['exttype'] == "a" || $_POST['exptype'] == "r" && $_POST['expression'] == "")):
			switch ($_POST['exptype']):
				case "d": 
					$WHERE1 = "device = '".$_POST['device']."'";
					break;
				case "l": 
					$WHERE1 = "location REGEXP BINARY '^".$_POST['location']."'";
					break;
				case "r":
					$WHERE1 = "device REGEXP BINARY '".$_POST['expression']."'";
					break;
			endswitch;
			switch ($_POST['limit']):
				case "time":
					switch ($_POST['period']):
						case "h": 
							$time = 3600 * $_POST['time'];
							break;
						case "d":
							$time = 86400 * $_POST['time'];
							break;
						case "w":
							$time = 604800 * $_POST['time'];
							break;
					endswitch;
					$WHERE2 = "lastdis > ".(time() - $time);
					break;
			endswitch;
			($WHERE1 || $WHERE2) ? ($WHERE = " WHERE ".$WHERE1.($WHERE1 && $WHERE2 ? " AND " : NULL).$WHERE2) : $WHERE = "";
			$query = "SELECT device, devip, description FROM devices".$WHERE;
			$result = DbQuery($query, $link);
			echo mysql_error();
			while ($row = DbFetchArray($result)):
				//deternime device type
				switch (TRUE):
					case preg_match('#[C|c]isco|WS-C|AIR-(AP|BR)|[C|c]at#', $row['description']):
						if (preg_match('#C[0-9]{4}-K9W7#', $row['description'])):
							$type['alias'] = $row['device']." (Cisco Access Point)";
							$type['group'] = "switches";
						elseif (preg_match('#Version [2-7]\.#', $row['description'])):
							$type['alias'] = $row['device']." (Cisco, old firmware)";
							$type['group'] = "switches";
						else:
							$type['alias'] = $row['device']." (Cisco)";
							$type['group'] = "switches_cisco";
						endif;
						break;
					case preg_match('#[P|p]ro[C|c]urve#', $row['description']):
						$type['alias'] = $row['device']." (HP ProCurve)";
						$type['group'] = "switches_hp";
						break;
					default:
						$type['alias'] = $row['device']." (Switch)";
						$type['group'] = "switches";
				endswitch;
				$replace_array = array("&" => "+");
				$config = "define host {\n";
				$config .= "	use			generic-switch\n";
				$config .= "	host_name		".strtr($row['device'], $replace_array)."\n";
				$config .= "	alias			".$type['alias']."\n";
				$config .= "	address			".long2ip($row['devip'])."\n";
				$config .= "	hostgroups		".$type['group']."\n";
				$config .= "	notifications_enabled	".($_POST['en'] ? "1" : "0")."\n";
				$config .= "	}";
				if ($_POST['tofile']):
					$file = fopen($nagios_cfg_dir."/".$row['device'].".cfg", "w");
					$bytes_written = fputs($file, $config);
					fclose($file);
				endif;
				if ($_POST['toscreen']):
					$configScreenOut .= " ##################################".str_repeat("#", strlen($row['device']))."##<br>";
					$configScreenOut .= " # configuration script for device ".$row['device']." #<br>";
					$configScreenOut .= " ##################################".str_repeat("#", strlen($row['device']))."##<br><br>";
					$configScreenOut .= str_replace("\n", "<br>", $config)."<br><br>";
				endif;
			endwhile;
		elseif ($_POST['exptype'] == "d"):
			$ERR = "Select a device!";
		elseif ($_POST['exptype'] == "l"):
			$ERR = "Select a location!";
		else:
			$ERR = "No regular expression given!";
		endif;
	endif;
?>
<h1>Export Nagios configuration scripts</h1>
<br>
<form action="<?= $self ?>.php" method="post">
<table>
	<tr class="<?= $modgroup[$self] ?>1">
		<th rowspan="2"><img src="img/32/nag.png"></td>
		<th>Export configuration script for:</th>
		<th>Export options:</th>
		<th rowspan="2"><input type="submit" name="mkconf" value="Export"></th>
	</tr>
	<tr class="<?= $modgroup[$self] ?>1">
		<td>
<input type="radio" checked name="exptype" value="d"> Device: <select name="device">
	<option value="0" selected>Select a device:</option>
<?php
	$result = DbQuery("SELECT device FROM devices ORDER BY UPPER(device)", $link);
	while ($row = DbFetchArray($result)): ?>
	<option<?= $row['device'] == $_POST['device'] ? " selected" : NULL ?>><?= $row['device'] ?></option>
<?php
	endwhile;
?>
</select><br>
<br>

<input type="radio" name="exptype" value="l"<?= $_POST['exptype'] == "l" ? " checked" : NULL ?>> All devices of location: <select name="location">
	<option value="0" selected>Select a location:</option>
<?php
	$result = DbQuery("SELECT DISTINCT SUBSTRING_INDEX(location, ';', 1) AS region FROM devices WHERE location REGEXP BINARY '[A-Z]{2,3};[A-Za-z]+;[A-Za-z0-9]+;.*' ORDER BY location", $link);
	while ($row = DbFetchArray($result)):
#		$locations[] = $row['region'];
?>
	<option><?= $row['region'] == $_POST['location'] ? " selected" : NULL ?><?= $row['region'] ?></option>
<?php
		$region_result = DbQuery("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(location, ';', 2), ';', -1) AS city FROM devices WHERE SUBSTRING_INDEX(location, ';', 1) = '".$row['region']."' ORDER BY location", $link);
		while ($region_row = DbFetchArray($region_result)):
#			$locations[$row['region']][] = $region_row['city'];
?>
	<option value="<?= $row['region'] ?>;<?= $region_row['city'] ?>"<?= $row['region'].";".$region_row['city'] == $_POST['location'] ? " selected" : NULL ?>> - <?= $region_row['city'] ?> (<?= $row['region'] ?>)</option>
<?php
			$city_result = DbQuery("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(location, ';', 3), ';', -1) AS building FROM devices WHERE SUBSTRING_INDEX(location, ';', 2) = '".$row['region'].";".$region_row['city']."' ORDER BY location", $link);
			while ($city_row = DbFetchArray($city_result)):
#				$locations[$row['region']][$region_row['city']][] = $city_row['building'];
?>
	<option value="<?= $row['region'] ?>;<?= $region_row['city'] ?>;<?= $city_row['building'] ?>"<?= $row['region'].";".$region_row['city'].";".$city_row['building'] == $_POST['location'] ? " selected" : NULL ?>> -- <?= $city_row['building'] ?> (<?= $region_row['city'] ?>, <?= $row['region'] ?>)</option>
<?php
			endwhile;
		endwhile;
	endwhile;
?>
</select><br>
<br>
<input type="radio" name="exptype" value="r"<?= $_POST['exptype'] == "r" ? " checked" : NULL ?>> All devices matching following regular expression:
	<input type="text" name="expression" value="<?= $_POST['expression'] ?>"> <input type="submit" name="~" value="Show devices">
<br>
<br>
<input type="radio" name="exptype" value="a"> All devices
		</td>
		<td>
<input type="checkbox" name="tofile" value="print"<?= !$_POST['mkconf'] || $_POST['tofile'] ? " checked" : NULL ?>> Write configuration file to Nagios configuration file directory<br>
<br>
<input type="checkbox" name="toscreen" value="print"<?= !$_POST['mkconf'] || $_POST['toscreen'] ? " checked" : NULL ?>> Show configuration script here<br>
<br>
<input type="checkbox" name="limit" value="time"<?= $_POST['limit'] ? " checked" : NULL ?>> Don't select devices not seen for <input type="text" name="time" size="3" value="<?= $_POST['time'] ?>"> <select name="period">
	<option value="h"<?= $_POST['period'] == "h" ? " selected" : NULL ?>>Hours</option>
	<option value="d"<?= $_POST['period'] == "d" ? " selected" : NULL ?>>Days</option>
	<option value="w"<?= $_POST['period'] == "w" ? " selected" : NULL ?>>Weeks</option>
</select><br>
<br>
<input type="checkbox" name="en" value="enable"<?= $_POST['en'] ? " checked" : NULL ?>> Enable notifications for selected host(s)<br>
		</td>
	</tr>
</table>
</form>
<?php
	if ($ERR): ?>
<div class="textpad warn"><center><b><?= $ERR ?></b></center></div>
<?php
	endif;

	if ($_POST['~']):
		$result = DbQuery("SELECT device, devip FROM devices WHERE device REGEXP BINARY '".$_POST['expression']."'", $link);
?>
<div class="textpad devConf">
<?php
		if (DbNumRows($result)): ?>
<b>Hosts matching regular expression '<?= $_POST['expression'] ?>':</b><br>
<?php
			while ($row = DbFetchArray($result)): ?>
<br><?= $row['device'] ?> (<?= long2ip($row['devip']) ?>)
<?php
			endwhile;
		else: ?>
Regular expression '<?= $_POST['expression'] ?>': No hosts found!
<?php
		endif;
?>
</div>
<?php
	endif;
	
	if ($_POST['tofile'] && $bytes_written): ?>
<div class="textpad good"><center><b>Configuration scripts written to Nagios configuration file directory.<br>
Changes to Nagios will apply when Nagios server is restarted.</b></center></div>
<?php
	endif;

	if ($_POST['toscreen'] && $configScreenOut): ?>
<div class="textpad devConf">
<pre>
<?= $configScreenOut ?>
</pre>
</div>
<?php
	endif;
?>
<br>
<?php
	include("inc/footer.php");
?>
