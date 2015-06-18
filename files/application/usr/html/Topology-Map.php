<?php
# Program: Topology-Map.php
# Programmer: Remo Rickli

$nocache   = 1;
$refresh   = 600;
$printable = 1;

include_once ("inc/header.php");
include_once ("inc/libdev.php");
include_once ("inc/libmap.php");
include_once ("inc/librrd.php");

$dev  = array();
$reg  = array();
$nlnk = array();

$imgmap    = "";
$mapinfo   = "";
$mapframes = "";
$maplinks  = "";
$mapitems = "";

$_GET = sanitize($_GET);
$st = isset($_GET['st']) ? $_GET['st'] : array('%');
$in = isset($_GET['in']) ? $_GET['in'] : array('location');
$op = isset($_GET['op']) ? $_GET['op'] : array('like');
$co = isset($_GET['co']) ? $_GET['co'] : array();

$fmt = isset($_GET['fmt']) ? $_GET['fmt'] : "";
$dim = isset($_GET['dim']) ? $_GET['dim'] : "800x600";
list($xm,$ym) = explode("x",$dim);

$fsz = isset($_GET['fsz']) ? $_GET['fsz'] : intval($xm)/8;
$len = isset($_GET['len']) ? $_GET['len'] : intval($xm)/4;

$tit = isset($_GET['tit']) ? $_GET['tit'] : "$netlbl";
$mde = isset($_GET['mde']) ? $_GET['mde'] : "b";
$lev = isset($_GET['lev']) ? $_GET['lev'] : 1;
if ( ($mde == "f" ) and $lev < 4){$lev = 4;}
if ($mde == "b" and $fmt == 'json'){$mde = 'r';}

$xo  = isset($_GET['xo']) ? $_GET['xo'] : 0;
$yo  = isset($_GET['yo']) ? $_GET['yo'] : 0;
$rot = isset($_GET['rot']) ? $_GET['rot'] : 0;
$cro = isset($_GET['cro']) ? $_GET['cro'] : 0;
$bro = isset($_GET['bro']) ? $_GET['bro'] : 0;

$ifi = ($_GET['ifi']) ? "checked" : "";
$ifa = ($_GET['ifa']) ? "checked" : "";
$ipi = ($_GET['ipi']) ? "checked" : "";
$ipd = ($_GET['ipd']) ? "checked" : "";
$loo = ($_GET['loo']) ? "checked" : "";
$loa = ($_GET['loa']) ? "checked" : "";
$loi = (($loo)?1:0) + (($loa)?2:0);
$dco = ($_GET['dco']) ? "checked" : "";
$dmo = ($_GET['dmo']) ? "checked" : "";
$dvi = (($dco)?1:0) + (($dmo)?2:0);

$lis = isset($_GET['lis']) ? $_GET['lis'] : "";
$lit = isset($_GET['lit']) ? $_GET['lit'] : "";
$lil = isset($_GET['lil']) ? $_GET['lil'] : 0;
$lal = isset($_GET['lal']) ? $_GET['lal'] : 50;
$pos = isset($_GET['pos']) ? $_GET['pos'] : "";
$pwt = isset($_GET['pwt']) ? $_GET['pwt'] : 10;
$lsf = isset($_GET['lsf']) ? $_GET['lsf'] : 10;
$fco = isset($_GET['fco']) ? $_GET['fco'] : 6;

$imas= ($pos == "d")?4:18;

$oc = "";
$oi = "";
$dyn= "";
if($_GET['dyn']){
	# $oi = 'oninput="this.form.submit();"'; deactivated cauz Safari goes haywire
	$oi = $oc = 'onchange="this.form.submit();"';
	$dyn = "checked";
}

$cols = array(	"device"=>"Device",
		"devip"=>"IP $adrlbl",
		"type"=>"Device $typlbl",
		"firstdis"=>$fislbl,
		"lastdis"=>$laslbl,
		"services"=>$srvlbl,
		"description"=>$deslbl,
		"devos"=>"Device OS",
		"bootimage"=>"Bootimage",
		"contact"=>$conlbl,
		"location"=>$loclbl,
		"devgroup"=>$grplbl,
		"snmpversion"=>"SNMP $verlbl",
		"login"=>"Login",
		"cpu"=>"% CPU",
		"temp"=>$tmplbl,
		"vlanid"=>"Vlan ID",
		"vlanname"=>"Vlan $name",
		"vrfname"=>"VRF",
		"ifip"=>$netlbl,
		"neighbor"=>$neblbl,
		"mac"=>'Node MAC',
		"nodip"=>'Node IP',
		"name"=>"Node $namlbl",
		"oui"=>"Node $venlbl"
		);

