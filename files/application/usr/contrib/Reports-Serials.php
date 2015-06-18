<?
# 2011-07-05	Steffen Scholz	Update queries to fix following:
#						- deleted devices will be removed from all tables and written to devdel-table for delete rrd's clearly (instead of delete only from devices-table and leave trashing rrd/interfaces/links/... behind)
#						- also serials INCLUDING "-" among other charachters will be proceeded (like MAC-addresses as serials) - only follwing strings will be ignored:	 "-" as single character ,"" (empty), including "noSuch", "err", 
# 2011-08-30	Steffen Scholz	update to new DB-Structure in nedi-1.0.6.220

	include("inc/header.php");

	$link = @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	
	if ($_POST['rmdevs']){
		if ($isadmin){
		foreach ($_POST['delete'] as $dld){
		$query	= GenQuery('devices','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Device ".DbError($link)."</h4>";}else{echo "<h5>Device $dld $dellbl OK</h5>";}
		$query	= GenQuery('interfaces','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>IF ".DbError($link)."</h4>";}else{echo "<h5>IF $dld $dellbl OK</h5>";}
		$query	= GenQuery('modules','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Modules ".DbError($link)."</h4>";}else{echo "<h5>Modules $dld $dellbl OK</h5>";}
		$query	= GenQuery('links','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Links ".DbError($link)."</h4>";}else{echo "<h5>Links $dld $dellbl OK</h5>";}
		$query	= GenQuery('links','d','','','',array('neighbor'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Links ".DbError($link)."</h4>";}else{echo "<h5>Links $dld $dellbl OK</h5>";}
		$query	= GenQuery('configs','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Config ".DbError($link)."</h4>";}else{echo "<h5>Config $dld $dellbl OK</h5>";}
		$query	= GenQuery('monitoring','d','','','',array('name'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Monitoring ".DbError($link)."</h4>";}else{echo "<h5>Monitoring $dld $dellbl OK</h5>";}
		$query	= GenQuery('incidents','d','','','',array('name'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Incidents ".DbError($link)."</h4>";}else{echo "<h5>Incidents $dld $dellbl OK</h5>";}
		$query	= GenQuery('vlans','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Vlans ".DbError($link)."</h4>";}else{echo "<h5>Vlans $dld $dellbl OK</h5>";}
		$query	= GenQuery('networks','d','','','',array('device'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Networks ".DbError($link)."</h4>";}else{echo "<h5>Networks $dld $dellbl OK</h5>";}
		$query	= GenQuery('events','d','','','',array('source'),array('='),array($dld) );
		if( !@DbQuery($query,$link) ){echo "<h4>Events ".DbError($link)."</h4>";}else{echo "<h5>Events $dld $dellbl OK</h5>";}
		$query	= GenQuery('devdel','i','','','',array('device','user','time'),'',array($dld,$_SESSION['user'],time()) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$dellbl $dld $updlbl OK</h5>";}
		$query = GenQuery('events','i','','','',array('level','time','source','info','class'),'',array('100',time(),$dld,"User $_SESSION[user] deleted this device",'usrd') );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h5>$msglbl $updlbl OK</h5>";}
#old:		DbQuery("DELETE FROM devices WHERE device = '".$rmdev."' LIMIT 1", $link);		SteffenScholz-2011-07-05
			}
		}
	}
	$collisions = array();
	$coll_serials = array();
	$devices = DbQuery("SELECT device, devip, serial, firstdis, lastdis FROM devices WHERE serial NOT REGEXP '^$|^-$|noSuch|err|n/a'", $link);
#old	$devices = DbQuery("SELECT name, ip, serial, firstseen, lastseen FROM devices WHERE serial REGEXP BINARY '^[-() A-Z0-9]{2,}$'", $link);		SteffenScholz-2011-07-05
	while ($device = DbFetchArray($devices)):
		if (@!in_array($device['serial'], $coll_serials)):
			$lookup = DbQuery("SELECT device, devip, firstdis, lastdis FROM devices WHERE serial = '".$device['serial']."' AND NOT device = '".$device['device']."'", $link);
			while ($colldev = DbFetchArray($lookup)):
				# determine reason for serial number collision
				# use 
				#	1 for change from dev1 to dev2
				#	2 for change from dev2 to dev1
				#	0 for unknown collision reason (i.e. devices appearance times overlap)
				if ($colldev['firstdis'] > $device['lastdis']):
					$reason = 1;
				elseif ($colldev['lastdis'] < $device['firstdis']):
					$reason = 2;
				else:
					$reason = 0;
				endif;
				$collisions[] = array('serial' => $device['serial'], 'dev1_name' => $device['device'], 'dev1_ip' => $device['devip'], 'dev2_name' => $colldev['device'], 'dev2_ip' => $colldev['devip'], 'reason' => $reason);
				$coll_serials[] = $device['serial'];
			endwhile;
		endif;
	endwhile;
?>
<h1>Serial number collisions</h1>
<br>
<?
	$collnr = count($collisions);
	if ($collnr): ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<table class="content">
	<tr class="<?= $modgroup[$self] ?>1">
		<td rowspan="<?= $collnr + 1 ?>"><img src="img/32/key.png"></td>
		<th>Serial number</th>
		<th>Device 1</th>
		<th>Reason</th>
		<th>Device 2</th>
		<th>Action</th>
	</tr>
<?
		foreach ($collisions as $collision): 
			if ($collision['reason'] == 2):
				$devOld = 'dev2';
				$devNew = 'dev1';
			else:
				$devOld = 'dev1';
				$devNew = 'dev2';
			endif;
?>
	<tr class="<?= $modgroup[$self] ?>2">
		<td><?= $collision['serial'] ?></td>
		<td><?= $collision[$devOld.'_name']." at ".long2ip($collision[$devOld.'_ip']) ?></td>
		<td><?= $collision['reason'] == 0 ? "has the same serial number as" : "seems to have become" ?></td>
		<td><?= $collision[$devNew.'_name']." at ".long2ip($collision[$devNew.'_ip']) ?></td>
		<td><?
			switch ($collision['reason']):
				case 1:
				case 2: ?>
			<input type="checkbox" name="delete[]" value="<?= $collision[$devOld.'_name'] ?>"> Delete <?= $collision[$devOld.'_name'] ?><br>		
<?
					break;
				case 0: ?>
			<input type="checkbox" name="delete[]" value="<?= $collision[$devOld.'_name'] ?>"> Delete <?= $collision[$devOld.'_name'] ?><br>
			<input type="checkbox" name="delete[]" value="<?= $collision[$devNew.'_name'] ?>"> Delete <?= $collision[$devNew.'_name'] ?><br>
<?
					break;
			endswitch; ?>
			<a href="Devices-List.php?ina=device&amp;opa=%3D&amp;sta=<?= $collision[$devOld.'_name'] ?>&amp;cop=OR&amp;inb=device&amp;opb=%3D&amp;stb=<?= $collision[$devNew.'_name'] ?>&amp;col%5B%5D=device&amp;col%5B%5D=devip&amp;col%5B%5D=serial&amp;col%5B%5D=location&amp;col%5B%5D=firstdis&amp;col%5B%5D=lastdis">Details</a></td>
	</tr>
<?
		endforeach;
?>
</table>
<br>
<input type="submit" name="rmdevs" value="Delete selected devices">
</form>
<?
	else: ?>
<div class="<?= $modgroup[$self] ?>">
No serial number collisions have been found!
</div>
<?
	endif;
	
	include("inc/footer.php");
?>
