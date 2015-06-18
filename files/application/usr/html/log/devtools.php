<?php
# This file can be used to add links from Device-Status, with the following variables available for linking:
# $ip		Device IP address
# $ud		URL encoded device name
# $os		Operating system
# $rcomm	SNMP read community
# $wcomm	SNMP write community
# $rver		SNMP read version
# $wver		SNMP write version
# $wasup	device was seen in last discovery
# $isadmin	current user is in admin group

# Usage example for Cisco-WLC and HP-MSMs to list all controlled APs (using custom value as reference to controller's IP):
if($os == "ArubaOS" or $os == "IOS-wlc" or $os == "MSM"){
	echo "<a href=\"Devices-List.php?in[]=login&op[]=%3D&st[]=$ud\"><img src=\"img/16/wlan.png\" title=\"AP $lstlbl\"></a>";
}
?>
