<?php

/**
 * ldap by Stephane Garret & vtur
 * Check all the directories. When the user is found, then import it
 * @param $login : user login
 * @param $password : user password 
 * @param $import : import user or check
**/
function user_from_ldap_servers($login, $password = '', $import = true){

	global $ldapsrv, $user_dn, $fields;
	global $dbhost,$dbuser,$dbpass,$dbname;

	// search if user exist in local user DB
		$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);
		$query	= GenQuery('users','s','*','','',array('user'),array('='),array($login) );
		$res    = DbQuery($query,$link);
	if ($import) {
		if (DbNumRows($res)==0){
			$result=ldapFindDn($login);
			if ($result != false){
				return $result;
				}
			}  
			return false;
		} else {
			$result=ldapFindDn($login);
			if ($result != false){
				$ds1 = connect_ldap($ldapsrv[0], $ldapsrv[1], $user_dn, $password, 0,0);
			if($ds1){
				//Authetication OK for user
				return true;
			}else {
				//Authetication Failed for user
				return false;
			}
		}
	}
		return false;
}


/** Find User dn
 *
 * @param   $login  dn of the user to find
*/
function ldapFindDn($login) {
	global $ldapsrv, $user_dn, $ldapmap;
	
	//Connect to the directory
	$ds = connect_ldap($ldapsrv[0], $ldapsrv[1], $ldapsrv[4], $ldapsrv[2], 0,0);
	if ($ds) {
		//Get the user's dn
		$user_dn = ldap_search_user_dn($ds, $ldapsrv[3], $ldapsrv[5], stripslashes($login), '');
		if ($user_dn) {
			if ((getFromLDAP($ds, $user_dn, addslashes($login))) and (getldapusersgroups($ds, $user_dn, addslashes($login)))) {
				return true;
			} else {
				return false;
			}
		}
	} else {
		return false;
	}
}

/**
 * Function that try to load from LDAP the user information...
 *
 * @param $ldap_connection ldap connection descriptor
 * @param $ldap_method LDAP method
 * @param $userdn Basedn of the user
 * @param $login User Login
 */
function getFromLDAP($ldap_connection, $userdn, $login) {

    global $fields, $ldapsrv, $ldapmap;

    if ($ldap_connection) {
	$fields=array('ldap_login'=>$ldapsrv[5], 
		'ldap_field_email'=>$ldapmap[6], 
		'ldap_field_realname'=>'sn', 
 		'ldap_field_firstname'=>'givenname', 
 		'ldap_field_phone'=>$ldapmap[7], 
 		'ldap_field_title'=>'title'
 	); 
	$fields = array_filter($fields);
	$f = array_values($fields);
	$sr = @ldap_read($ldap_connection, $userdn, "objectClass=*", $f);
	$v = ldap_get_entries($ldap_connection, $sr);
	if (!is_array($v) || count($v) == 0){
	     return false;
	}
	foreach ($fields as $k => $e) {
	    if (empty($v[0][$e][0])){
		switch ($k){
                    case "title":
                    case "type":
                    default:
			$fields[$k] = "";
                    //	break;
                   }
            } else {
		switch ($k) {
		    case "language":
		    case "title":
		    case "type":
		    default:
			if (!empty($v[0][$e][0])){
			    $fields[$k] = addslashes($v[0][$e][0]);
			}else{
			    $fields[$k] = "";
		//	    break;
			}						
		}
	    }	
		$stringData = "Field $fields[$k] = ($v[0][$e][0]\n";
		fwrite($fh, $stringData);
	}

	return true;
    }
    return false;
}

/**
 * Get users groups from ldap
 * Currently only 'PosixGroup' class with attribute 'memberuid' are searched
 * @param $ldap_connection ldap connection descriptor
 * @param $userdn Basedn of the user (left for a further use)
 * @param $login User Login
 */

function getldapusersgroups($ldap_connection, $userdn, $login) {
    global $ldapusersgrp,$ldapsrv;
    $ldapusersgrp = array();
    $attributes = array("cn");    
        
    if ($ldap_connection) {
	$sr = ldap_search($ldap_connection, $ldapsrv[3], "(&(objectclass=PosixGroup)(memberuid=$login))",$attributes);
	$data = ldap_get_entries($ldap_connection, $sr);
	if($data["count"] > 0){
    	    for ($i=0; $i<$data["count"];$i++) {
		array_push($ldapusersgrp,$data[$i]['cn'][0]);
	    }
	}
	return true;
    }
    return false;
}

/**
 * Connect to a LDAP serveur
 *
 * @param $host : LDAP host to connect
 * @param $port : port to use
 * @param $login : login to use
 * @param $password : password to use
 * @param $use_tls : use a tls connection ?
 * @param $deref_options Deref options used
**/
function connect_ldap($host, $port, $login = "", $password = "", $use_tls = false,$deref_options) {

	global $CFG_GLPI;

	$ds = @ldap_connect($host, intval($port));
	if ($ds) {
		@ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		@ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		@ldap_set_option($ds, LDAP_OPT_DEREF, $deref_options);
		//@ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);

		if ($use_tls) {
			if (!@ldap_start_tls($ds)) {
				return false;
			}
		}
		// Auth bind
		if ($login != '') {
			$b = @ldap_bind($ds, $login, $password);
		} else { // Anonymous bind
			$b = @ldap_bind($ds);
		}
	
		if ($b) {
			return $ds;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


/**
 * Get dn for a user 
 *
 * @param $ds : LDAP link
 * @param $basedn : base dn used to search
 * @param $login_attr : attribute to store login
 * @param $login : user login
 * @param $condition : ldap condition used
 * @return dn of the user, else false
**/
function ldap_search_user_dn($ds, $basedn, $login_attr, $login, $condition) {

	$filter = "($login_attr=$login)";
	
	if (!empty ($condition)){
		$filter = "(& $filter $condition)";
	}
	if ($result = ldap_search($ds, $basedn, $filter, 
		array ("dn", $login_attr),0,0)
	){
		$info = ldap_get_entries($ds, $result);
		if (is_array($info) AND $info['count'] == 1) {
			return $info[0]['dn'];
		} else { 
			$dn = "$login_attr=$login," . $basedn;
			return $dn;
		}
	} else {
		return false;
	}
}


?>
