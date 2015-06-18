<?php
#============================================================================
#
# Program: libmap.php
# Programmer: Remo Rickli
#
# Functions for creating a map.
#
#============================================================================

#===================================================================
# Generate the PHP script for the image.

function WritePNG($flt,$mod=0){

	global $xm,$ym,$mde,$fmt,$tit,$now, $dev;
	global $mapbg,$mapinfo,$mapframes,$maplinks,$mapitems;

	$maphdr = array();
	$mapftr = array();

       	$map  = ($mod)?"":"<?PHP\n";
	$map .= "session_start();\n";
	#$map .= "if(!isset(\$_SESSION['group'])){exit;}\n"; TODO implement for stricter security?
	$map .= "# PNG map created on $now by $_SESSION[user] using NeDi (visit http://www.nedi.ch for more info)\n";
	$map .= "ini_set(\"memory_limit\",\"64M\");\n";
	$map .= "header(\"Content-type: image/png\");\n";
	$map .= "error_reporting(0);\n";
	if($mde == "g"){
		$map .= "\$image = Imagecreatefromjpeg(\"../topo/$mapbg\");\n";
		$map .= "Imagealphablending(\$image,true);\n";
		$map .= "\$gainsboro  = Imagecolorallocatealpha(\$image, 230, 230, 230, 40);\n";
		$map .= "\$whitesmoke = Imagecolorallocatealpha(\$image, 245, 245, 245, 40);\n";
	}elseif ($fmt == "png"){
		$map .= "\$image = Imagecreatetruecolor($xm, $ym);\n";
		$map .= "Imagealphablending(\$image,true);\n";
		$map .= "\$gainsboro  = Imagecolorallocatealpha(\$image, 230, 230, 230, 25);\n";
		$map .= "\$whitesmoke = Imagecolorallocatealpha(\$image, 245, 245, 245, 25);\n";
		$map .= "\$white      = ImageColorAllocate(\$image, 255, 255, 255);\n";
		$map .= "ImageFilledRectangle(\$image, 0, 0, $xm, $ym, \$white);\n";
	}else{
		$map .= "\$image = Imagecreate($xm, $ym);\n";
		$map .= "\$gainsboro  = ImageColorAllocate(\$image, 230, 230, 230);\n";
		$map .= "\$whitesmoke = ImageColorAllocate(\$image, 245, 245, 245);\n";
		$map .= "\$white      = ImageColorAllocate(\$image, 255, 255, 255);\n";
		$map .= "ImageFilledRectangle(\$image, 0, 0, $xm, $ym, \$white);\n";
	}
	$map .= "\$red       = ImageColorAllocate(\$image, 200, 0, 0);\n";
	$map .= "\$purple    = ImageColorAllocate(\$image, 128, 0,128 );\n";
	$map .= "\$yellow    = ImageColorAllocate(\$image, 220, 200, 0);\n";
	$map .= "\$orange    = ImageColorAllocate(\$image, 250, 150, 0);\n";
	$map .= "\$green     = ImageColorAllocate(\$image, 0, 130, 0);\n";
	$map .= "\$limegreen = ImageColorAllocate(\$image, 50, 200, 50);\n";
	$map .= "\$navy      = ImageColorAllocate(\$image, 0, 0, 130);\n";
	$map .= "\$blue      = ImageColorAllocate(\$image, 80, 100, 250);\n";
	$map .= "\$burlywood = ImageColorAllocate(\$image,222,184,135);\n";
	$map .= "\$cornflowerblue      = ImageColorAllocate(\$image, 100, 150, 220);\n";
	$map .= "\$gray      = ImageColorAllocate(\$image, 100, 100, 100);\n";
	$map .= "\$lightgray = ImageColorAllocate(\$image, 211, 211, 211);\n";
	$map .= "\$black     = ImageColorAllocate(\$image, 0, 0, 0);\n";
	if($tit == '#'){
		$map .= "ImageString(\$image, 1, 8, 8, \"".count($dev)." Devs\", \$gray);\n";
	}elseif($tit){
		$map .= "ImageString(\$image, 5, 8, 8, \"$tit\", \$black);\n";
		$map .= "ImageString(\$image, 1, 8, 26, \"".count($dev)." Devs ($flt)\", \$gray);\n";
		$map .= "ImageString(\$image, 1, ".($xm - 120).",".($ym - 10).", \"$_SESSION[user] $now\", \$gray);\n";
	}

	$map .= $mapinfo . $mapframes . $maplinks . "imagesetthickness(\$image,1);\n" . $mapitems;

	$map .= "Imagepng(\$image);\n";
	$map .= "Imagedestroy(\$image);\n";

	if($mod){
		eval($map);
	}else{
		$map .= " ?>\n";

		$fd =  @fopen("map/map_$_SESSION[user].php","w") or die ("can't create map/map_$_SESSION[user].php");
		fwrite($fd,$map);
		fclose($fd);
	}
}

#===================================================================
# Generate the SVG xml.

function WriteSVG($flt){

	global $xm,$ym,$dev,$mde,$tit,$now,$mapinfo,$mapframes,$maplinks,$mapitems;

       	$map  = "<?php xml version=\"1.0\" encoding=\"iso-8859-1\" standalone=\"no\" ?>\n";
	$map .= "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.0//EN\" \"http://www.w3.org/TR/SVG/DTD/svg10.dtd\">\n";
	$map .= "<svg viewBox=\"0 0 $xm $ym\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";
	$map .= "<g id=\"main\" font-size=\"9\">\n";
	$map .= "<rect id=\"canvas\" width=\"$xm\" height=\"$ym\" x=\"0\" y=\"0\" stroke=\"black\" fill=\"white\" />\n";
	$map .= "<g id=\"title\">\n";
	$map .= "	<text x=\"8\" y=\"20\" font-size=\"16\" font-weight=\"bold\">$tit</text>\n";
	$map .= "	<text x=\"8\" y=\"32\" style=\"fill:gray;\">".count($dev)." Devices $flt</text>\n";
	$map .= "	<text x=\"".($xm - 120)."\" y=\"".($ym - 5)."\" style=\"fill:gray;\">$_SESSION[user] $now</text>\n";
	$map .= "</g>\n";

	$map .= "<g id=\"info\">\n";
	$map .= $mapinfo;
	$map .= "</g>\n";

	$map .= "<g id=\"frames\">\n";
	$map .= $mapframes;
	$map .= "</g>\n";

	$map .= "<g id=\"links\">\n";
	$map .= $maplinks;
	$map .= "</g>\n";

	$map .= "<g id=\"items\">\n";
	$map .= $mapitems;
	$map .= "</g>\n";

	$map .= "</g></svg>\n";

	$fd =  @fopen("map/map_$_SESSION[user].svg","w") or die ("can't create map/map_$_SESSION[user].svg");
	fwrite($fd,$map);
	fclose($fd);
}

#===================================================================
# Generate the Json script. TheJit compatble output on flat and d3 on other maps
function WriteJson($mod=0) {

	global $debug,$xm,$ym,$len,$mde,$tit,$mapinfo,$mapframes,$maplinks,$mapitems,$srclbl;

	$jsdata = "{\n  \"nodes\":[\n".substr($mapitems,0,-2)."\n  ],\n  \"links\":[\n".substr($maplinks,0,-2)."\n  ]\n}";
	if($mod){
		echo $jsdata;
	}else{
		$fd =  @fopen("map/map_$_SESSION[user].json","w") or die ("can't create map/map_$usr.json");
		fwrite($fd, $jsdata);
		fclose($fd);
		if($debug) var_dump(json_decode($jsdata));
	}
}

