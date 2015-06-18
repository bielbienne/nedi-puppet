<?PHP
//===============================
// SNMP related functions
// Based on libsnmp.php v0.3 by Steffen Neuser
// Using a 1/4 of timeout to avoid hanging GUI
//===============================

function Walk($ip, $ver, $cm, $oid, $t=1000000, $r=2){

	global $debug, $comms;

	if($ver == 3 and $comms[$cm]['pprot']){
		if($debug){echo "<div class=\"textpad noti \">snmpwalk -v3 -c$cm ".$comms[$cm]['aprot']."/".$comms[$cm]['pprot']." $ip $oid ($t usec * $r)</div>";}
		return snmp3_real_walk($ip, $cm, 'authPriv', $comms[$cm]['aprot'], $comms[$cm]['apass'], $comms[$cm]['pprot'], $comms[$cm]['ppass'], ".$oid", $t );
	}elseif ($ver == 3 and $comms[$cm]['aprot']){
		if($debug){echo "<div class=\"textpad noti \">snmpwalk -v3 -c$cm ".$comms[$cm]['aprot']." $ip $oid ($t usec * $r)</div>";}
		return snmp3_real_walk($ip, $cm, 'authNoPriv', $comms[$cm]['aprot'], $comms[$cm]['apass'], 'DES', '', ".$oid", $t );
	}elseif ($ver == 2){
		if($debug){echo "<div class=\"textpad noti \">snmpwalk -v2c -c$cm $ip $oid ($t usec * $r)</div>";}
		return snmp2_real_walk($ip, $cm, ".$oid", $t );
	}else{
		if($debug){echo "<div class=\"textpad noti \">snmpwalk -v1 -c$cm $ip $oid ($t usec * $r)</div>";}
		return snmprealwalk($ip, $cm, ".$oid", $t );
	}
}

function Get($ip, $ver, $cm, $oid, $t=1000000, $r=2){

	global $debug, $comms;

	if($ver == 3 and $comms[$cm]['pprot']){
		if($debug){echo "<div class=\"textpad noti \">snmpget -v3 -c$cm ".$comms[$cm]['aprot']."/".$comms[$cm]['pprot']." $ip $oid ($t usec * $r)</div>";}
		return snmp3_get($ip, $cm, 'authPriv', $comms[$cm]['aprot'], $comms[$cm]['apass'], $comms[$cm]['pprot'], $comms[$cm]['ppass'], ".$oid", $t, $r);
	}elseif ($ver == 3 and $comms[$cm]['aprot']){
		if($debug){echo "<div class=\"textpad noti \">snmpget -v3 -c$cm ".$comms[$cm]['aprot']." $ip $oid ($t usec * $r)</div>";}
		return snmp3_get($ip, $cm, 'authNoPriv', $comms[$cm]['aprot'], $comms[$cm]['apass'], 'DES', '', ".$oid", $t, $r);
	}elseif ($ver == 2){
		if($debug){echo "<div class=\"textpad noti \">snmpget -v2c -c$cm $ip $oid ($t usec * $r)</div>";}
		return snmp2_get($ip, $cm, ".$oid", $t, $r);
	}else{
		if($debug){echo "<div class=\"textpad noti \">snmpget -v1 -c$cm $ip $oid ($t usec * $r)</div>";}
		return snmpget($ip, $cm, ".$oid", $t, $r);
	}
}

function Set($ip, $ver, $cm, $oid, $f, $v, $t=1000000, $r=2){

	global $debug, $comms;

	if($ver == 3 and $comms[$cm]['pprot']){
		if($debug){echo "<div class=\"textpad noti \">snmpset -v3 -c$cm ".$comms[$cm]['aprot']."/".$comms[$cm]['pprot']." $ip $oid $f $v ($t usec * $r)</div>";}
		return snmp3_set($ip, $cm, 'authPriv', $comms[$cm]['aprot'], $comms[$cm]['apass'], $comms[$cm]['pprot'], $comms[$cm]['ppass'], ".$oid", $f, $v, $t );
	}elseif ($ver == 3 and $comms[$cm]['aprot']){
		if($debug){echo "<div class=\"textpad noti \">snmpset -v3 -c$cm ".$comms[$cm]['aprot']." $ip $oid $f $v ($t usec * $r)</div>";}
		return snmp3_set($ip, $cm, 'authNoPriv', $comms[$cm]['aprot'], $comms[$cm]['apass'], 'DES', '', ".$oid", $f, $v, $t );
	}elseif ($ver == 2){
		if($debug){echo "<div class=\"textpad noti \">snmpset -v2c -c$cm $ip $oid $f $v ($t usec * $r)</div>";}
		return snmp2_set($ip, $cm, ".$oid", $f, $v, $t );
	}elseif ($ver == 0){
		if($debug){echo "<div class=\"textpad noti \">Skipping Non-SNMP Device</div>";}
		return 0;
	}else{
		if($debug){echo "<div class=\"textpad noti \">snmpset -v1 -c$cm $ip $oid $f $v ($t usec * $r)</div>";}
		return snmpset($ip, $cm, ".$oid", $f, $v, $t );
	}
}

?>
