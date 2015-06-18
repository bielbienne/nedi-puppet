<?php
session_start(); 

if( isset($_SESSION['group']) ){
	unset($_SESSION['group']);
	session_destroy();
}
echo "<script>document.location.href='index.php';</script>\n";

?>
