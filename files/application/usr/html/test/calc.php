<html><body>
<?php
$ip = $_SERVER['REMOTE_ADDR'];

echo "PHP ". phpversion();

echo "<h2>Little PHP bitwise test for IP:$ip</h2>\n";
$dip1 = sprintf("%u", ip2long($ip));
echo long2ip($dip1);
echo " $dip1 <b>1.IP</b><br>\n";
$dip2 = ip2long($ip);
echo long2ip($dip2);
echo " $dip2 <b>2.IP</b><br>\n";
echo "<hr width=300 align=left>";

$dmsk1 = (4294967295 >> 8) << 8;
echo long2ip($dmsk1);
echo " $dmsk1 <b>1.prefix 24</b><br>\n";
$dmsk2 = 0xffffffff << 8;
echo long2ip($dmsk2);
echo " $dmsk2 <b>2.prefix 24</b><br>\n";

echo "<hr width=300 align=left><br>";
echo long2ip($dip1 & $dmsk1 ) ." <b>1.IP 1.prefix</b><br>";
echo long2ip($dip1 & $dmsk2 ) ." <b>1.IP 2.prefix</b><br>";
echo long2ip($dip2 & $dmsk1 ) ." <b>2.IP 1.prefix</b><br>";
echo long2ip($dip2 & $dmsk2 ) ." <b>2.IP 2.prefix</b><br>";
?>
</body></html>