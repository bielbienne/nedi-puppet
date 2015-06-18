<?php
# Program: User-Wlan.php
# Programmer: Remo Rickli

#error_reporting(E_ALL ^ E_NOTICE);

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$ip = isset($_GET['ip']) ? $_GET['ip'] : "";
$du = isset($_GET['du']) ? $_GET['du'] : "";
$lo = isset($_GET['lo']) ? $_GET['lo'] : "";
$us = isset($_GET['us']) ? $_GET['us'] : "";

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
?>
<h1><?= $usrlbl ?> <?= $edilbl ?></h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" name="usr" action="<?= $self ?>.php">
<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
Device <SELECT size="1" name="ip" onchange="this.form.submit();">
<OPTION VALUE="">------------
<?php
$query	= GenQuery('devices','s','device,inet_ntoa(devip)','','',array('devos'),array('='),array('MSM') );
$res	= DbQuery($query,$link);
if($res){
	while( $d = DbFetchRow($res) ){
		echo "<option value=\"$d[1]\"".( ($ip == $d[1])?" selected":"").">$d[0]\n";
	}
	DbFreeResult($res);
}else{
	print DbError($link);
	die ( mysql_error() );
}
?>
</SELECT>
<img src="img/16/brld.png" title="Reload with current IP" onClick="document.location.href='?ip='+document.usr.ip.value;">
</th>
<th>User <input type="text" name="us" size="12">
</th>
<th width="80">
<input type="submit" value="<?= $addlbl ?>">
</th>
</tr></table></form><p>
<?php
}

if($ip){
	#require_once("inc/soapapi-inc.php");
	if($debug){echo "INC : api<br>\n";}
	SoapApi::ClearWSDLCache();

	try {
		$url = sprintf("%s://%s:%d/SOAP", "http", $ip, 448);
		if($debug){echo "URL : $url<br>\n";}

		$c = new SoapApi("http://$ip/soapapi.wsdl", array('connection_timeout'=> $timeout,
							'location' => $url ,
							'local_cert' => "log/soap-api-client.crt",
							'passphrase' => "clientcertpa55")
				);

		if($us){
			echo "<h3>$addlbl $usrlbl $us</h3>";
			flush();
			$c->soapAddUserAccount($us, $us, "Enabled", "Enabled");
			sleep(8);# TODO find better way than that to avoid errors???
		}elseif($du){
			echo "<h3>$usrlbl $du, $dellbl</h3>";
			$c->soapDeleteUserAccount($du);
		}elseif($lo){
			echo "<h3>$usrlbl $lo, Logout</h3>";
			$c->soapExecuteUserAccountLogout($lo);
		}

?>
<h2><?= $usrlbl ?> <?= $lstlbl ?></h2>
<table class="content"><tr class="<?= $modgroup[$self] ?>2">
<th>ID</th>
<th><?= $namlbl ?></th>
<th>Access Ctrl</th>
<th>Active</th>
<th>Expired</th>
<th>Exausted</th>
<th>1st Login Expired</th>
<th>Period</th>
<th>Not Begun</th>
<th>Not Ended</th>
<th><?= $timlbl ?> Left</th>
<th><?= $fislbl ?></th>
<th>Session Left</th>
<th><?= $laslbl ?></th>
<th><?= $cmdlbl ?></th>

</tr>
<?php
		$users = SoapGetList("soapGetUserAccountList","username");

		foreach ($users as $i => $nam){
			$row++;
			if ($row % 2){$bg = "txta"; $bi = "imga";}else{$bg = "txtb"; $bi = "imgb";}
			echo "<tr class=\"$bg\"><td>$i</td><th>$nam</th>";
			$rc = $c->soapGetUserAccount($nam);
			echo "<th>".StatImg($rc->accessControlledState,16)."</th>";
			echo "<th>".StatImg($rc->activeState,16)."</th>\n";
			
			$rc = $c->soapGetUserAccountStatus($nam);
			echo "<th>".StatImg($rc->result->item->isAccountExpired,16)."</th>";
			echo "<th>".StatImg($rc->result->item->isOnlineTimeExausted,16)."</th>";
			echo "<th>".StatImg($rc->result->item->isTimeSinceFirstLoginExpired,16)."</th>";
			echo "<th>".StatImg($rc->result->item->isTimeCurrentlyOutsideValidPeriodOfDay,16)."</th>";
			echo "<th>".StatImg($rc->result->item->isValidityPeriodNotBegun,16)."</th>";
			echo "<th>".StatImg($rc->result->item->isValidityPeriodEnded,16)."</th>\n";

			echo "<th>".$rc->result->item->remainingOnlineTime."</th>";
			echo "<th>".$rc->result->item->firstLogin."</th>";
			echo "<th>".$rc->result->item->remainingSessionTime."</th>";
			echo "<th>".$rc->result->item->expiration."</th>\n";

			echo "<th><a href=\"?ip=$ip&lo=$nam\"><img src=\"img/16/exit.png\" title=\"Logout\"></a>";
			echo "<a href=\"?ip=$ip&du=$nam\"><img src=\"img/16/bcnl.png\" title=\"$usrlbl $dellbl\" onclick=\"return confirm('$dellbl: $cfmmsg?')\"></a>";
			echo "</th><tr>\n";
		#	$us = $c->GetUserAccountValidity($val);
		#			print_r($rc);
		}
		echo "</table>";
	} catch (Exception $e) {
		echo "<h4>". $e->getMessage(), "</h4>";
	}
}

include_once ("inc/footer.php");

//===================================================================
// Get list using SOAP
function SoapGetList($list,$item) {
	
	global $c;

	$sobj = $c->$list();
	$size = sizeof($sobj->result->item);
	if ($size < 2) {
		$ret[0] = $sobj->result->item->$item;
	}else {
		for ($i=0; $i<$size; $i++) {
			$ret[$i] = $sobj->result->item[$i]->$item;
		}
	}
	return $ret;
}

//===================================================================
// Return statusimage
function StatImg($stat,$size) {

	if( preg_match("/1|enabled|on/i",$stat) ){
		return "<img src=\"img/$size/bchk.png\" title=\"$stat\">";
	}else{
		return "<img src=\"img/$size/bcls.png\" title=\"$stat\">";
	}
}

#/*
class SoapApi extends SoapClient
{
    static function ClearWSDLCache()
    {
        //  clearing WSDL cache
        ini_set("soap.wsdl_cache_enabled", "0");
    }

    function soapGetUserAccount($username)
    {
        //  Get the user account settings. This also returns all effectives attributes from account profiles (no custom attribute though).
        $rc = $this->GetUserAccount(array("username" => $username));

        return $rc;
    }

    function soapGetUserAccountList()
    {
        //  Get the user account list.
        $rc = $this->GetUserAccountList(array());

        return $rc;
    }

    function soapAddUserAccount($username, $password, $activeState, $accessControlledState)
    {
        //  Add a new user account.
        $rc = $this->AddUserAccount(array("username" => $username, "password" => $password, "activeState" => $activeState, "accessControlledState" => $accessControlledState));

        return $rc;
    }

    function soapDeleteUserAccount($username)
    {
        //  Delete an user account.
        $rc = $this->DeleteUserAccount(array("username" => $username));

        return $rc;
    }

    function soapGetUserAccountStatus($username)
    {
        //  Get the status of an User Account.
        $rc = $this->GetUserAccountStatus(array("username" => $username));

        return $rc;
    }

    function soapExecuteUserAccountLogout($username)
    {
        //  Logout an User Account.
        $rc = $this->ExecuteUserAccountLogout(array("username" => $username));

        return $rc;
    }

}
#*/
?>
