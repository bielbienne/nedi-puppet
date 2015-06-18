<p>
<div id="footer" class="<?= $modgroup[$self] ?>1">
<?php
if( isset($_GET['print']) or isset($_GET['xls']) ){
	echo "$_SESSION[user], $now";
}elseif($debug){
	echo "$cmdlbl $timlbl ".round(microtime(1) - $start,2)." $tim[s]";
}else{
?>
&copy; 2001-2013 Remo Rickli & contributors
<?php
}
?>
</div>
</body>
</html>
