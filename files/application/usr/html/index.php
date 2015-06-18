<?php
#============================================================================
# Program: index.php (NeDi GUI)
# Programmers: Remo Rickli & community
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.

#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.

#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.
#============================================================================
# Visit http://www.nedi.ch/ for more information.
#============================================================================

$nedipath  = preg_replace( "/^(\/.+)\/ht\w+\/.+.php/","$1",$_SERVER['SCRIPT_FILENAME']);		# Guess NeDi path for nedi.conf

require_once ("inc/libmisc.php");
ReadConf('usr');
require_once ("inc/libdb-" . strtolower($backend) . ".php");
include_once ("inc/timezones.php");

$_GET  = sanitize($_GET);
$_POST = sanitize($_POST);
if( isset($_GET['goto']) and preg_match('/^\w+-\w+\.php/',$_GET['goto']) ){				# Only allow links to NeDi modules
	$goto = $_GET['goto'];
}elseif( date('z') > 330 ){										# Let the X-mas spirits inspire users...
	$goto = 'Other-Invoice.php';
}else{
	$goto = 'User-Profile.php';
}
$user  = isset($_GET['user'])  ? $_GET['user'] : '';
$user  = isset($_POST['user']) ? $_POST['user'] : $user;

if( $guiauth == 'sso' and isset($_SERVER['HTTP_AUTH_USER']) ){
	$user = $_SERVER['HTTP_AUTH_USER'];
	$_POST['pass'] = 'NO_PASSWD';
}elseif( $guiauth == 'sso' and isset($_SERVER['PHP_AUTH_USER']) ){
	$user = $_SERVER['PHP_AUTH_USER'];
	$_POST['pass'] = 'NO_PASSWD';
}

$raderr = "";

# if username starts with "/C=" (Certificate), then use $_SERVER['SSL_CLIENT_S_DN_CN'] as suggested by Daniel
if(isset( $user) and preg_match('/^\/C=/',$user) and isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
	$user = $_SERVER['SSL_CLIENT_S_DN_CN'];
}

