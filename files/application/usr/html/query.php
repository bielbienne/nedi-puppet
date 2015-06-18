<?php
#============================================================================
# Program: query.php (NeDi DB Interface)
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
#error_reporting(E_ALL);

require_once ("inc/libmisc.php");
ReadConf('usr');
require_once ("inc/libdb-" . strtolower($backend) . ".php");

$_POST = sanitize($_POST);

header("Content-type: text/plain");

#$_POST['u'] = "admin";
#$_POST['p'] = "admin";
#$_POST['m'] = "json";
#$_POST['q'] = "select * from incidents";

if( isset($_POST['u']) and isset($_POST['p']) ){
	$pass = hash("sha256","NeDi".$_POST['u'].$_POST['p']);							# Salt & pw
	$link = DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	$query= GenQuery('users','s','*','','',array('usrname','password'),array('=','='),array($_POST['u'],$pass),array('AND') );
	$res  = DbQuery($query,$link);
	$uok  = DbNumRows($res);
	DbFreeResult($res);

	if($uok == 1) {
		$res = DbQuery($_POST['q'],$link);
		$sys = posix_uname();
		$sys['nedi'] = "1.0.9-010"; 
		if($_POST['m']){
			if($res){
				while($l = DbFetchArray($res)) {
					$rows[] = $l;
				}
				array_unshift($rows,$sys);
				print json_encode($rows);
			}else{
				echo "ERR :DB - ".DbError($link);
			}
		}else{
			echo join(';;',$sys)."\n";
			if($res){
				while($l = DbFetchRow($res)) {
					echo join(';;',$l)."\n";
				}
			}else{
				echo "ERR :DB - ".DbError($link);
			}
		}
		DbFreeResult($res);
	}else{
		echo "ERR :Incorrect password!";
	}
}else{
	echo "ERR :Need credentials!";
}
