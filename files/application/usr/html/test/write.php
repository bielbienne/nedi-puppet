<html><body>
<h3>
<?php

	echo shell_exec('whoami');
	echo "</h3>";
	$fd =  @fopen("../log/IcanWrite","w") or die ("Can't write ../log/IcanWrite!");
	fwrite($fd,"Could write to log!\n");
	fclose($fd);

	readfile("../log/IcanWrite");	
?>
</body></html>