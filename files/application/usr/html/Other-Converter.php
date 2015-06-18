<?php
# Program: Other-Converter.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$txt  = isset($_GET['txt']) ? $_GET['txt'] : "";

if( !isset($_GET['print']) ) {
?>
<h1>Text Converter</h1>
<form method="get" action="<?= $self ?>.php">
<table class="content" ><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<th>
<?= $inflbl ?>: <input type="text" name="txt" value="<?= $txt ?>" size="40">
</th>
<th width="80">
<input type="submit" value="<?= $sholbl ?>">
</th>
</tr>
</table></form>
<?php
}

?>
<h2>Decimal 2 ASCII</h2>
<div class="textpad code txta" name="out">
<?php

$ord = preg_split('/\D/', $txt);
foreach ($ord as $o){
	if($o > 31 and $o < 122){
		echo chr($o);
	}else{
		echo "$o ";
	}
}
echo "\n";
?>
</div><br>

<?php
include_once ("inc/footer.php");
?>