#===================================================================
# Draws a link.
function DrawLink($x1,$y1,$x2,$y2,$opt) {

	global $fmt,$lev,$lix,$liy,$lis,$lit,$lil,$lal,$ipi,$ifi,$ifa,$pos,$xm,$ym,$debug;
	global $dev,$maplinks,$mapitems,$errlbl,$trflbl,$rrdcmd,$rrdstep,$nedipath,$liy;
	
	$liy["$x1,$y1,$y2"] = ($liy["$x1,$y1,$y2"])?0:9;						# offset coherent if/ip info on start of links from a node where link end is same y
	$liy["$x2,$y2,$y1"] = ($liy["$x2,$y2,$y1"])?0:9;						# offset coherent if/ip info on end of links from a node where link end is same y
        if($x1 == $x2){											# offset coherent, horizontal links...
                $lix[$x1]+= 2;
                $x1 += $lix[$x1];
                $x2 = $x1;
        }elseif($y1 == $y2){										# offset coherent, verical links...
                $liy[$y1]+= 2;
                $y1 += $liy[$y1];
                $y2 = $y1;
        }
	$xlm = intval($x1 + $x2) / 2;									# middle of link
	$ylm = intval($y1 + $y2) / 2;

	$dctr1 = sqrt( pow(($x1 - $xm/2),2) + pow(($y2 - $ym/2),2) );					# Pythagoras tells distance to map center of either possible arc centerpoint
	$dctr2 = sqrt( pow(($x2 - $xm/2),2) + pow(($y1 - $ym/2),2) );

	if($dctr1 < $dctr2){
		$xctr = $x1;
		$yctr = $y2;
		$xedg = $xr2 = $x2;
		$xr1  = $xedg-intval(($xedg-$xctr)/8);
		$yedg = $yr1 = $y1;
		$yr2  = $yedg-intval(($yedg-$yctr)/8);
	}else{
		$xctr = $x2;
		$yctr = $y1;
		$xedg = $xr1 = $x1;
		$xr2  = $xedg-intval(($xedg-$xctr)/8);
		$yedg = $yr2 = $y2;
		$yr1  = $yedg-intval(($yedg-$yctr)/8);
	}
	#$maplinks .= "ImageString(\$image, 3, $xctr,$yctr,\"C\", \$blue);\n";
	#$maplinks .= "ImageString(\$image, 3, $xedg,$yedg,\"E\", \$blue);\n";

	$futl = round($opt['ftr']*800/$opt['fbw']/$rrdstep,1);
	list($t,$cf)  = LinkStyle( $opt['fbw'],$futl );
	if( is_numeric($opt['rbw']) and $opt['rbw'] > 0 ){
		$rutl = round($opt['rtr']*800/$opt['rbw']/$rrdstep,1);
		list($tr,$cr) = LinkStyle( $opt['rbw'],$rutl );
	}else{
		$rutl = round($opt['rtr']*800/$opt['fbw']/$rrdstep,1);
		list($tr,$cr) = LinkStyle( $opt['fbw'],$rutl );
	}

	#$maplinks .= "ImageString(\$image, 3, $x1,$y1,\"Start\", \$blue);\n";
	$maplinks .= "\$stylarr = array(\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,
					\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf, 
					\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf, 
					\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf,\$$cf, 
					\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,
					\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,
					\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,
					\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr,\$$cr);\n";
	$maplinks .= "imagesetstyle(\$image,\$stylarr);\n";
	$lsty = 'IMG_COLOR_STYLED';

	#$maplinks .= "\$bru = imagecreatefrompng('../img/netr.png');\n";
	#$maplinks .= "imagesetbrush(\$image, \$bru);\n";
	#$lsty = 'IMG_COLOR_BRUSHED';

	if($lis == "a1" or $x2 == $x1 and $y2 == $y1){
		if($x2 == $x1 and $y2 == $y1){
			$y1 += 1;
			$w = 100;
			$h = 50;
			$s = 0;
			$e = 360;
			$xctr  += 50;
			$xlm = $xctr + 50;
			$ylm = $y1;
		}else{
			$w = 2*abs($x2-$x1);
			$h = 2*abs($y2-$y1);

			$l = sqrt($w*$h)/10;
			
			if($xctr > $xedg){								# Left half
				if($yctr > $yedg){							# Upper Quadrant
					$s = 180;$e = 270;$ylm -= $l;
				}else{
					$s = 90;$e = 180;$ylm += $l;
				}
				$xlm -= $l;
			}else{
				if($yctr > $yedg){
					$s = 270;$e = 0;$ylm -= $l;
				}else{
					$s = 0;$e = 90;$ylm += $l;
				}
				$xlm += $l;
			}
		}
		if($fmt == "svg"){
			$maplinks .= "<path d=\"M $x1 $y1 A $w $h 0 0 1 $x2 $y2\" stroke=\"$cf\" stroke-width=\"$t\" fill = \"none\"/>\n";
		}else{
			$maplinks .= "imagesetthickness(\$image,$t);\n";
			$maplinks .= "imagearc(\$image, $xctr, $yctr, $w, $h, $s, $e, $lsty);\n";
		}
	}elseif($lis == "a2" or $lis == "a3"){
		$xlm = $xedg;
		$ylm = $yedg;
		if($fmt == "svg"){
			$maplinks .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$xlm\" y2=\"$ylm\" stroke=\"$cf\" stroke-width=\"$t\"/>\n";
			$maplinks .= "<line x1=\"$xlm\" y1=\"$ylm\" x2=\"$x2\" y2=\"$y2\" stroke=\"$cf\" stroke-width=\"$t\"/>\n";
		}elseif($lis == "a3"){
			$maplinks .= "imagesetthickness(\$image,$t);\n";
			$maplinks .= "imageline(\$image,$x1,$y1,$xr1,$yr1,$lsty);\n";
			$maplinks .= "imageline(\$image,$xr2,$yr2,$x2,$y2,$lsty);\n";
			$maplinks .= "imageline(\$image,$xr1,$yr1,$xr2,$yr2,$lsty);\n";
		}else{
			$maplinks .= "imagesetthickness(\$image,$t);\n";
			$maplinks .= "imageline(\$image,$x1,$y1,$xlm,$ylm,$lsty);\n";
			$maplinks .= "imageline(\$image,$xlm,$ylm,$x2,$y2,$lsty);\n";
		}
	}elseif($lis == "a4"){
		$xlm = $xedg-intval(($xedg-$xctr)/5);
		$ylm = $yedg-intval(($yedg-$yctr)/5);
		if($fmt == "svg"){
			$maplinks .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$xlm\" y2=\"$ylm\" stroke=\"$cf\" stroke-width=\"$t\"/>\n";
			$maplinks .= "<line x1=\"$xlm\" y1=\"$ylm\" x2=\"$x2\" y2=\"$y2\" stroke=\"$cf\" stroke-width=\"$t\"/>\n";
		}else{
			$maplinks .= "imagesetthickness(\$image,$t);\n";
			$maplinks .= "imageline(\$image,$x1,$y1,$xlm,$ylm,$lsty);\n";
			$maplinks .= "imageline(\$image,$xlm,$ylm,$x2,$y2,$lsty);\n";
		}
	}else{
		if($fmt == "svg"){
			$maplinks .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"$cf\" stroke-width=\"$t\"/>\n";
		}else{
			$maplinks .= "imagesetthickness(\$image,$t);\n";
			$maplinks .= "imageline(\$image,$x1,$y1,$x2,$y2,$lsty);\n";
		}
	}

	$xlm = $xlm + $lil/10*intval($xm/($xlm - $xm/2.1));						# move info on a ray from the center
	$ylm = $ylm + $lil/10*intval($ym/($ylm - $ym/2.1));						# .1 to avoid div 0

	if( is_array($opt['fif']) ){
		$yof = 2 + $liy["$x1,$y1,$y2"];
		foreach ($opt['fif'] as $fi){
			$f = explode(';;', $fi);
			$ifal = ($ifa and $dev[$f[0]]['ifal'][$f[1]])?" ".$dev[$f[0]]['ifal'][$f[1]]:"";
			if( preg_match('/^[febd]/',$lit) and $rrdcmd ){
				$rrd = "$nedipath/rrd/" . rawurlencode($f[0]) . "/" . rawurlencode($f[1]) . ".rrd";
				if (file_exists($rrd)){
					$rrdif["$f[0]-$f[1]"] = $rrd;
				}elseif($debug){
					echo "<div class=\"textpad alrm\">FRRD:$rrd not found!</div>\n";
				}
			}
			if($lev > 3){
				$ifl = ($ifi and $f[1] != '-')?$f[1]:'';
			}else{
				$ifl = ($ifi)?"$f[0] $f[1]":"";
			}
			$ipl = ($ipi)?$dev[$f[0]]['ifip'][$f[1]]:"";
			$alpha = atan2( ($ylm-$y1),($xlm-$x1) );
			$mapitems .= DrawLabel(	$x1+cos($alpha)*$lal,
						$y1+sin($alpha)*$lal+$yof,
						Safelabel("$ifl$ipl$ifal"),1,"gray");
			$yof += 9;
		}
	}
	if( is_array($opt['rif']) ){
		$yof = 2 + $liy["$x2,$y2,$y1"];
		foreach ($opt['rif'] as $ri){
			$r = explode(';;', $ri);
			$ifal = ($ifa and $dev[$r[0]]['ifal'][$r[1]])?" ".$dev[$r[0]]['ifal'][$r[1]]:"";
			if($lev > 3){
				$ifl = ($ifi and $r[1] != '-')?$r[1]:'';
			}else{
				$ifl = ($ifi)?"$r[0] $r[1]":"";
			}
			$ipl = ($ipi)?$dev[$r[0]]['ifip'][$r[1]]:"";
			$alpha = atan2( ($ylm-$y2),($xlm-$x2) );
			$mapitems .= DrawLabel(	$x2+cos($alpha)*$lal,
						$y2+sin($alpha)*$lal+$yof,
						Safelabel("$ifl$ipl$ifal"),1,"gray");
			$yof += 9;
		}
	}

	if($lit == 'w'){
		$mapitems .= DrawLabel($xlm,$ylm-8,DecFix($opt['fbw']) . "/" . DecFix($opt['rbw']),1,"green");
	}elseif($lit == 't'){
		foreach ($opt['fty'] as $t => $c){
			$ftyp .= ($c > 1)?"${c}x $t ":"$t ";
		}
		foreach ($opt['rty'] as $t => $c){
			$rtyp .= ($c > 1)?"${c}x $t ":"$t ";
		}
		$mapitems .= DrawLabel($xlm,$ylm- 8,$ftyp,1,"blue");
		$mapitems .= DrawLabel($xlm,$ylm-16,$rtyp,1,"blue");
	}elseif($lit == 'l' and $pos != "d"){
		$mapitems .= DrawLabel($xlm,$ylm-8,"$futl%/$rutl%",3,"black");
	}elseif( is_array($rrdif) ){
		if( preg_match('/^f/',$lit) ){
			$opts = GraphOpts(substr($lit,1),0,0,$trflbl,$opt['fbw']);
			list($draw,$tit) = GraphTraffic($rrdif,'trf');
			$mapitems .= DrawLabel($xlm,$ylm-25,DecFix($opt['fbw']) . "/" . DecFix($opt['rbw']),1,"green");
		}elseif( preg_match('/^e/',$lit) ){
			$opts = GraphOpts(substr($lit,1),0,0,$errlbl,1);
			list($draw,$tit) = GraphTraffic($rrdif,'err');
		}elseif( preg_match('/^d/',$lit) ){
			$opts = GraphOpts(substr($lit,1),0,0,"Discards",0);
			list($draw,$tit) = GraphTraffic($rrdif,'dsc');
		}else{
			$opts = GraphOpts(substr($lit,1),0,0,"Broadcasts",0);
			list($draw,$tit) = GraphTraffic($rrdif,'brc');
		}
		exec("$rrdcmd graph map/$xlm$ylm.png -a PNG $opts $draw");
		if($fmt == "json"){
		}elseif($fmt == "svg"){
			$mapitems .= "	<text x=\"$xlm\" y=\"$ylm\" fill=\"gray\">no RRDs in SVG!</text>\n";
		}else{
			$mapitems .= "\$icon = Imagecreatefrompng(\"$xlm$ylm.png\");\n";
			$mapitems .= "\$w = Imagesx(\$icon);\n";
			$mapitems .= "\$h = Imagesy(\$icon);\n";
			$mapitems .= "Imagecopy(\$image, \$icon,$xlm-\$w/2,$ylm-\$h/2,0,0,\$w,\$h);\n";
			$mapitems .= "Imagedestroy(\$icon);\n";
			$mapitems .= "unlink(\"$xlm$ylm.png\");\n";
		}
	}
}
#===================================================================
# Draws box

