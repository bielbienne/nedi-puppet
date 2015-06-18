<?php
# Program: Other-Info.php
# Programmer: Remo Rickli

$printable = 1;
$exportxls = 0;

include_once ("inc/header.php");
?>

<h1>Information</h1>

<table class="content"><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td>

	<table class="full">
	<tr class="<?= $modgroup[$self] ?>2">
	<th>Language: <?= $_SESSION['lang'] ?></th>
	<th>Theme: <?= $_SESSION['theme'] ?></th>
	<th><?= $optlbl ?>: <?= $_SESSION['opt'] ?></th>

	</tr>
	<tr class="<?= $modgroup[$self] ?>2">

	<th>Volume: <?= $_SESSION['vol']  ?></th>
	<th><?= $collbl ?>: <?= $_SESSION['col']  ?></th>
	<th><?= $limlbl ?>: <?= $_SESSION['lim']  ?>/<?= $_SESSION['lsiz']  ?></th>

	</tr>
	<tr class="<?= $modgroup[$self] ?>2">

	<th><?= $gralbl ?> <?= $sizlbl ?>: <?= $_SESSION['gsiz'] ?></th>
	<th><?= $trflbl ?> Bit/s: <?= $_SESSION['gbit'] ?></th>
	<th>Fahrenheit: <?= $_SESSION['far'] ?></th>

	</tr></table>
</td>
</tr></table>
<p>
<?php phpinfo(); ?>

<style type="text/css">
td, th { border: 1px solid #000000; font-size: 100%; vertical-align: baseline;}
</style>
