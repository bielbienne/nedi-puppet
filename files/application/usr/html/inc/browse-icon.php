<?php 
//===============================
// Browse Device Icon
//===============================
session_start(); 
require_once ('libmisc.php');
require_once ("../languages/$_SESSION[lang]/gui.php");

if( !preg_match("/net/",$_SESSION['group']) ){
	echo $nokmsg;
	die;
}
$_GET = sanitize($_GET);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=<?= $charset ?>">
<script language="JavaScript">
<!--
function update(img){
	opener.document.bld.ico.value=img;
	self.close();
}
//-->
</script>
</head>
<body>
Icon <a href="http://www.nedi.ch/expand" target="Window"><?= $sumlbl ?></a>
<?

if ( $handle = opendir("../img/dev") ){
	while (false !== ($f = readdir($handle))) {
		if ( stristr($f,'.png') ){
			$icon[] = $f;
		}
	}
	closedir($handle);
	sort($icon);
	$p = "";
	foreach ($icon as $i){
			$n = str_replace(".png","",$i);
			$t = substr($i, 0, 2);
			if ($t <> $p){
				echo "<h3>$t</h3>";
			}
			$p = $t;
			echo "<img src=../img/dev/$i title=\"$n\" hspace=4 vspace=4 onClick=\"update('$n');\">\n";

	}
}
?>

</body>
</html>