function DrawBuilding($x,$y,$r,$c,$b) {

	global $lev,$loi,$flr,$fsz,$fco,$fmt,$imas;
	global $pos,$dev,$mapframes,$mapitems,$imgmap;

	$row = $rows = $cols = 0;
	foreach(array_keys($flr[$r][$c][$b]) as $f){							# Determine building size
		$curcol = count($flr[$r][$c][$b][$f]);
		$cols   = max($curcol,$cols);
		if($curcol > $fco){									# Break row, if > Floor columns
			$rows += ceil($curcol / $fco);							# How many rows result?
			$cols = $fco;
		}else{
			$rows++;
		}
	}
	$woff = intval($fsz*($cols-1)/2);
	$hoff = intval($fsz*($rows-1)/2);

	$x1 = $x - $woff - intval($fsz/2) - $imas/2;
	$y1 = $y - $hoff - intval($fsz/2);
	$x2 = $x + $woff + intval($fsz/2);
	$y2 = $y + $hoff + intval($fsz/2);

	if($fmt == "json"){
	}elseif($fmt == "svg"){
		$mapframes .= "	<rect fill=\"whitesmoke\" x=\"$x1\" y=\"$y1\" width=\"".($x2-$x1)."\" height=\"".($y2-$y1)."\" fill-opacity=\"0.6\" />\n";
		if($pos == "d"){
			if($loi) $mapframes .= "	<text x=\"$x1\" y=\"".($y1-4)."\" font-size=\"12\" fill=\"blue\">$b</text>\n";
		}else{
			$mapframes .= "	<rect fill=\"gainsboro\" x=\"$x1\" y=\"".($y1+15)."\" width=\"20\" height=\"".($y2-$y1-20)."\" fill-opacity=\"0.6\" />\n";
			$mapframes .= "	<text x=\"".($x1+4)."\" y=\"".($y1+12)."\" font-size=\"12\" fill=\"blue\">$b</text>\n";
		}
		$mapframes .= "	<rect fill=\"none\" stroke=\"black\" x=\"$x1\" y=\"$y1\" width=\"".($x2-$x1)."\" height=\"".($y2-$y1)."\"/>\n";
	}else{
		$mapframes .= "Imagefilledrectangle(\$image, $x1, $y1, $x2, $y2, \$whitesmoke);\n";
		if($pos == "d"){
			if($loi) $mapframes .= "ImageString(\$image, 3, $x1, ".($y1-14).",\"$b\", \$blue);\n";
		}else{
			$mapframes .= "Imagefilledrectangle(\$image, $x1, ".($y1+15).", ".($x1+20).", $y2, \$gainsboro);\n";
			$mapframes .= "ImageString(\$image, 3, ".($x1+4).", $y1,\"$b\", \$blue);\n";
		}
		$mapframes .= "Imagerectangle(\$image, $x1, $y1, $x2, $y2, \$black);\n";
	}
	uksort($flr[$r][$c][$b], "Floorsort");
	foreach(array_keys($flr[$r][$c][$b]) as $f){
		$mapitems .= DrawItem(	$x - $woff - intval($fsz/2),
					$y - $hoff + $row*$fsz,
					0,$f,'fl');
		usort( $flr[$r][$c][$b][$f],"Roomsort" );
		$col = 0;
		foreach($flr[$r][$c][$b][$f] as $dv){
			if($col == $fco){
				$col = 0;
				$row++;
			}
			$dev[$dv]['x'] = $x - $woff + $col*$fsz;
			$dev[$dv]['y'] = $y - $hoff + $row*$fsz;
			$mapitems .= DrawItem($dev[$dv]['x'],$dev[$dv]['y'],'0',$dv,'d');
			$imgmap   .= "<area href=\"Devices-Status.php?dev=".urlencode($dv)."\" coords=\"".($dev[$dv]['x']-$imas) .",". ($dev[$dv]['y']-$imas) .",". ($dev[$dv]['x']+$imas) .",". ($dev[$dv]['y']+$imas)."\" shape=\"rect\" title=\"$dv ".$dev[$dv]['ip']." CPU:".$dev[$dv]['cpu']."%  T:".$dev[$dv]['tmp']."C\">\n";
			if( $lev == 6){DrawNodes($dv);}
			$col++;
		}
		$row++;
	}

}

//===================================================================
// Return device shape style based on icon
function Devshape($ico="xxan"){

	$lev = substr($ico,1,1);
	$col = substr($ico,2,1);
	$shd = substr($ico,3,1);

	if($shd == 'd' or $lev == 'b'){
		$x = 16;
	}elseif($shd == 'n' or $lev == 'm'){
		$x = 12;
	}elseif($shd == 'p' or $lev == 's'){
		$x = 10;
	}else{
		$x = 6;
	}

	$shp = 'r';
	if( preg_match('/^c[23]/',$ico) ){
		$y = $x;
	}elseif( preg_match('/^w[23]/',$ico) ){
		$y = $x/2;
	}elseif( preg_match('/^ph/',$ico) ){
		$x = 6;
		$y = 8;
	}elseif( preg_match('/^w[ab]/',$ico) ){
		$shp = 'c';
		$x = 6;
		$y = 8;
	}elseif( preg_match('/^r/',$ico) ){
		$shp = 'c';
		$y = intval($x/1.5);
	}else{
		$x /= 2;
		$y = $x;
	}

	if($col == "b"){
		return array("blue",$x,$y,$shp);
	}elseif($col == "g"){
		return array("green",$x,$y,$shp);
	}elseif($col == "o"){
		return array("orange",$x,$y,$shp);
	}elseif($col == "r"){
		return array("red",$x,$y,$shp);
	}elseif($col == "p"){
		return array("purple",$x,$y,$shp);
	}elseif($col == "y"){
		return array("yellow",$x,$y,$shp);
	}else{
		return array("gray",$x,$y,$shp);
	}
}

#===================================================================
# Draws a single item