$link	= DbConnect($dbhost,$dbuser,$dbpass,$dbname);

?>
<h1>Topology Map</h1>

<?php  if( !isset($_GET['print']) ) { ?>

<form method="get" name="dynfrm" action="<?= $self ?>.php">
<table class="content" ><tr class="<?= $modgroup[$self] ?>1">
<th width="50"><a href="<?= $self ?>.php"><img src="img/32/<?= $selfi ?>.png"></a></th>
<td valign="top">
<h3><?= $fltlbl ?></h3>

<?PHP Filters(); ?>

</td>
<td valign="top" nowrap>

<h3><?= $manlbl ?></h3>
<img src="img/16/say.png" title="Map <?= $namlbl ?>">
<input type="text" <?= $oc ?> name="tit" value="<?= $tit ?>" size="18">
<br>

<img src="img/16/img.png" title="<?= $sizlbl ?> & <?= $frmlbl ?>">
<select size="1" <?= $oc ?> name="dim">
<?= ($dim)?"<option value=\"$dim\">$dim</option>":"" ?>
<option value="320x200">320x200
<option value="320x240">320x240
<option value="640x400">640x400
<option value="640x480">640x480
<option value="800x600">800x600
<option value="1024x600">1024x600
<option value="1024x768">1024x768
<option value="1280x768">1280x768
<option value="1280x1024">1280x1024
<option value="1600x900">1600x900
<option value="1600x1200">1600x1200
<option value="1920x1200">1920x1200
</select>
<select size="1" <?= $oc ?> name="fmt">
<option value="png">png
<option value="png8" <?= ($fmt == "png8")?" selected":"" ?>>8bit
<option value="svg" <?= ($fmt == "svg")?" selected":"" ?>>svg
<option value="json" <?= ($fmt == "json")?" selected":"" ?>>json
</select>
<br>

<img src="img/16/abc.png" title="Map <?= $typlbl ?>">
<select size="1" <?= $oc ?> name="lev" title="<?= $levlbl ?>">
<option value="1"><?= $place['r'] ?>
<option value="2" <?= ($lev == "2")?" selected":"" ?>><?= $place['c'] ?>
<option value="3" <?= ($lev == "3")?" selected":"" ?>><?= $place['b'] ?>
<option value="4" <?= ($lev == "4")?" selected":"" ?>>SNMP Dev
<option value="5" <?= ($lev == "5")?" selected":"" ?>><?= $alllbl ?>  Dev
<option value="6" <?= ($lev == "6")?" selected":"" ?>>Nodes
</select>
<select size="1" <?= $oc ?> name="mde" title="Map <?= $typlbl ?>">
<option value="b">bld
<option value="r" <?= ($mde == "r")?" selected":"" ?>>ring
<option value="f" <?= ($mde == "f")?" selected":"" ?>>flat
<option value="g" <?= ($mde == "g")?" selected":"" ?>>geo
</select>
<br>

<img src="img/16/geom.png" title="Map <?= $loclbl ?>">
<input type="number" min="-1000" max="1000" step="10" <?= $oi ?> name="xo" value="<?= $xo ?>" size="3" title="X <?= $loclbl ?>">
<input type="number" min="-1000" max="1000" step="10" <?= $oi ?> name="yo" value="<?= $yo ?>" size="3" title="Y <?= $loclbl ?>">
<br>

<img src="img/16/brld.png" title="Map <?= $rotlbl ?>">
<input type="number" min="-180" max="180" <?= $oi ?> name="rot" value="<?= $rot ?>" size="3" title="<?= $place['r'] ?>">
<input type="number" min="-180" max="180" <?= $oi ?> name="cro" <?= ($mde == "f" or $lev < 2 and $dyn)?"disabled":"" ?> value="<?= $cro ?>" size="3" title="<?= $place['c'] ?>">
<input type="number" min="-180" max="180" <?= $oi ?> name="bro" <?= ($mde == "f" or $lev < 3 and $dyn)?"disabled":"" ?> value="<?= $bro ?>" size="3" title="<?= $place['b'] ?>">

</td>
<td valign="top" nowrap><h3>Layout</h3>

<img src="img/16/ncfg.png" title="<?= $cnclbl ?> <?= $frmlbl ?>">
<input type="number" min="0" max="1000"  <?= $oi ?> step="10" name="len" value="<?= $len ?>" size="3" title="<?= $lenlbl ?>">
<select size="1" <?= $oc ?> name="lis">
<option value=""><?= $strlbl ?>
<option value="a1" <?= ($lis == "a1")?" selected":"" ?>><?= $arclbl ?>
<option value="a2" <?= ($lis == "a2")?" selected":"" ?>><?= $arclbl ?> 2
<option value="a3" <?= ($lis == "a3")?" selected":"" ?>><?= $arclbl ?> 3
<option value="a4" <?= ($lis == "a4")?" selected":"" ?>><?= $arclbl ?> 4
</select>
<br>

<img src="img/16/ncon.png" title="<?= $cnclbl ?> <?= $inflbl ?>">
<input type="number" min="-100" max="100" <?= $oi ?> name="lil" <?= ($fmt == "json" or !$lit and $dyn)?"disabled":"" ?> value="<?= $lil ?>" size="3" title="<?= $inflbl ?> <?= $loclbl ?>">
<select size="1" <?= $oc ?> name="lit">
<option value=""><?= $inflbl ?>
<option value="w" <?= ($lit == "w")?" selected":"" ?>><?= $bwdlbl ?>
<option value="l" <?= ($lit == "l")?" selected":"" ?>>Link <?= $lodlbl ?>
<option value="t" <?= ($lit == "t")?" selected":"" ?>>Link <?= $typlbl ?>
<?php if($rrdcmd){ ?>
<option value="" class="noti">- <?= $trflbl ?>
<option value="f1" <?= ($lit == "f1")?" selected":"" ?>> <?= $siz['t'] ?>
<option value="f2" <?= ($lit == "f2")?" selected":"" ?>> <?= $siz['s'] ?>
<option value="f3" <?= ($lit == "f3")?" selected":"" ?>> <?= $siz['m'] ?>
<option value="f4" <?= ($lit == "f4")?" selected":"" ?>> <?= $siz['l'] ?>
<option value="" class="crit">- <?= $errlbl ?>
<option value="e1" <?= ($lit == "e1")?" selected":"" ?>> <?= $siz['t'] ?>
<option value="e2" <?= ($lit == "e2")?" selected":"" ?>> <?= $siz['s'] ?>
<option value="e3" <?= ($lit == "e3")?" selected":"" ?>> <?= $siz['m'] ?>
<option value="e4" <?= ($lit == "e4")?" selected":"" ?>> <?= $siz['l'] ?>
<option value="" class="warn">- Bcast
<option value="b1" <?= ($lit == "b1")?" selected":"" ?>> <?= $siz['t'] ?>
<option value="b2" <?= ($lit == "b2")?" selected":"" ?>> <?= $siz['s'] ?>
<option value="b3" <?= ($lit == "b3")?" selected":"" ?>> <?= $siz['m'] ?>
<option value="b4" <?= ($lit == "b4")?" selected":"" ?>> <?= $siz['l'] ?>
<option value="" class="alrm">- Discard 
<option value="d1" <?= ($lit == "d1")?" selected":"" ?>> <?= $siz['t'] ?>
<option value="d2" <?= ($lit == "d2")?" selected":"" ?>> <?= $siz['s'] ?>
<option value="d3" <?= ($lit == "d3")?" selected":"" ?>> <?= $siz['m'] ?>
<option value="d4" <?= ($lit == "d4")?" selected":"" ?>> <?= $siz['l'] ?>
<?}?>
</select>
<br>

<img src="img/16/link.png" title="<?= $cnclbl ?> <?= $endlbl ?>">
<input type="number" min="1" max="100"  <?= $oi ?> name="lsf" <?= ($mde == "f" and $lev < 6 and $dyn and $fmt != "json")?"disabled":"" ?> value="<?= $lsf ?>" size="3" title="<?= ($fmt == 'json')?$cnclbl:"$lenlbl/$levlbl" ?>">
<input type="number" min="0" max="100" <?= $oi ?> step="5" name="lal" <?= (!$ifi and !$ifa and !$ipi and $dyn and $fmt != "json")?"disabled":"" ?> value="<?= $lal ?>" size="3" title="<?= ($fmt == 'json')?$metlbl:"IF/IP $loclbl" ?>">
<br>

<img src="img/16/dev.png" title="<?= $nodlbl ?> <?= $cfglbl ?>">
<input type="number" min="0" max="100" <?= $oi ?> name="pwt" <?= ($fmt == "json")?"disabled":"" ?> value="<?= $pwt ?>" size="3" title="<?= $nodlbl ?> <?= $loclbl ?>/#<?= $cnclbl ?>">
<select size="1" <?= $oc ?> name="pos" title="<?= $nodlbl ?> <?= $typlbl ?>">
<option value="">Icon
<option value="d" <?= ($pos == "d")?" selected":"" ?>><?= $shplbl ?> <?= $siz['t'] ?>
<option value="s" <?= ($pos == "s")?" selected":"" ?>><?= $shplbl ?> <?= $siz['s'] ?>
<option value="D" <?= ($pos == "D")?" selected":"" ?>><?= $imglbl ?> <?= $siz['s'] ?>
<option value="p" <?= ($pos == "p")?" selected":"" ?>><?= $imglbl ?> <?= $siz['m'] ?>
<option value="P" <?= ($pos == "P")?" selected":"" ?>><?= $imglbl ?> <?= $siz['l'] ?>
<option value="a" <?= ($pos == "a")?" selected":"" ?>><?= $avalbl ?>
<option value="c" <?= ($pos == "c")?" selected":"" ?>>CPU <?= $lodlbl ?>
<option value="h" <?= ($pos == "h")?" selected":"" ?>><?= $tmplbl ?>

</select>
<br>
<img src="img/16/home.png" title="<?= $place['b'] ?> <?= $cfglbl ?>">
<input type="number" min="6" max="1000" <?= $oi ?> name="fsz" <?= ($mde == "f" or $fmt == "json" or $lev < 4 and $dyn)?"disabled":"" ?> value="<?= $fsz ?>" size="3" title="<?= $place['f'] ?> <?= $sizlbl ?>">
<input type="number" min="1" max="50" <?= $oi ?> name="fco" <?= ($mde == "f" or $fmt == "json" or $lev < 4 and $dyn)?"disabled":"" ?> value="<?= $fco ?>" size="2" title="<?= $collbl ?>">

</td>
<td valign="top" nowrap><h3><?= $sholbl ?></h3>

<img src="img/16/port.png" title="IF <?= $inflbl ?>"> 
<input type="checkbox" title="IF <?= $namlbl ?>" <?= $oc ?> name="ifi" <?= $ifi ?>> <input type="checkbox" title="IF Alias" <?= $oc ?> name="ifa" <?= $ifa ?>><br>
<img src="img/16/glob.png" title="IP <?= $adrlbl ?>"> 
<input type="checkbox" title="Device IP" <?= $oc ?> name="ipd" <?= $ipd ?>> <input type="checkbox" title="IF IP" <?= $oc ?> name="ipi" <?= $ipi ?>><br>
<img src="img/16/fort.png" title="<?= $loclbl ?>"> <input type="checkbox" <?= $oc ?> name="loo" title="<?= $place['o'] ?>" <?= $loo ?>> <input type="checkbox" <?= $oc ?> name="loa" title="<?= $place['a'] ?>" <?= $loa ?>><br>
<img src="img/16/find.png" title="<?= $inflbl ?>"> <input type="checkbox" <?= $oc ?> name="dco" title="<?= $conlbl ?>" <?= $dco ?>> <input type="checkbox" <?= $oc ?> name="dmo" title="<?= $modlbl ?>" <?= $dmo ?>>

</td>
<th width="80" valign="top">

<h3>
<img src="img/16/exit.png" title="Stop" onClick="stop_countdown(interval);">
<span id="counter"><?= $refresh ?></span>
</h3>
<br>
<img src="img/16/walk.png" title="Dynamic-<?= $edilbl ?>"> <input type="checkbox" onchange="this.form.submit();" name="dyn" <?= $dyn ?>><br>

<p>
<input type="submit" value="<?= $cmdlbl ?>">

</th></tr>
</table></form><p>
<?php
}

