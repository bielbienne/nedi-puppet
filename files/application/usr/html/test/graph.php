<?php

header("Content-type: image/png");
$string = "GD works!";
$im    = imagecreatefrompng("../img/nedib.png");
$orange = imagecolorallocate($im, 220, 210, 60);
imagestring($im, 3, 50, 9, $string, $orange);
imagepng($im);
imagedestroy($im);

?> 