function DrawItem($x,$y,$opt,$label,$typ) {

	global $fmt,$dev,$nod,$pos,$loi,$dvi,$ipd,$redbuild,$cpua,$tmpa,$stco;

	$r  = ($opt > 2)?12:6;
	$lx = intval($x-strlen($label) * 2);

	if($typ == 3){											# Building
		$bc = ( preg_match('/$redbuild/',$label) )?"red":"burlywood";
		if($pos == "s"){
			$itxt = IconRect($x,$y,$r,$r,$bc);
			if($fmt == "json"){
				$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
			}else{
				$itxt .= DrawLabel($x,$y+$r,Safelabel($label),2,"navy");
			}
		}elseif($pos == "d"){
			$itxt = IconRect($x,$y,$r/3,$r/3,$bc);
			if($fmt == "json") $itxt .= ',"name":"'.Safelabel($label)."\"},\n";
		}else{
			$itxt = IconPng($x,$y,BldImg($opt,$label),30);
			if($fmt == "json"){
				$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
			}else{
				$itxt .= DrawLabel($x,$y+$r*2,Safelabel($label),3,"navy");
			}
		}
	}elseif($typ == 2){										# City
		if($pos == "s"){
			$itxt = IconCircle($x,$y,$r,$r,"purple");
			if($fmt == "json"){
				$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
			}else{
				$itxt .= DrawLabel($x,$y+$r,Safelabel($label),2,"navy");
			}
		}elseif($pos == "d"){
			$itxt = IconCircle($x,$y,$r/3,$r/3,"purple");
			if($fmt == "json") $itxt .= ',"name":"'.Safelabel($label)."\"},\n";
		}else{
			$itxt = IconPng($x,$y,CtyImg($opt),48);
			if($fmt == "json"){
				$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
			}else{
				$itxt .= DrawLabel($x,$y+25,Safelabel($label),4,"navy");
			}
		}
	}elseif($typ == 1){										# Region
		if($pos == "s"){
			$itxt = IconCircle($x,$y,$r,$r,"cornflowerblue");
			if($fmt == "json"){
				$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
			}else{
				$itxt .= DrawLabel($x,$y+$r,Safelabel($label),2,"navy");
			}
		}elseif($pos == "d"){
			$itxt = IconCircle($x,$y,$r/3,$r/3,"cornflowerblue");
			if($fmt == "json") $itxt .= ',"name":"'.Safelabel($label)."\"},\n";

		}else{
			$itxt = IconPng($x,$y,"32/glob",32);
			if($fmt == "json"){
				$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
			}else{
				$itxt .= DrawLabel($x,$y+12,Safelabel($label),4,"navy");
			}
		}
	}elseif($typ == "ri"){										# Regioninfo
		if($pos == "s"){
			$itxt = IconCircle($x,$y,10,6,"gainsboro");
		}elseif($pos == "d"){
			$itxt = IconCircle($x,$y,4,2,"gainsboro");
		}else{
			$itxt = IconPng($x,$y,"regg",25);
		}
		$itxt .= DrawLabel($x,$y+10,Safelabel($label),2,"cornflowerblue");
	}elseif($typ == "ci"){										# Cityinfo
		if($pos == "s"){
			$itxt = IconRect($x,$y,10,6,"whitesmoke");
		}elseif($pos == "d"){
			$itxt = IconRect($x,$y,4,2,"whitesmoke");
		}else{
			$itxt = IconPng($x,$y,"cityg",30);
		}
		$itxt .= DrawLabel($x,$y+10,Safelabel($label),2,"cornflowerblue");
	}elseif($typ == "bi"){										# Bldinfo
		if($pos == "s"){
			$itxt .= IconRect($x,$y,4,4,"whitesmoke");
		}elseif($pos == "d"){
			$itxt .= IconRect($x,$y,2,2,"whitesmoke");
		}else{
			$itxt .= IconPng($x,$y,"bldg",30);
		}
		$itxt .= DrawLabel($x,$y+10,Safelabel($label),2,"cornflowerblue");
	}elseif($typ == "fl"){										# Floorinfo
		if($pos == "s"){
			$itxt = IconRect($x,$y,3,2,"black");
			$itxt .= DrawLabel($x,$y+6,Safelabel($label),3,"navy");
		}elseif($pos == "d"){
			$itxt .= IconRect($x,$y,1,0.5,"black");
		}else{
			$itxt = IconPng($x,$y,"stair",10);
			$itxt .= DrawLabel($x,$y+6,Safelabel($label),3,"navy");
		}
	}elseif($typ == "d"){										# Device
		list($clr,$sx,$sy,$shp) = Devshape($dev[$label]['ico']);
		if($pos == "s"){
			if($shp == "c"){
				$itxt = IconCircle($x,$y,$sx,$sy,$clr);
			}else{
				$itxt = IconRect($x,$y,$sx,$sy,$clr);
			}
			if($dev[$label]['stk'] > 1){
				$itxt .= DrawLabel($x+20,$y-6,$dev[$label]['stk'],2,"blue");
			}
		}elseif($pos == "d"){
			$sx = intval($sx/3);
			$sy = intval($sy/3);
			if($shp == "c"){
				$itxt = IconCircle($x,$y,$sx,$sy,$clr);
			}else{
				$itxt = IconRect($x,$y,$sx,$sy,$clr);
			}
		}elseif($pos == "c"){
			if($dev[$label]['cpu'] == "-"){
				$itxt = IconCircle($x,$y,8,4,"gray");
			}elseif($dev[$label]['cpu'] < $cpua/2){
				$itxt = IconRect($x,$y,12,6,"green");
				$itxt .= DrawLabel($x,$y-3,$dev[$label]['cpu']."%",1,"whitesmoke");
			}elseif($dev[$label]['cpu'] < $cpua){
				$itxt = IconRect($x,$y,16,8,"yellow");
				$itxt .= DrawLabel($x,$y-3,$dev[$label]['cpu']."%",1,"black");
			}else{
				$itxt = IconRect($x,$y,intval(0.5*$dev[$label]['cpu']),intval(0.2*$dev[$label]['cpu']),"orange");
				$itxt .= DrawLabel($x,$y-3,$dev[$label]['cpu']."%",2,"whitesmoke");
			}
			if($dev[$label]['stk'] > 1){
				$itxt .= DrawLabel($x+20,$y-6,$dev[$label]['stk'],3,"blue");
			}
		}elseif($pos == "h"){
			if(!$dev[$label]['tmp']){
				$itxt = IconCircle($x,$y,8,4,"gray");
			}elseif($dev[$label]['tmp'] < $tmpa/2){
				$itxt = IconRect($x,$y,12,6,"blue");
				$itxt .= DrawLabel($x,$y-3,$dev[$label]['tlb'],1,"whitesmoke");
			}elseif($dev[$label]['tmp'] < $tmpa){
				$itxt = IconRect($x,$y,16,8,"purple");
				$itxt .= DrawLabel($x,$y-3,$dev[$label]['tlb'],1,"whitesmoke");
			}else{
				$itxt = IconRect($x,$y,intval(0.5*$dev[$label]['tmp']),intval(0.2*$dev[$label]['tmp']),"red");
				$itxt .= DrawLabel($x,$y-3,$dev[$label]['tlb'],3,"whitesmoke");
			}
			if($dev[$label]['stk'] > 1){
				$itxt .= DrawLabel($x+20,$y-6,$dev[$label]['stk'],2,"blue");
			}
		}elseif($pos == 'a'){
			if($dev[$label]['sta'] == ''){
				$itxt .= IconCircle($x,$y,8,4,"gray");
			}elseif($dev[$label]['sta'] == 0){
				$itxt = IconRect($x,$y,18,6,"green");
				$itxt .= DrawLabel($x-4,$y-4,$stco['100'],1,"whitesmoke");
			}elseif($dev[$label]['sta'] == 1){
				$itxt = IconRect($x,$y,24,8,"yellow");
				$itxt .= DrawLabel($x-4,$y-4,$stco['250'],1,"black");
			}else{
				$itxt = IconRect($x,$y,24,8,"red");
				$itxt .= DrawLabel($x-4,$y-6,$stco['200'],3,"whitesmoke");
			}
			if($dev[$label]['stk'] > 1){
				$itxt .= DrawLabel($x+20,$y-6,$dev[$label]['stk'],2,"blue");
			}
		}elseif($pos == "p" or $pos == "P" or $pos == "D"){
			$itxt = Panel($x,$y,$dev[$label]['typ'],$dev[$label]['stk'],$dev[$label]['ico'],$dev[$label]['siz']);
			if( $pos != "D" ) $boxw = strlen($label);
		}else{
			$itxt = IconPng($x,$y,"dev/" . $dev[$label]['ico'],30);
			if($dev[$label]['stk'] > 1 and $fmt != "json"){
				$itxt .= IconPng($x+30,$y,$dev[$label]['stk'],16);
			}
		}

		if($fmt == "json"){
			$itxt .= ',"name":"'.Safelabel($label)."\"},\n";
		}elseif($pos != "d"){
			$devl = DrawLabel($x,$y+18,Safelabel($label),1,"black");
			if($loi){
				if($loi == 1){
					$locl = $dev[$label]['rom'];
				}elseif($loi == 2){
					$locl = $dev[$label]['rak'];
				}else{
					$locl = $dev[$label]['rom']." ".$dev[$label]['rak'];
				}
				$locw  = strlen($locl);
				$devl .= DrawLabel($x,$y-28,Safelabel($locl),1,"cornflowerblue");
			}
			if($ipd){
				$iplw  = strlen($dev[$label]['ip']);
				$devl .= DrawLabel($x,$y+26,$dev[$label]['ip'],1,"blue");
			}
			if($dvi){
				if($dvi == 1){
					$dvil = $dev[$label]['con'];
				}elseif($dvi == 2){
					$dvil = $dev[$label]['mod'];
				}elseif($dvi == 3){
					$dvil = $dev[$label]['con']." ".$dev[$label]['mod'];
				}
				$dviw  = strlen($dvil);
				$devl .= DrawLabel($x,$y+(($ipd)?34:26),Safelabel($dvil),1,"gray");
			}
			if($boxw){
				$boxw  = ($iplw > $boxw)?$iplw:$boxw;
				$boxw  = ($locw > $boxw)?$locw:$boxw;
				$boxw  = ($dviw > $boxw)?$dviw:$boxw;
				$pof   = (($ipd)?4:0) + (($dvi)?4:0);
				$itxt .= IconRect($x+5,$y+24+$pof/2,$boxw*4+4,8+$pof,"whitesmoke");
			}
			$itxt .= $devl;
		}
	}elseif($typ == "n"){
		if($pos == "s"){
			$itxt .= IconCircle($x,$y,5,5,"limegreen");
		}elseif($pos == "d"){
			$itxt = IconCircle($x,$y,2,2,"limegreen");
		}elseif($pos == "P"){
			$itxt = IconPng($x,$y,"32/node",32);
			$y += 6;
		}else{
			$itxt = IconPng($x,$y,"oui/" . $nod[$label]['ico'],20);
		}
		if($fmt == "json"){
			$itxt .= ',"name":"'.Safelabel($nod[$label]['nam'])."\"},\n";
		}elseif($pos != "d"){
			$itxt .= DrawLabel($x,$y+8,Safelabel($nod[$label]['nam']),1,"black");
			if ($ipd){$itxt .= DrawLabel($x,$y+16,$nod[$label]['ip'],1,"blue");}
		}
	}
	return $itxt;
}

#===================================================================
# Creates a Json device
function JsonDev($d,$jgrp){
	
	global $dev,$pos,$tmpa,$cpua,$stco;

	if($pos == "c"){
		if($dev[$d]['cpu'] == '-'){
			$jval = 1;
			$jtit = "$d CPU:-";
			if($jgrp === 'f')$jgrp = 2;
		}elseif($dev[$d]['cpu'] > $cpua){
			$jval = intval(($dev[$d]['cpu']-$cpua)/3+5);
			if($jgrp === 'f')$jgrp = 1;
		}else{
			$jval = 4;
			if($jgrp === 'f')$jgrp = 0;
		}
		$jimg = "16/cpu.png";
		$jtit = "$d CPU:".$dev[$d]['cpu']."%";
	}elseif($pos == "h"){
		if($dev[$d]['tmp'] > $tmpa){
			$jval = intval(($dev[$d]['tmp']-$tmpa)/2+5);
			if($jgrp === 'f')$jgrp = 2;
		}elseif($dev[$d]['tmp']){
			$jval = 4;
			if($jgrp === 'f')$jgrp = 1;
		}else{
			$jval = 1;
			if($jgrp === 'f')$jgrp = 0;
		}
		$jtit = "$d ".$dev[$d]['tlb'];
	}elseif($pos == "a"){
		if($dev[$d]['sta'] == 1){
			$jtit = "$d $stco[250]";
			$jval = 5;
			if($jgrp === 'f')$jgrp = 2;
		}elseif($dev[$d]['sta']){
			$jtit = "$d $stco[200]";
			$jval = intval(sqrt($dev[$d]['sta']))+3;
			if($jgrp === 'f')$jgrp = 1;
		}else{
			$jval = 2;
			$jtit = "$d $stco[100]";
			if($jgrp === 'f')$jgrp = 0;
		}
	}else{
		$jval = intval(sqrt($dev[$d]['siz'])*2)+1;
		$jtit = "$d ".(($dev[$d]['siz'])?$dev[$d]['siz'].'RU':'');
		if($jgrp === 'f')$jgrp = 0;
	}
	return "    {\"name\":\"$jtit\",\"img\":\"$jimg\",\"group\":$jgrp,\"value\":$jval},\n";
}

#===================================================================
# Draws nodes around device
function DrawNodes($dv){

	global $link,$fsz,$fco,$fmt,$len,$lsf,$in,$op,$st,$imas;
	global $dev,$nod,$nlnk,$mapframes,$mapitems,$imgmap,$sub,$cud,$jnod;

	if($sub){
		include_once ('libnod.php');
	}else{
		include_once ('inc/libnod.php');
	}

	if($in[0] == "vlanid" or $in[0] == "mac" or $in[0] == 'nodip' or $in[0] == 'name' or $in[0] == 'oui'){
		$nquery	= GenQuery('nodes','s','name,nodip,mac,oui,ifname,ifmetric,iftype,speed,duplex,pvid,alias,dinoct,doutoct','ifname','',array('device',$in[0]),array('=',$op[0]),array($dv,$st[0]),array('AND'),'LEFT JOIN interfaces USING (device,ifname)');
	}else{
		$nquery	= GenQuery('nodes','s','name,nodip,mac,oui,ifname,ifmetric,iftype,speed,duplex,pvid,alias,dinoct,doutoct','ifname','',array('device'),array('='),array($dv),array(),'LEFT JOIN interfaces USING (device,ifname)');
	}
	$nres	= DbQuery($nquery,$link);
	if($nres){
		$cun = 0;
		$nn  = DbNumRows($nres);
		while( ($n = DbFetchRow($nres)) ){
			$nod[$n[2]]['nam'] = $n[0];
			$nod[$n[2]]['ip'] = long2ip($n[1]).(($n[9])?" Vl$n[9]":"");
			$nod[$n[2]]['ico'] = Nimg("$n[2];$n[3]");
			list($nod[$n[2]]['x'],$nod[$n[2]]['y']) = CircleCoords($dev[$dv]['x'],$dev[$dv]['y'],$cun,$nn,8*($cun % 2),$len/pow($lsf/10,3),0,0);
			$mapitems .= DrawItem($nod[$n[2]]['x'],$nod[$n[2]]['y'],'0',$n[2],'n');
			$imgmap   .= "<area href=\"Nodes-Status.php?mac=$n[2]\" coords=\"".($nod[$n[2]]['x']-$imas) .",". ($nod[$n[2]]['y']-$imas) .",". ($nod[$n[2]]['x']+$imas) .",". ($nod[$n[2]]['y']+$imas)."\" shape=\"rect\" title=\"".$nod[$n[2]]['nam']." ".$nod[$n[2]]['ip']."\">\n";
			$nlnk["$dv;;$n[2]"]['fbw'] = $n[7];
			$nlnk["$dv;;$n[2]"]['rbw'] = $n[8];
			$nlnk["$dv;;$n[2]"]['ftr'] = $n[11];
			$nlnk["$dv;;$n[2]"]['rtr'] = $n[12];
			$nlnk["$dv;;$n[2]"]['ifal'][] = $n[10];
			$nlnk["$dv;;$n[2]"]['fif'][] = "$dv;;$n[4]";
			$nlnk["$dv;;$n[2]"]['rif'][] = ($n[5] < 256)?";;$n[5]db":"";			# Draws SNR...
			if($fmt == "json") $cud++;
			$jnod["$dv;;$n[2]"] = $cud;
			$cun++;

		}
		DbFreeResult($nres);
	}else{
		echo DbError($link);
	}
}