if( isset( $user) and preg_match('/^[A-Za-z0-9@\.]+$/',$user) ){			# Avoid SQL injection as suggested by Daniel
	$form_user = $user;									# SSO Code for HTTPAUTH PassTrough by Juergen Vigna
	$form_pass = $_POST['pass'];

	$pass = hash("sha256","NeDi".$user.$_POST['pass']);					# Salt & pw
	$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	if($guiauth == 'none'){
		$uok	= 1;
		$query	= GenQuery('users','s','*','','',array('usrname'),array('='),array($user) );
		$res    = DbQuery($query,$link);
	}elseif( strstr($guiauth,'pam') && $user != "admin" ){					# PAM code by Owen Brotherhood & Bruberg
		if (!extension_loaded ('pam_auth')){dl('pam_auth.so');}					# dl removed in PHP5.3?
		$uok	= pam_auth($user,$_POST['pass']);
		$query	= GenQuery('users','s','*','','',array('usrname'),array('='),array($user) );
		$res    = DbQuery($query,$link);
	}elseif( strstr($guiauth,'radius') && $user != "admin" ){				# Radius code by Till Elsner
		$radres = radius_auth_open();
		if (!$radres) {
			$raderr = "Error while preparing RADIUS authentication: ".radius_strerror($radres);
		}
		foreach ($radsrv as $rs){
			if (!radius_add_server($radres, $rs[0], $rs[1], $rs[2], $rs[3], $rs[4])){
				echo "<h4>RADIUS: ".radius_strerror($radres)."</h4>";
			}
		}
		if (!radius_create_request($radres, RADIUS_ACCESS_REQUEST)) {
			$raderr = "RADIUS create: ".radius_strerror($radres);
		}
		if (!(	radius_put_string($radres, RADIUS_USER_NAME, $user)
			&& radius_put_string($radres, RADIUS_USER_PASSWORD, $_POST['pass'])
			&& radius_put_string($radres, RADIUS_CALLING_STATION_ID, $_SERVER['REMOTE_ADDR'])# dstrezov's suggestion for ACS compliance
			&& radius_put_addr($radres, RADIUS_NAS_IP_ADDRESS, $_SERVER['SERVER_ADDR']) )){
			$raderr = "RADIUS put: ".radius_strerror($radres);
		}
		$radauth = radius_send_request($radres);
		if (!$radauth){
			$raderr = "RADIUS send: ".radius_strerror($radres);
		}else{
			switch ($radauth){
				case RADIUS_ACCESS_ACCEPT:
					$query	= GenQuery('users','s','*','','',array('usrname'),array('='),array($user) );
					$res    = DbQuery($query,$link);
					$uok	= DbNumRows($res);
					break;
				case RADIUS_ACCESS_REJECT:
					$raderr = "Incorrect RADIUS login!";
					break;
				case RADIUS_ACCESS_CHALLENGE:
					$raderr = "No RADIUS challenge handling yet!";
					break;
				default:
					$raderr = "Unknown RADIUS error!";
			}
		}
	}elseif( strstr($guiauth,'ldap') && $user != "admin" ){				# Ldap code by Stephane Garret & vtur
		require_once ("inc/libldap.php");
		if (user_from_ldap_servers($user,$_POST['pass'], false)){
			$query	= GenQuery('users','s','*','','',array('usrname'),array('='),array($user) );
			$res    = DbQuery($query,$link);
			$uok = 1;
			$ldaperr = "<h4>Authentication LDAP OK</h4>";
		}else {
			$uok = 0;
			$ldaperr = "<h4>Authentication LDAP Failed </h4>";
		}
	}elseif( strstr($guiauth,'sso') and $_POST['pass'] == 'NO_PASSWD') {				# SSO Code for HTTPAUTH PassTrough by Juergen Vigna
			$query	= GenQuery('users','s','*','','',array('usrname'),array('='),array($user) );
			$res    = DbQuery($query,$link);
			$uok = DbNumRows($res);
			if ($uok != 1) {
				$user = $form_user;
				$_POST['pass'] = $form_pass;
				$pass   = hash("sha256","NeDi".$user.$_POST['pass']);		# Salt & pw
				$query	= GenQuery('users','s','*','','',array('usrname','password'),array('=','='),array($user,$pass),array('AND') );
				$res    = DbQuery($query,$link);
				$uok    = DbNumRows($res);
			}
	}else{
	        $pass = hash("sha256","NeDi".$user.$_POST['pass']);				# Salt & pw
		$query	= GenQuery('users','s','*','','',array('usrname','password'),array('=','='),array($user,$pass),array('AND') );
		$res    = DbQuery($query,$link);
		$uok    = DbNumRows($res);
	}

	if($uok == 1) {
		$usr = DbFetchRow($res);
		session_start(); 
		$_SESSION['user']  = $user;
		$_SESSION['group'] = "usr,";
		$_SESSION['view']  = $usr[15];
		$_SESSION['bread'] = array();
		$_SESSION['ver']   = "1.0.9-010";
		if( strstr($guiauth,'ldap') and $user != "admin" and is_array($ldapmap) ){
			if (($ldapmap[0]) and in_array($ldapmap[0],$ldapusersgrp)){
				$_SESSION['group']   .= "adm,";
			}
			if (($ldapmap[1]) and in_array($ldapmap[1],$ldapusersgrp)){
				$_SESSION['group']   .= "net,";
			}
			if (($ldapmap[2]) and in_array($ldapmap[2],$ldapusersgrp)){
				$_SESSION['group']   .= "dsk,";
			}
			if (($ldapmap[3]) and in_array($ldapmap[3],$ldapusersgrp)){
				$_SESSION['group']   .= "mon,";
			}
			if (($ldapmap[4]) and in_array($ldapmap[4],$ldapusersgrp)){
				$_SESSION['group']   .= "mgr,";
			}
			if (($ldapmap[5]) and in_array($ldapmap[5],$ldapusersgrp)){
				$_SESSION['group']   .= "oth,";
			}
			if(DbNumRows($res)>0){
				$_SESSION['lang'] = $usr[8];
				$_SESSION['theme']= $usr[9];
				$_SESSION['vol']  = ($usr[10] & 3)*33;
				$_SESSION['lsiz'] = ($usr[10] & 124) >> 2;
				$_SESSION['col']  = $usr[11];
				$_SESSION['lim']  = $usr[12];
				$_SESSION['gsiz'] = $usr[13] & 7;
				$_SESSION['gbit'] = $usr[13] & 8;
				$_SESSION['far']  = $usr[13] & 16;
				$_SESSION['opt']  = $usr[13] & 32;
				$_SESSION['map']  = $usr[13] & 64;
				$_SESSION['gneg'] = $usr[13] & 128;
				$_SESSION['nip']  = $usr[13] & 256;
				$_SESSION['date'] = ($usr[14])?substr($usr[14],0,-3):'j.M y G:i';
				$_SESSION['tz']   = $tzone[substr($usr[14],-3)];
			}else{
				$_SESSION['lang'] = 'english';
				$_SESSION['theme']= 'default';
				$_SESSION['vol']  = 33;
				$_SESSION['lsiz'] = 12;
				$_SESSION['col']  = 6;
				$_SESSION['lim']  = 5;
				$_SESSION['gsiz'] = 2;
				$_SESSION['date'] = 'j.M y G:i';
			}
		}else{
			if ($usr[2] &  1) {$_SESSION['group']	.= "adm,";}
			if ($usr[2] &  2) {$_SESSION['group']	.= "net,";}
			if ($usr[2] &  4) {$_SESSION['group']	.= "dsk,";}
			if ($usr[2] &  8) {$_SESSION['group']	.= "mon,";}
			if ($usr[2] & 16) {$_SESSION['group']	.= "mgr,";}
			if ($usr[2] & 32) {$_SESSION['group']	.= "oth,";}

			$_SESSION['lang'] = $usr[8];
			$_SESSION['theme']= $usr[9];
			$_SESSION['vol']  = ($usr[10] & 3)*33;
			$_SESSION['lsiz'] = ($usr[10] & 124) >> 2;
			$_SESSION['col']  = $usr[11];
			$_SESSION['lim']  = $usr[12];
			$_SESSION['gsiz'] = $usr[13] & 7;
			$_SESSION['gbit'] = $usr[13] & 8;
			$_SESSION['far']  = $usr[13] & 16;
			$_SESSION['opt']  = $usr[13] & 32;
			$_SESSION['map']  = $usr[13] & 64;
			$_SESSION['gneg'] = $usr[13] & 128;
			$_SESSION['nip']  = $usr[13] & 256;
			$_SESSION['date'] = ($usr[14])?substr($usr[14],0,-3):'j.M y G:i';
			$_SESSION['tz']   = $tzone[substr($usr[14],-3)];
			$query	= GenQuery('users','u','usrname','=',$user,array('lastlogin'),array(),array(time()) );
			DbQuery($query,$link);
		}
	}else{
	    print DbError($link);
	}
	if( isset($_SESSION['group']) ){
		echo "<body style=\"background-color: #666666;\"><script>document.location.href='$goto';</script></body>\n";
	}elseif($raderr){
		$disc = "<h4>$raderr</h4>";
	} else {
		$disc = "<h4>Incorrect login!</h4>";
	}

}
?>
<html>
<head>
<title>NeDi 1.0.9-010</title>
<meta name="generator" content="NeDi 1.0.9-010">
<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1">
<link href="themes/default.css" type="text/css" rel="stylesheet">
<link rel="shortcut icon" href="img/favicon.ico">
</head>
<body onLoad="document.login.user.focus();">
<div align="center">
<form name="login" method="post" action="index.php?goto=<?= urlencode($goto) ?>">
<table class="login">
<tr class="loginbg"><th colspan="3">
<?= ( file_exists('themes/custom.png') )?'<img src="themes/custom.png"><br>':'<a href="http://www.nedi.ch"><img src="img/nedib.png"></a>'; ?>
</th></tr>
<tr>
<th class="txta" align="center" colspan="3">
<img src="img/nedie<?= rand(1,8) ?>.jpg">
<p><hr>
<?= $disc ?>
</th></tr>
<tr class="loginbg">
<th>User <input type="text" name="user" size="15"></th>
<th>Pass <input type="password" name="pass" size="15"></th>
<th><input type="submit" value="Login">
</th>
</tr>
</table>
</form>
</div>

</body>