if($fmt == 'json'){
	if( !isset($_GET['print']) ){echo "<h2>Json Map <a href=\"map/map_$_SESSION[user].json\" title=\"$lenlbl: $len $metlbl: $lal $cnclbl: $lsf\">($srclbl)</a></h2>";}
	Map();
	WriteJson();
?>

<style>
.chart {
	background-color: white;
	display:block;
	margin: 0 auto;
	border:1px solid black;
}
.node {
	stroke: #222;
	stroke-width: 0.2px;
	font-size:8px;
}

.link {
	stroke: #555;
	stroke-opacity: .6;
}
</style>

<script src="inc/d3.v3.lic-min.js"></script> 
<script>
var	width = <?= $xm ?>,
	height = <?= $ym ?>;

var color = d3.scale.category20();

var force = d3.layout.force()
	.charge(<?= -3*$lal ?>)
	.linkDistance(<?= intval($len/4) ?>)
	.size([width, height]);

var svg = d3.select("body")
	.append("svg")
	.attr("class", "chart")
	.attr("width", width)
	.attr("height", height);

d3.json("map/map_<?= $_SESSION['user'] ?>.json", function(error, graph){
	force
	.nodes(graph.nodes)
	.links(graph.links)
	.start();

	var link = svg.selectAll(".link")
		.data(graph.links)
		.enter().append("line")
		.attr("class", "link")
		.style("stroke-width", function(d) { return d.value; });

	var node = svg.selectAll(".node")
		.data(graph.nodes)
		.enter().append("g")
		.attr("class", "node")
		.call(force.drag);

	node
	.filter(function(d) { return d.type == "circle"; })
	.append("circle")
	.attr("r", function(d) { return d.width; })
	.style("fill", function(d) { return d.style; });

	node
	.filter(function(d) { return d.type == "rect"; })
	.append("rect")
	.attr("x", function(d) { return -d.width/2; })
	.attr("y", function(d) { return -d.height/2; })
	.attr("width", function(d) { return d.width; })
	.attr("height", function(d) { return d.height; })
	.style("fill", function(d) { return d.style; });

	node
	.filter(function(d) { return d.type == "icon"; })
	.append("image")
	.attr("xlink:href", function(d) { return "img/"+d.style+".png"; })
	.attr("x", function(d) { return -d.width/2; })
	.attr("y", function(d) { return -d.height/2; })
	.attr("width", function(d) { return d.width; })
	.attr("height", function(d) { return d.height; });

	node
	.filter(function(d) { return d.type == "panel"; })
	.append("image")
	.attr("xlink:href", function(d) { return d.style; })
	.attr("x", function(d) { return -d.width/2; })
	.attr("y", function(d) { return -d.height/2; })
	.attr("width", function(d) { return d.width; })
	.attr("height", function(d) { return d.height; });

<?PHP if($pos == "d"){?>

		node.append("title")
			.text(function(d) { return d.name; });
<?PHP }else{ ?>
		node.append("text")
			.attr("dx", function(d) { return -d.name.length * 2 })
			.attr("dy", function(d) { return Math.floor(d.height/8+16); })
			.text(function(d) { return d.name });
<?PHP } # Cheating on this one ;-) ?>

		force.on("tick", function() {
			link.attr("x1", function(d) { return d.source.x; })
			.attr("y1", function(d) { return d.source.y; })
			.attr("x2", function(d) { return d.target.x; })
			.attr("y2", function(d) { return d.target.y; });

			node.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
		});

});

</script>
<?PHP	
}elseif($fmt == 'svg'){
	if( !isset($_GET['print']) ){echo "<h2>SVG Map</h2>";}
	Map();
	WriteSVG( Condition($in,$op,$st,$co,1) );
?>
	<embed width="<?= $xm ?>" height="<?= $ym ?>" src="map/map_<?= $_SESSION[user] ?>.svg" name="SVG Map" type="image/svg+xml" style="display:block;margin-left:auto;margin-right:auto;border:1px solid black">
<?php
}else{
	if($fmt){
		if( !isset($_GET['print']) ){
			echo "<h2><a href=\"Reports-Combination.php?map=1\"><img src=\"img/16/chrt.png\" title=\"$sholbl $cmblbl Report\"></a> PNG Map</h2>";
		}
		Map();
		WritePNG( Condition($in,$op,$st,$co,1) );
	}else{
		if( !isset($_GET['print']) ){echo "<h3>PNG Map ($laslbl)</h3>";}
	}
	if (file_exists("map/map_$_SESSION[user].php")) {
?>
<img style="display:block;margin-left:auto;margin-right:auto;border:1px solid black" usemap="#net" src="map/map_<?= $_SESSION['user'] ?>.php">
<map name="net">
<?= $imgmap ?>
</map>
<?php
	}
}

include_once ("inc/footer.php");

?>