#===================================================================
# Generate PNG icon text
function IconPng($x,$y,$i,$s){

	global $fmt;

	if($i){
		if($fmt == "json"){
			return "	{\"type\":\"icon\",\"height\":$s,\"width\":$s,\"style\":\"$i\"";
		}elseif($fmt == "svg"){
			return "<image x=\"".($x-$s/2)."\" y=\"".($y-$s/2)."\" width=\"$s\" height=\"$s\" xlink:href=\"../img/$i.png\"/>\n";
		}else{
			$icon = "\$icon = Imagecreatefrompng(\"../img/$i.png\");\n";
			$icon .= "\$w = Imagesx(\$icon);\n";
			$icon .= "\$h = Imagesy(\$icon);\n";
			$icon .= "Imagecopy(\$image, \$icon,intval($x - \$w/2),intval($y - \$h/2),0,0,\$w,\$h);\n";
			$icon .= "Imagedestroy(\$icon);\n";
			return $icon;
		}
	}
}

#===================================================================
# Generate Jpeg icon text
function Panel($x,$y,$t,$s,$i,$z){

	global $fmt,$pos;

	$pnl = DevPanel($t,$i,$z);
	
	if($fmt == "json"){
		if($pos == "D"){
			$sc = 10;
		}elseif($pos == "p"){
			$sc = 20;
		}else{
			$sc = 80;
		}
		return "	{\"type\":\"panel\",\"height\":".(($z)?$z*$sc:$sc).",\"width\":".(($z)?2*$sc:$sc).",\"style\":\"$pnl\"\n";
	}elseif($fmt == "svg"){
		$stk = "";
		if($s > 1){
			$stk = DrawLabel($x+55,$y,$s,2,"blue");
		}
		if($pos == "D"){
			$sc = 12;
		}elseif($pos == "p"){
			$sc = 50;
		}else{
			$sc = 100;
		}
		return "<image x=\"".($x-$sc)."\" y=\"".($y-$sc/2)."\" width=\"".($sc*2)."\" height=\"$sc\" xlink:href=\"../$pnl\"/>$stk\n";
	}else{
		if($pos == "D"){
			$sc = 10;
		}elseif($pos == "p"){
			$sc = 5;
		}else{
			$sc = 2;
		}
		$icon = "\$icon = imagecreatefromjpeg(\"../$pnl\");\n";
		$icon .= "\$w = Imagesx(\$icon);\n";
		$icon .= "\$h = Imagesy(\$icon);\n";
		for ($c = 1; $c <= $s; $c++) {
			$icon .= "imagecopyresized(\$image, \$icon,intval($x-\$w/$sc), intval($y-$c*\$h/".($sc/2)."+($s*\$h/$sc) ),0,0,intval(\$w/".($sc/2)."),intval(\$h/".($sc/2)."+1),\$w,\$h );\n";
		}
		$icon .= "Imagedestroy(\$icon);\n";
		return $icon;
	}
}

#===================================================================
# Generate rectangular shape (and set $h to height for following labeloffset)
function IconRect($x,$y,$w,$h,$c){

	global $fmt;

	if($fmt == "json"){
		return "	{\"type\":\"rect\",\"height\":".(2*$h).",\"width\":".(2*$w).",\"style\":\"$c\"";
	}elseif($fmt == "svg"){
		return "<rect fill=\"$c\" stroke=\"black\" x=\"".($x-$w)."\" y=\"".($y-$h)."\" width=\"".(2*$w)."\" height=\"".(2*$h)."\" />\n";
	}else{
		$icon = "Imagefilledrectangle(\$image, ".($x-$w).", ".($y-$h).", ".($x+$w).", ".($y+$h).", \$$c);\n";
		$icon .= "Imagerectangle(\$image, ".($x-$w).", ".($y-$h).", ".($x+$w).", ".($y+$h).", \"\$black\");\n";
		$icon .= "\$h = $h;";
		return $icon;
	}
}

#===================================================================
# Generate circular shape  (and set $h to height for following labeloffset)
function IconCircle($x,$y,$rx,$ry,$c){

	global $fmt;

	if($fmt == "json"){
		return "	{\"type\":\"circle\",\"height\":$ry,\"width\":$rx,\"style\":\"$c\"";
	}elseif($fmt == "svg"){
		return "<ellipse  fill=\"$c\" stroke=\"black\" cx=\"$x\" cy=\"$y\" rx=\"$rx\" ry=\"$ry\"/>\n";
	}else{
		$icon = "Imagefilledellipse(\$image, $x, $y, ".(2*$rx).", ".(2*$ry).", \"\$$c\");\n";
		$icon .= "Imageellipse(\$image, $x, $y, ".(2*$rx).", ".(2*$ry).", \"\$black\");\n";
		$icon .= "\$h = $ry;";
		return $icon;
	}
}

#===================================================================
# Generate label text
function DrawLabel($x,$y,$t,$s,$c){

	global $fmt;

	if($t != ""){
		$fs = ($s == 1)?10:(4*$s);
		$lx = intval($x-strlen($t) * $fs/4);

		if($fmt == "json"){
		}elseif($fmt == "svg"){
			return "<text x=\"$lx\" y=\"".($y+$fs)."\" font-size=\"$fs\" fill=\"$c\">$t</text>\n";
		}else{
			return "ImageString(\$image, $s, $lx, $y, \"$t\", \$$c);\n";
		}
	}

}

//===================================================================
// Return link style based on forward bandwidth or utilisation
function LinkStyle($bw=0,$utl=0){

	global $lit;

	if($lit == 'l'){
		$w = 4;
		if($utl == 0){										# No traffic
			return array($w,'gainsboro');
		}elseif($utl < 2){
			return array($w,'cornflowerblue');
		}elseif($utl < 5){
			return array($w,'blue');
		}elseif($utl < 10){
			return array($w,'green');
		}elseif($utl < 25){
			return array($w,'limegreen');
		}elseif($utl < 50){
			return array($w,'yellow');
		}elseif($utl < 75){
			return array($w,'orange');
		}else{
			return array($w,'red');
		}
	}else{
		if($bw == 0){										# No bandwidth
			return array('1','lightgray');
		}elseif($bw == 11000000 or $bw == 54000000 or $bw == 300000000 or $bw == 450000000){	# Most likely Wlan
			return array('5','gainsboro');
		}elseif($bw < 10000000){								# Most likely serial links
			return array(intval($bw/1000000),'limegreen');
		}elseif($bw < 100000000){								# 10 Mbit Ethernet
			return array(intval($bw/10000000),'blue');
		}elseif($bw < 1000000000){								# 100 Mbit Ethernet
			return array(intval($bw/100000000),'orange');
		}elseif($bw < 10000000000){								# 1 Gbit Ethernet
			return array(intval($bw/1000000000),'red');
		}else{											# 10 Gbit Ethernet
			return array(intval($bw/10000000000),'purple');
		}
	}
}

#===================================================================
# Generate the map.
function Map(){

	global $debug,$link,$locsep,$vallbl,$sholbl,$sumlbl,$imas,$fmt,$lit,$fsz,$pos;
	global $xm,$ym,$xo,$yo,$rot,$cro,$bro,$len,$lsf,$mde,$in,$op,$st,$co,$lev,$loo,$loa,$loi,$ipi,$ifa;
	global $mapbg,$mapitems,$maplinks,$mapinfo,$imgmap,$reg,$cty,$bld,$flr,$dev,$nod,$nlnk,$jnod,$cud;

	$rlnk = array();
	$clnk = array();
	$blnk = array();
	$dlnk = array();

	$acol = '';
	$join = '';
	if($pos == 'a'){
		$acol = ',status';
		$join .= 'LEFT JOIN monitoring USING (device) ';
	}
	if( in_array('vlanid', $in) or in_array('vlanname', $in) ) $join .= 'LEFT JOIN vlans USING (device) ';
	if( in_array('mac', $in) or in_array('nodip', $in) or in_array('name', $in)  or in_array('oui', $in) ) $join .= 'LEFT JOIN nodes USING (device) ';
	if( in_array('ifip', $in) or in_array('vrfname', $in) )	$join .= 'LEFT JOIN networks USING (device) ';
	if( in_array('neighbor', $in) )	$join .= 'LEFT JOIN links USING (device) ';

	$query .= GenQuery('devices','s',"distinct device,devip,type,location,contact,devmode,icon,cpu,temp,devopts,size,stack$acol,snmpversion",'','',$in,$op,$st,$co,$join);# Postgres requires ordercolumn (snmpversion)!
	if($lev < 5){
		if( strpos($query,'WHERE') ){
			$query .= ' AND snmpversion != 0';
		}else{
			$query .= ' WHERE snmpversion != 0';
		}
	}
	$query .= ' order by snmpversion desc';

	$res	= DbQuery($query,$link);
	if($res){
		while( ($d = DbFetchRow($res)) ){
			$l = explode($locsep, $d[3]);
			$reg[$l[0]]['ndv']++;
			$cty[$l[0]][$l[1]]['ndv']++;
			$dev[$d[0]]['reg'] = $l[0];
			if($d[6] and $ipi){								# Get IP info for interfaces on snmpdevs
				$nquery	= GenQuery('networks','s','ifname,ifip,ifip6,vrfname','','',array('device'),array('='),array($d[0]) );
				$nres	= DbQuery($nquery,$link);
				if($nres){
					while( ($n = DbFetchRow($nres)) ){
						if($n[1]){
							$dev[$d[0]]['ifip'][$n[0]] .= " ". long2ip($n[1]).(($n[3])?" ($n[3])":"");
						}else{
							$dev[$d[0]]['ifip'][$n[0]] .= " ". DbIPv6($n[2]).(($n[3])?" ($n[3])":"");
						}
					}
				}else{
					echo DbError($nlink);
				}
				DbFreeResult($nres);
			}
			if($d[6] and ($ifa or $lit == 'l') ){						# Get IF alias TODO use iftype to determine links?
				$nquery	= GenQuery('interfaces','s','ifname,ifidx,iftype,alias,dinoct,doutoct','','',array('device'),array('='),array($d[0]) );
				$nres	= DbQuery($nquery,$link);
				if($nres){
					while( ($n = DbFetchRow($nres)) ){
						$dev[$d[0]]['ifty'][$n[0]] = $n[1];
						$dev[$d[0]]['ifix'][$n[0]] = $n[2];
						$dev[$d[0]]['ifal'][$n[0]] = $n[3];
						$dev[$d[0]]['ifin'][$n[0]] = $n[4];
						$dev[$d[0]]['ifout'][$n[0]] = $n[5];
					}
				}else{
					echo DbError($nlink);
				}
				DbFreeResult($nres);
			}
			if($lev > 1){
				$dev[$d[0]]['cty'] = $l[1];
			}
			if($lev > 2){
				$bld[$l[0]][$l[1]][$l[2]]['ndv']++;
				$dev[$d[0]]['bld'] = $l[2];
			}
			if($lev > 3){
				if ($mde == "r") {
					$flr[$l[0]][$l[1]][$l[2]][$d[0]]['ndv']++;
				}else{
					$flr[$l[0]][$l[1]][$l[2]][$l[3]][] = $d[0];
				}
				$dev[$d[0]]['ip']  = long2ip($d[1]);
				$dev[$d[0]]['rom'] = $l[4];
				$dev[$d[0]]['rak'] = ($l[5])?$l[5]:"";
				$dev[$d[0]]['typ'] = $d[2];
				$dev[$d[0]]['con'] = $d[4];
				$dev[$d[0]]['mod'] = Devmode($d[5]);
				$dev[$d[0]]['ico'] = $d[6];
				if( substr($d[9],1,1) == "C" ){
					$dev[$d[0]]['cpu'] = $d[7];
				}else{
					$dev[$d[0]]['cpu'] = "-";
				}
				$dev[$d[0]]['tmp'] = $d[8];
				if($d[8] != 0){
					$dev[$d[0]]['tlb'] = ($_SESSION['far'])?intval($dev[$d[0]]['tmp']*1.8+32)."F":$dev[$d[0]]['tmp']."C";
				}else{
					$dev[$d[0]]['tlb'] = "-";
				}
				$dev[$d[0]]['stk'] = ($d[11] > 1)?$d[11]:1;
				$dev[$d[0]]['siz'] = $d[10] * $dev[$d[0]]['stk'];
				if($pos == 'a') $dev[$d[0]]['sta'] = $d[12];
			}
		}
		DbFreeResult($res);
	}else{
		echo DbError($link);
	}

# Precalculate Links
	foreach(array_keys($dev) as $d){								# Devs sorted by snmpversion creates links with stats first!
		$lquery	= GenQuery('links','s','*','','',array('device'),array('='),array($d));
		$lres	= DbQuery($lquery,$link);
		while( ($k = DbFetchRow($lres)) ){
			if( isset($dev[$k[3]]['reg']) ){						# Only use, if we have complete devs
				$rlquery = GenQuery('links','s','*','','',array('device','neighbor'),array('=','='),array($k[3],$k[1]),array('AND'));
				$rlres	 = DbQuery($rlquery,$link);
				$rlnum   = DbNumRows($rlres);
				if($debug){echo "<div class=\"textpad good\">LINK:$k[1] to $k[3] with BW of $k[5]</div>\n";}
				if( array_key_exists("$k[3];;$k[1]",$dlnk) ){
					$dlnk["$k[3];;$k[1]"]['rbw'] += $k[5];
					$dlnk["$k[3];;$k[1]"]['rtr'] += $dev[$k[3]]['ifin'][$k[4]];
					$dlnk["$k[3];;$k[1]"]['rif'][] = "$k[1];;$k[2]";
					$dlnk["$k[3];;$k[1]"]['rty']["$k[6]:".date('j.M',$k[10])]++;
				}elseif( isset($dev[$k[3]]['ico']) ){
					if(!$rlnum){
						if($debug){echo "<div class=\"textpad alrm\">LNK: Fixing missing link from $k[3] to $k[1]</div>\n";}
						$dlnk["$k[1];;$k[3]"]['rbw'] += $k[5];
						$dlnk["$k[1];;$k[3]"]['rtr'] += $dev[$k[1]]['ifin'][$k[2]];
						$dlnk["$k[1];;$k[3]"]['rif'][] = "$k[3];;$k[4]";
						$dlnk["$k[1];;$k[3]"]['fty']["$k[6]:".date('j.M',$k[10])]++;
					}
					$dlnk["$k[1];;$k[3]"]['fbw'] += $k[5];
					$dlnk["$k[1];;$k[3]"]['ftr'] += $dev[$k[1]]['ifout'][$k[2]];
					$dlnk["$k[1];;$k[3]"]['fif'][] = "$k[1];;$k[2]";
					$dlnk["$k[1];;$k[3]"]['fty']["$k[6]:".date('j.M',$k[10])]++;
				}
				$ra = $dev[$k[1]]['reg'];
				$rb = $dev[$k[3]]['reg'];
				$ca = $dev[$k[1]]['cty'];
				$cb = $dev[$k[3]]['cty'];
				$ba = $dev[$k[1]]['bld'];
				$bb = $dev[$k[3]]['bld'];

				if($mde != "f" and $ra != $rb ){
					$reg[$ra]['nlk']++;
					$reg[$ra]['alk'][$rb]++;					# Needed for arranging
					if( array_key_exists("$rb;;$ra",$rlnk) ){			# Reverse link exists?
						$rlnk["$rb;;$ra"]['rbw'] += $k[5];
						$rlnk["$rb;;$ra"]['rtr'] += $dev[$k[1]]['ifin'][$k[2]];
						$rlnk["$rb;;$ra"]['rif'][] = "$k[1];;$k[2]";
						$rlnk["$rb;;$ra"]['rty']["$k[6]:".date('j.M',$k[10])]++;
					}else{
						if(!$rlnum){
							$reg[$rb]['nlk']++;
							$reg[$rb]['alk'][$rb]++;
							$rlnk["$ra;;$rb"]['rbw'] += $k[5];
							$rlnk["$ra;;$rb"]['rtr'] += $dev[$k[1]]['ifin'][$k[2]];
							$rlnk["$ra;;$rb"]['rif'][] = "$k[3];;$k[4]";
							$rlnk["$ra;;$rb"]['rty']["$k[6]:".date('j.M',$k[10])]++;
						}
						$rlnk["$ra;;$rb"]['fbw']  += $k[5];
						$rlnk["$ra;;$rb"]['ftr']  += $dev[$k[1]]['ifout'][$k[2]];
						$rlnk["$ra;;$rb"]['fif'][] = "$k[1];;$k[2]";
						$rlnk["$ra;;$rb"]['fty']["$k[6]:".date('j.M',$k[10])]++;
					}
				}
				if($mde != "f" and $lev > 1){
					if("$ra;;$ca" != "$rb;;$cb"){
						$cty[$ra][$ca]['nlk']++;
						if($ra == $rb){$cty[$ra][$ca]['alk'][$cb]++;}#TODO test whether this improves arranging!
						if( array_key_exists("$rb;;$cb;;$ra;;$ca",$clnk) ){
							$clnk["$rb;;$cb;;$ra;;$ca"]['rbw']  += $k[5];
							$clnk["$rb;;$cb;;$ra;;$ca"]['rtr']  += $dev[$k[1]]['ifin'][$k[2]];
							$clnk["$rb;;$cb;;$ra;;$ca"]['rif'][] = "$k[1];;$k[2]";
							$clnk["$rb;;$cb;;$ra;;$ca"]['rty']["$k[6]:".date('j.M',$k[10])]++;
						}else{
							if(!$rlnum){
								$cty[$rb][$cb]['nlk']++;
								if($ra == $rb){$cty[$rb][$cb]['alk'][$ca]++;}
								$clnk["$ra;;$ca;;$rb;;$cb"]['rbw']  += $k[5];
								$clnk["$ra;;$ca;;$rb;;$cb"]['rtr']  += $dev[$k[1]]['ifin'][$k[2]];
								$clnk["$ra;;$ca;;$rb;;$cb"]['rif'][] = "$k[3];;$k[4]";
								$clnk["$ra;;$ca;;$rb;;$cb"]['rty']["$k[6]:".date('j.M',$k[10])]++;
							}
							$clnk["$ra;;$ca;;$rb;;$cb"]['fbw']  += $k[5];
							$clnk["$ra;;$ca;;$rb;;$cb"]['ftr']  += $dev[$k[1]]['ifout'][$k[2]];
							$clnk["$ra;;$ca;;$rb;;$cb"]['fif'][] = "$k[1];;$k[2]";
							$clnk["$ra;;$ca;;$rb;;$cb"]['fty']["$k[6]:".date('j.M',$k[10])]++;
						}
					}
				}
				if($mde != "f" and $lev > 2){
					if("$ra;;$ca;;$ba" != "$rb;;$cb;;$bb"){
						$bld[$ra][$ca][$ba]['nlk']++;
						if("$ra;;$ca" == "$rb;;$cb"){$bld[$ra][$ca][$ba]['alk'][$bb]++;}
						if( array_key_exists("$rb;;$cb;;$bb;;$ra;;$ca;;$ba",$blnk) ){
							$blnk["$rb;;$cb;;$bb;;$ra;;$ca;;$ba"]['rbw']  += $k[5];
							$blnk["$rb;;$cb;;$bb;;$ra;;$ca;;$ba"]['rtr']  += $dev[$k[1]]['ifin'][$k[2]];
							$blnk["$rb;;$cb;;$bb;;$ra;;$ca;;$ba"]['rif'][] = "$k[1];;$k[2]";
							$blnk["$rb;;$cb;;$bb;;$ra;;$ca;;$ba"]['rty']["$k[6]:".date('j.M',$k[10])]++;
						}else{
							if(!$rlnum){
								$bld[$rb][$cb][$bb]['nlk']++;
								if("$ra;;$ca" == "$rb;;$cb"){$bld[$rb][$cb][$bb]['alk'][$ba]++;}
								$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['rbw']  += $k[5];
								$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['rtr']  += $dev[$k[1]]['ifin'][$k[2]];
								$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['rif'][] = "$k[3];;$k[4]";
								$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['rty']["$k[6]:".date('j.M',$k[10])]++;
							}
							$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['fbw']  += $k[5];
							$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['ftr']  += $dev[$k[1]]['ifout'][$k[2]];
							$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['fif'][] = "$k[1];;$k[2]";
							$blnk["$ra;;$ca;;$ba;;$rb;;$cb;;$bb"]['fty']["$k[6]:".date('j.M',$k[10])]++;
						}
					}
				}
				if($lev > 3){
					$dev[$k[1]]['nlk']++;						# Count devlinks for flatmode
					$dev[$k[1]]['alk'][$k[3]]++;					# Needed for arranging
					#if ($mde == "r") {# TODO find arrange method for building rings (only links within bld matter!)
					#	$flr[$l[0]][$l[1]][$l[2]][$k[1]]['alk'][$k[3]]++;
					#}
					if(!$rlnum){
						$dev[$k[3]]['nlk']++;
						$dev[$k[3]]['alk'][$k[1]]++;
					}
				}
			}
		}
		DbFreeResult($lres);
	}
	$rk = array_keys($reg);
	$nr = count($rk);


# Draw Layout
	$cud = 0;
	if($mde == "f"){
		$fstnod = 1;
		$nd = count( array_keys($dev) );
		foreach(Arrange($dev) as $dv){
			$jdev[$dv]  = $cud;
			list($dev[$dv]['x'],$dev[$dv]['y']) = CircleCoords(intval($xm/2 + $xo),intval($ym/2 - $yo),$cud,$nd,$dev[$dv]['nlk'],$len,$rot);
			$mapitems .= DrawItem($dev[$dv]['x'],$dev[$dv]['y'],'0',$dv,'d');
			if( $lev == 6){
				DrawNodes($dv);
			}
			$imgmap .= "<area href=\"Devices-Status.php?dev=".urlencode($dv)."\" coords=\"".($dev[$dv]['x']-$imas) .",". ($dev[$dv]['y']-$imas) .",". ($dev[$dv]['x']+$imas) .",". ($dev[$dv]['y']+$imas)."\" shape=\"rect\" title=\"$dv ".$dev[$dv]['ip']." CPU:".$dev[$dv]['cpu']."% Temp:".$dev[$dv]['tlb']."\">\n";
			if ($loi){
				$mapinfo .= DrawLabel($dev[$dv]['x'],$dev[$dv]['y']-40,Safelabel($dev[$dv]['cty']." ".$dev[$dv]['bld']),1,"cornflowerblue");
			}elseif ($debug){
				$mapinfo .= DrawLabel($dev[$dv]['x'],$dev[$dv]['y']-40,"Pos$cud",1,"cornflowerblue");
			}
			$cud++;
		}
	}else{
		if ($mde == "g") {									# Prepare geographic stuff
			if( count($rk) == 1 ){
				$ck = array_keys($cty[$rk[0]]);
				if( count($ck) == 1 ){
					$mapbg = TopoMap($rk[0],$ck[0]);
				}else{
					$mapbg = TopoMap($rk[0]);
				}
			}else{
				$mapbg = TopoMap();
			}
			$bg = Imagecreatefromjpeg("../topo/$mapbg");
			$xm = Imagesx($bg);
			$ym = Imagesy($bg);
			Imagedestroy($bg);
		}

		$cur = 0;
		$toc = 0;
		$tob = 0;
		foreach(Arrange($reg) as $r){
			if ($mde == "g") {
				list($reg[$r]['x'],$reg[$r]['y'],$reg[$r]['cmt']) = DbCoords($r);
			}
			if(!$reg[$r]['x']){
				list($reg[$r]['x'],$reg[$r]['y']) = CircleCoords(intval($xm/2 + $xo),intval($ym/2 - $yo),$cur,$nr,$reg[$r]['nlk'],$len,$rot);
			}
			if( $lev == 1){
				$jreg[$r] = $cur;
				$mapitems .= DrawItem($reg[$r]['x'],$reg[$r]['y'],$reg[$r]['ndv'],$r,1);
				$imgmap   .= "<area href=\"?lev=2&mde=$mde&fmt=png&loo=$loo&loa=$loa&st[]=". urlencode( TopoLoc($r) ) ."\" coords=\"".($reg[$r]['x']-$imas) .",". ($reg[$r]['y']-$imas) .",". ($reg[$r]['x']+$imas) .",". ($reg[$r]['y']+$imas)."\" shape=\"rect\" title=\"$sholbl\">\n";
			}else{
				if ($loi){
					if(count($cty[$r]) > 1){
						$mapinfo .= DrawItem($reg[$r]['x'],$reg[$r]['y'],'0',$r." ".$reg[$r]['cmt'],'ri');
					}else{
						$mapinfo .= DrawLabel($reg[$r]['x'],$reg[$r]['y']-42,Safelabel($r),1,"cornflowerblue");
					}
				}
				$cuc = 0;
				$nc  = count( array_keys($cty[$r]) );
				foreach(Arrange($cty[$r]) as $c){
					if ($mde == "g") {
						list($cty[$r][$c]['x'],$cty[$r][$c]['y'],$cty[$r][$c]['cmt']) = DbCoords($r,$c);
					}
					if(!$cty[$r][$c]['x']){
						list($cty[$r][$c]['x'],$cty[$r][$c]['y']) = CircleCoords($reg[$r]['x'],$reg[$r]['y'],$cuc,$nc,$cty[$r][$c]['nlk'],$len*10/$lsf,$cro);
					}
					if( $lev == 2){
						$jcty["$r;;$c"] = $toc;
						$mapitems .= DrawItem($cty[$r][$c]['x'],$cty[$r][$c]['y'],$cty[$r][$c]['ndv'],$c,2);
						$imgmap   .= "<area href=\"?lev=3&mde=$mde&fmt=png&loo=$loo&loa=$loa&st[]=". urlencode( TopoLoc($r,$c) ) ."\" coords=\"".($cty[$r][$c]['x']-$imas) .",". ($cty[$r][$c]['y']-$imas) .",". ($cty[$r][$c]['x']+$imas) .",". ($cty[$r][$c]['y']+$imas)."\" shape=\"rect\" title=\"$sholbl\">\n";
					}else{
						if ($loi){
							if(count($bld[$r][$c]) > 1){
								$mapinfo .= DrawItem($cty[$r][$c]['x'],$cty[$r][$c]['y'],'0',$c." ".$cty[$r][$c]['cmt'],'ci');
							}else{
								$mapinfo .= DrawLabel($cty[$r][$c]['x'],$cty[$r][$c]['y']-30,Safelabel($c),1,"cornflowerblue");
							}
						}
						$cub = 0;
						$nb  = count( array_keys($bld[$r][$c]) );
						foreach(Arrange($bld[$r][$c]) as $b){
							if ($mde == "g") {
								list($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y'],$bld[$r][$c][$b]['cmt']) = DbCoords($r,$c,$b);
							}
							if(!$bld[$r][$c][$b]['x']){
								list($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y']) = CircleCoords($cty[$r][$c]['x'],$cty[$r][$c]['y'],$cub,$nb,$bld[$r][$c][$b]['nlk']*(($mde == "b")?($cb % 2)+0.3:1),$len/pow($lsf/10,2),$bro);
							}
							if($lev == 3){
								$jbld["$r;;$c;;$b"] = $tob;
								$mapitems .= DrawItem($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y'],$bld[$r][$c][$b]['ndv'],$b,3);
								$imgmap   .= "<area href=\"?lev=4&mde=$mde&fmt=png&loo=$loo&loa=$loa&st[]=". urlencode( TopoLoc($r,$c,$b) ) ."\" coords=\"".($bld[$r][$c][$b]['x']-$imas) .",". ($bld[$r][$c][$b]['y']-$imas) .",". ($bld[$r][$c][$b]['x']+$imas) .",". ($bld[$r][$c][$b]['y']+$imas)."\" shape=\"rect\" title=\"$sholbl\">\n";
							}elseif ($mde == "b" or $mde == "g"){
								DrawBuilding($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y'],$r,$c,$b);
							}else{
								if ($loi){
									if(count($flr[$r][$c][$b]) > 1){
										$mapinfo .= DrawItem($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y'],'0',$b." ".$bld[$r][$c][$b]['cmt'],'bi');
									}else{
										$mapinfo .= DrawLabel($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y']-38,Safelabel($b),1,"cornflowerblue");
									}
								}
								$cd = 0;
								$nd = count( array_keys($flr[$r][$c][$b]) );
								foreach(Arrange($flr[$r][$c][$b]) as $d){
									$jdev[$d] = $cud;
									list($dev[$d]['x'],$dev[$d]['y']) = CircleCoords($bld[$r][$c][$b]['x'],$bld[$r][$c][$b]['y'],$cd,$nd,$dev[$d]['nlk'],$fsz,0,0);
									$mapitems .= DrawItem($dev[$d]['x'],$dev[$d]['y'],'0',$d,'d');
									$imgmap   .= "<area href=\"Devices-Status.php?dev=".urlencode($d)."\" coords=\"".($dev[$d]['x']-$imas) .",". ($dev[$d]['y']-$imas) .",". ($dev[$d]['x']+$imas) .",". ($dev[$d]['y']+$imas)."\" shape=\"rect\" title=\"$dv ".$dev[$d]['ip']." CPU:".$dev[$d]['cpu']."%  T:".$dev[$d]['tmp']."C\">\n";
									if( $lev == 6){DrawNodes($d);}
									$cd++;
									$cud++;
								}
							}
							$cub++;
							$tob++;
						}
					}
					$cuc++;
					$toc++;
				}
			}
			$cur++;
		}
	}

# Draw Links
	if($lev == 1){
		$rlkeys = array_keys($rlnk);
		foreach($rlkeys as $li){
			$l = explode(';;', $li);
			if($fmt == "json"){
				$ls = intval(sqrt($rlnk[$li]['fbw']/100000000/$lsf)+1);
				$maplinks .= "    {\"source\":".$jreg[$l[0]].",\"target\":".$jreg[$l[1]].",\"value\":$ls},\n";
			}else{
				DrawLink($reg[$l[0]]['x'],
					$reg[$l[0]]['y'],
					$reg[$l[1]]['x'],
					$reg[$l[1]]['y'],
					$rlnk[$li]);
			}
		}
	}elseif($lev == 2){
		foreach(array_keys($clnk) as $li){
			$l = explode(';;', $li);
			if($fmt == "json"){
				$ls = intval(sqrt($clnk[$li]['fbw']/100000000/$lsf)+1);
				$maplinks .= "    {\"source\":".$jcty["$l[0];;$l[1]"].",\"target\":".$jcty["$l[2];;$l[3]"].",\"value\":$ls},\n";
			}else{
				DrawLink($cty[$l[0]][$l[1]]['x'],
					$cty[$l[0]][$l[1]]['y'],
					$cty[$l[2]][$l[3]]['x'],
					$cty[$l[2]][$l[3]]['y'],
					$clnk[$li]);
			}
		}
	}elseif($lev == 3){
		foreach(array_keys($blnk) as $li){
			$l = explode(';;', $li);
			if($fmt == "json"){
				$ls = intval(sqrt($blnk[$li]['fbw']/100000000/$lsf)+1);
				$maplinks .= "    {\"source\":".$jbld["$l[0];;$l[1];;$l[2]"].",\"target\":".$jbld["$l[3];;$l[4];;$l[5]"].",\"value\":$ls},\n";
			}else{
				DrawLink($bld[$l[0]][$l[1]][$l[2]]['x'],
					$bld[$l[0]][$l[1]][$l[2]]['y'],
					$bld[$l[3]][$l[4]][$l[5]]['x'],
					$bld[$l[3]][$l[4]][$l[5]]['y'],
					$blnk[$li]);
			}
		}
	}elseif($lev > 3){
		foreach(array_keys($dlnk) as $li){
			$l = explode(';;', $li);
			if($fmt == "json"){
				$ls = intval(sqrt($dlnk[$li]['fbw']/100000000/$lsf)+1);
				$ls = ($ls)?$ls:1;
				$maplinks .= "    {\"source\":".$jdev[$l[0]].",\"target\":".$jdev[$l[1]].",\"value\":$ls},\n";
			}else{
				DrawLink($dev[$l[0]]['x'],
					$dev[$l[0]]['y'],
					$dev[$l[1]]['x'],
					$dev[$l[1]]['y'],
					$dlnk[$li]);
			}
		}
		if($lev == 6){
			foreach(array_keys($nlnk) as $li){
				$l = explode(';;', $li);
				if($fmt == "json"){
					$ls = intval(sqrt($nlnk[$li]['fbw']/100000000/$lsf)+1);
					$ls = ($ls)?$ls:1;
					$maplinks .= "    {\"source\":".$jdev[$l[0]].",\"target\":".$jnod["$l[0];;$l[1]"].",\"value\":$ls},\n";
				}else{
					DrawLink($dev[$l[0]]['x'],
						$dev[$l[0]]['y'],
						$nod[$l[1]]['x'],
						$nod[$l[1]]['y'],
						$nlnk[$li]);
				}
			}
		}
	}
}

#===================================================================
# Calculate circular coordinates, dynlen sets radius to 0 on single points (except nodes)
function CircleCoords($x,$y,$curp,$nump,$nl,$l,$r,$dynlen=1){

	global $pwt;

	if($nump == 1 and $dynlen){
		$l = 0;
	}
	$mywt  = pow( ($nl)?$nl:1,$pwt/50);
	$phi   = $r * 0.0174533 + 2 * $curp * M_PI / $nump;
	return array( intval($x + $l * cos($phi) * 1.3 / $mywt), intval($y + $l * sin($phi) / $mywt) );
}

#===================================================================
# Lookup coordinates and return if map matches
function DbCoords($r='', $c='', $b=''){

	global $mapbg,$link;

	$query	= GenQuery('locations','s','x,y,locdesc','','',array('region','city','building'),array('=','=','='),array($r,$c,$b),array('AND','AND'));
	$res	= DbQuery($query,$link);
	$nloc	= DbNumRows($res);
	if(!$c){$r="";}elseif(!$b){$c="";}								# Clear those for Topomap()
	if ($nloc == 1 and $mapbg == TopoMap($r,$c) ) {
		return DbFetchRow($res);
	}
}

#===================================================================
# Arrange locations according to links
function Arrange($circle){

	global $debug;

	$nodcircle  = array();
	$sortednod  = array();
	$hubweight  = array();
	$nbrnumber  = array();

	if($debug){echo "<div class=\"textpad code txta\"><h3>ARRANGE</h3>\n";}

	foreach(array_keys($circle) as $node){
		if( is_array($circle[$node]['alk']) ){
			$nbr = array_keys($circle[$node]['alk']);
			if (count($nbr) == 1 ){								# 1 neighbor
				$nodcircle[$node] = $nbr[0];
				if($debug){echo "LEAF:$node -> $nbr[0]<br>";}
			}else{										# Several neighbors
				if($debug){echo "<p>HUB :$node<br>";}
				$nodcircle[$node] = $node;
				foreach($nbr as $n){
					if( is_array($circle[$n]['alk']) ){
						$hubweight[$node] += (count(array_keys($circle[$n]['alk'])) > 1)?2:1;
					}
					if($debug){echo "NBR :$n<br>";}
				}
				if($debug){echo "WGHT:$hubweight[$node]<br>";}
			}
		}else{
			$nodcircle[$node] = 0;
			if($debug){echo "UNL :$node<br>";}
		}
	}

	if($debug){echo "Align Hubs:\n";}
	arsort($hubweight);
 	foreach($hubweight as $curh => $cw){
		if($cw < 4){
			if($debug){echo "HUB :$curh pos$cw<br>";}
			foreach($hubweight as $nexth => $nw){
				if( in_array($curh, array_keys($circle[$nexth]['alk'])) and $cw < $nw){
					if($debug){echo "HLNK:$curh $nexth = $nw<br>";}
					if($nodcircle[$curh] == $curh){					# Only align hub, if not done before
						$nodcircle[$curh] = $nexth."0".$curh;			# Hub will come in before the one it's aligned to
						if($debug){echo "HALI:$nexth to $curh<br>";}
					}else{
						if($debug){echo "HDON:$nexth is $nodcircle[$nexth]<br>";}
					}
				}
			}
		}
	}

	if($debug){echo "Arrange:\n";}

	asort($nodcircle);
	foreach ($nodcircle as $node => $nbr){
		if(array_key_exists($node,$hubweight) ){
			$sortednod[$node] = $nbr . "2";							# Hubs weight 2
			if($debug){echo "<p>HUB :$nbr<br>";}
		}else{
			$nbrnumber[$nbr]++;
			if($nbrnumber[$nbr]%2 ){							# Distribute LEAFs around HUBs
				$sortednod[$node] = $nodcircle[$nbr] . "1$node";
				if($debug){echo "LEAF:$node = $nbr BELOW<br>";}
			}else{
				$sortednod[$node] = $nodcircle[$nbr] . "3$node";
				if($debug){echo "LEAF:$node = $nbr ABOVE<br>";}
			}
		}
		if($debug){echo "SORT:$sortednod[$node]<br>";}
	}

	asort($sortednod);
	$sortedkeys = array_keys($sortednod);
	$csiz = count($sortedkeys);
	$iter = 0;

	if($debug){echo "<h3>Reposition nodes with 2 links crossing ($csiz total)</h3>\n";}
	do{
		$kpos = 0;
		$movednods = array();
		foreach($sortedkeys as $k){
			if($debug){echo "REPO$iter: $k ";}
			if( is_array($circle[$k]['alk']) ){						# Any links?
				$nbr  = array_keys($circle[$k]['alk']);
				$nnbr = count($nbr);
				if($nnbr == 2 and !in_array($k,$movednods) ){				# We got 2 links?
					$npos1 = array_search($nbr[0],$sortedkeys);
					$npos2 = array_search($nbr[1],$sortedkeys);
					$ndst1 = Dist($kpos,$npos1,$csiz);
					$ndst2 = Dist($kpos,$npos2,$csiz);
					$ktonb = $ndst1+$ndst2;
					$nbdst = Dist($npos1,$npos2,$csiz);
					if($debug){echo "pos$kpos connects to $nbr[0] on pos$npos1 len$ndst1 and $nbr[1] on pos$npos2 len$ndst2 (dist$ktonb vs nbrdist$nbdst) ";}# TODO add logic to detect crossing links or align and group nodes with 2 neighbors?
					if( $ktonb > $nbdst + 1){					# add 1 to avoid flapping
						$mpos = ($npos1 < $npos2)?$npos1:$npos2;
						$nb1 = count(array_keys($circle[$nbr[0]]['alk']));
						$nb2 = count(array_keys($circle[$nbr[1]]['alk']));
						$mpos = ($nb1 < $nb2)?$npos1:$npos2;
						if($debug){echo "$nbr[0] has $nb1 links and $nbr[1] has $nb2, <span class=\"warn\">moving to $mpos</span>\n";}
						array_splice($sortedkeys,$kpos,1);			# remove it
						#if($nbdst > 0){
							array_splice($sortedkeys,$mpos,0,$k);		# and insert after nbr with less links
						#}else{
						#	array_splice($sortedkeys,$mpos,0,$k);		# or before if 0 crossing
						#}
						if($debug){print_r($sortedkeys);}
						$movednods[] = $k;
						break 1;
					}else{
						if($debug){echo "stays\n";}
					}
				}else{
					if($debug){echo "has $nnbr neighbor".(($nnbr == 1)?'':'s')."\n";}
				}
			}else{
				if($debug){echo "no links\n";}
			}
			$kpos++;
		}
		if($kpos == $csiz){									# Went through whole array lets end
			if($debug){echo "REPO:iter$iter reached pos$kpos, done!\n";}
			$iter = $csiz;
		}
		$iter++;
	}while($iter < $csiz);
	if($debug){echo "</div>";}

	return $sortedkeys;
	}

#===================================================================
# Return shorter distance between 2 nodes
function Dist($a, $b,$s){

	$d1 = abs($a - $b);
	$d2 = $s - $d1;

	return ($d1 < $d2)?$d1:$d2;
}

#===================================================================
# Sort by room and #device links within floor
function Roomsort($a, $b){

	global $dev,$debug;

        if ($dev[$a]['rom'] == $dev[$b]['rom']){
		#if($debug){echo $dev[$a]['nlk']." == ".$dev[$b]['nlk']." linksort $a,$b<br>";}
		if ($dev[$a]['nlk'] == $dev[$b]['nlk']) return 0;
		return ($dev[$a]['nlk'] > $dev[$b]['nlk']) ? -1 : 1;
	}
        return ($dev[$a]['rom'] < $dev[$b]['rom']) ? -1 : 1;
}

?>
