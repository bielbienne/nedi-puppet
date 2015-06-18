<?PHP
//===================================================================
// Miscellaneous functions
//===================================================================

//===================================================================
// Read configuration
function ReadConf($group=''){

	global $locsep,$lang,$redbuild,$modgroup,$disc,$fahrtmp;
	global $comms,$mod,$backend,$dbhost,$dbname,$dbuser,$dbpass,$retire;
	global $timeout,$ignoredvlans,$useivl,$cpua,$mema,$tmpa,$trfa,$trfw;
	global $mapip,$poew,$pause,$latw,$rrdcmd,$nedipath,$rrdstep;
	global $cacticli,$cactiuser,$cactipass,$cactidb,$cactihost,$cactiurl;
	global $guiauth,$radsrv, $ldapsrv, $ldapmap;

	if (file_exists("$nedipath/nedi.conf")) {
		$conf = file("$nedipath/nedi.conf");
	}elseif (file_exists("/etc/nedi.conf")) {
		$conf = file("/etc/nedi.conf");
	}elseif (file_exists("../nedi.conf")) {
		$conf = file("../nedi.conf");
	}else{
		echo "Can't find nedi.conf!";
		die;
	}

	$mapip  = array();
	$locsep	= " ";
	foreach ($conf as $cl) {
		if ( !preg_match("/^#|^$/",$cl) ){
			$v =  preg_split('/[\t\s]+/', rtrim($cl,"\n\r\0") );

			if ($v[0] == "module"){
				$v[4] = isset($v[4]) ? $v[4] : "usr";
				$modgroup["$v[1]-$v[2]"] = $v[4];
				if( strpos($group,$v[4]) !== false){
					$mod[$v[1]][$v[2]] = $v[3];
				}
			}
			if ($v[0] == "comm"){
				$comms[$v[1]]['aprot'] = (isset($v[3]))?$v[2]:"";
				$comms[$v[1]]['apass'] = (isset($v[3]))?$v[3]:"";
				$comms[$v[1]]['pprot'] = (isset($v[5]))?$v[4]:"";
				$comms[$v[1]]['ppass'] = (isset($v[5]))?$v[5]:"";
			}
			elseif ($v[0] == "mapna")	{$mapip[$v[1]]['na'] = $v[2];}
			elseif ($v[0] == "backend")	{$backend = $v[1];}
			elseif ($v[0] == "dbhost")	{$dbhost  = $v[1];}
			elseif ($v[0] == "dbname")	{$dbname  = isset($_SESSION['snap'])?$_SESSION['snap']:$v[1];}
			elseif ($v[0] == "dbuser")	{$dbuser  = $v[1];}
			elseif ($v[0] == "dbpass")	{$dbpass  = $v[1];}

			elseif ($v[0] == "cpu-alert")	{$cpua = $v[1];}
			elseif ($v[0] == "mem-alert")	{$mema = $v[1];}
			elseif ($v[0] == "temp-alert")	{$tmpa = $v[1];}
			elseif ($v[0] == "poe-warn")	{$poew = $v[1];}
			elseif ($v[0] == "traf-alert")	{$trfa = $v[1];}
			elseif ($v[0] == "traf-warn")	{$trfw = $v[1];}

			elseif ($v[0] == "latency-warn"){$latw         = $v[1];}
			elseif ($v[0] == "pause")	{$pause        = $v[1];}
			elseif ($v[0] == "ignoredvlans"){$ignoredvlans = $v[1];}
			elseif ($v[0] == "useivl")	{$useivl       = $v[1];}
			elseif ($v[0] == "retire")	{$retire       = $v[1];}
			elseif ($v[0] == "timeout")	{$timeout      = $v[1];}

			elseif ($v[0] == "rrdcmd")	{$rrdcmd   = $v[1];}
			elseif ($v[0] == "nedipath")	{$nedipath = $v[1];}
			elseif ($v[0] == "rrdstep")	{$rrdstep  = $v[1];}

			elseif ($v[0] == "locsep")	{$locsep   = $v[1];}
			elseif ($v[0] == "guiauth")	{$guiauth  = $v[1];}
			elseif ($v[0] == "radserver")	{$radsrv[] = array($v[1],$v[2],$v[3],$v[4],$v[5]);}
			elseif ($v[0] == "ldapsrv")	{$ldapsrv  = array($v[1],$v[2],$v[3],$v[4],$v[5],$v[6]);}
			elseif ($v[0] == "ldapmap")	{$ldapmap  = array($v[1],$v[2],$v[3],$v[4],$v[5],$v[6],$v[7],$v[8]);}
			elseif ($v[0] == "redbuild")	{array_shift($v);$redbuild = implode(" ",$v);}
			elseif ($v[0] == "disclaimer")	{array_shift($v);$disc = implode(" ",$v);}

			elseif ($v[0] == "cacticli")	{array_shift($v);$cacticli = implode(" ",$v);}

			elseif ($v[0] == "cactihost")	{$cactihost = $v[1];}
			elseif ($v[0] == "cactidb")	{$cactidb   = $v[1];}
			elseif ($v[0] == "cactiuser")	{$cactiuser = $v[1];}
			elseif ($v[0] == "cactipass")	{$cactipass = $v[1];}
			elseif ($v[0] == "cactiurl")	{$cactiurl  = $v[1];}
		}
	}
}

//===================================================================
// Avoid directory traversal attacks (../ or ..\)
// Remove <script> tags
//       Avoid condition exclusion (e.g. attacking viewdev) with mysql comment --
// Recursive because array elements can be array as well
function sanitize( $arr ){
	if ( is_array($arr) ){
		return array_map( 'sanitize', $arr );
	}
	return preg_replace( "/\.\.\/|<\/?(java)?script>/i","", $arr );
}

//===================================================================
// Return IP address from hex value
function hex2ip($hip){
	return  hexdec(substr($hip, 0, 2)).".".hexdec(substr($hip, 2, 2)).".".hexdec(substr($hip, 4, 2)).".".hexdec(substr($hip, 6, 2));
}

//===================================================================
// Return IP address as hex
function ip2hex($ip){
	$i =  explode('.', str_replace( "*", "", $ip ) );
	return  sprintf("%02x%02x%02x%02x",$i[0],$i[1],$i[2],$i[3]);
}

//===================================================================
// Return IP address as bin
function ip2bin($ip){
	$i	=  explode('.',$ip);
	return sprintf(".%08b.%08b.%08b.%08b",$i[0],$i[1],$i[2],$i[3]);
}

//===================================================================
// Invert IP address
function ipinv($ip){
	$i	=  explode('.',$ip);
	return (255-$i[0]).".".(255-$i[1]).".".(255-$i[2]).".".(255-$i[3]);
}

//===================================================================
// convert netmask to various formats and check whether it's valid.
function Masker($in){

	if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$in) ){
		$mask = $in;
		list($n1,$n2,$n3,$n4) = explode('.', $in);
		$bits = str_pad(decbin($n1),8,0,STR_PAD_LEFT) .
			str_pad(decbin($n2),8,0,STR_PAD_LEFT) .
			str_pad(decbin($n3),8,0,STR_PAD_LEFT) .
			str_pad(decbin($n4),8,0,STR_PAD_LEFT);
		#$bits = str_pad(decbin($n1) . decbin($n2) . decbin($n3) . decbin($n4),32,0);
		$nbit = count_chars($bits);
		$pfix = $nbit[49];										// the 49th char is "1"...
		$dec  = ip2long($in);
	}elseif(preg_match("/^[-]|\d{3,10}$/",$in ) ){
		if( is_int($in) ){
			$in = sprintf("%u",$in);
		}
		$mask = long2ip($in);
		$bits = base_convert($in, 10, 2);
		$nbit = count_chars($bits);
		$pfix = $nbit[49];
		$dec  = $in;
	}elseif(preg_match("/^\d{1,2}$/",$in) ){
		#shift left of 255.255.255.255 will be 255.255.255.255.0! Trim after SHL (Vasily)
		#$bits = base_convert(sprintf("%u",0xffffffff << (32 - $in) ),10,2);
		$bits = base_convert(sprintf("%u",0xffffffff & (0xffffffff << (32 - $in)) ),10,2);
		$mask = bindec(substr($bits, 0,8)).".".bindec(substr($bits, 8,8)).".".bindec(substr($bits, 16,8)).".".bindec(substr($bits, 24,8));
		$pfix = $in;
		$dec  = 0xffffffff << (32 - $in);
	}
	$bin	= preg_replace( "/(\d{8})/", ".\$1", $bits );
	if(strstr($bits,'01') ){
		return array($pfix,'Illegal Mask',$bin,$dec);
	}else{
		return array($pfix,$mask,$bin,$dec);	
	}
}

//===================================================================
// Replace ridiculously big numbers with readable ones
function DecFix($n){

	if($n >= 1000000000){
		return round($n/1000000000)."G";
	}elseif($n >= 1000000){
		return round($n/1000000)."M";
	}elseif($n >= 1000){
		return round($n/1000)."k";
	}else{
		return $n;
	}

}

//===================================================================
// Colorize html bg according to timestamps
function Agecol($fs, $ls,$row){

	global $retire;

        $o = 120 + 20 * $row;
	if(!$ls){$ls = $fs;}

        $tmpf = round(100 - 100 * (time() - $fs) / ($retire * 86400));
        if ($tmpf < 0){$tmpf = 0;}

        $tmpl = round(100 * (time() - $ls) / ($retire * 86400));
        if ($tmpl > 100){$tmpl = 100;}

        $tmpd = round(100 * ($ls  - $fs) / ($retire * 86400));
        if ($tmpd > 100){$tmpd = 100;}

        $f = sprintf("%02x",$tmpf + $o);
        $l = sprintf("%02x",$tmpl + $o);
        $d = sprintf("%02x",$tmpd + $o);
        $g = sprintf("%02x",$o);

        return array("$g$f$d","$l$g$d");
}

#===================================================================
# Returns color based on type, order and offset for RRDs and Charts
# Type can traffic, error etc. or a numeric rgb pattern like 125,
# whereas any digit + offset must not be higher than 6!
# Parameters:	type, count, offset(0-3)
# Global:	-
# Return:	color
#===================================================================
function GetCol($typ,$cnt,$off=0){

	if($typ == 'trf'){
		return sprintf("#%x%x%x",$cnt%3*5+$off,$cnt%4*2+6+$off,$cnt%5*3+$off);
	}elseif($typ == 'err'){
		return sprintf("#%x%x%x",$cnt%4*2+6+$off,$cnt%5*3+$off,$cnt%3*5+$off);
	}elseif($typ == 'dsc'){
		return sprintf("#%x%x%x",$cnt%4*2+6+$off,$cnt%3*5+$off,$cnt%5*3+$off);
	}elseif($typ == 'brc'){
		return sprintf("#%x%x%x",$cnt%5*3+$off,$cnt%9+$off,13-$cnt%13+$off);
	}else{
		$r = substr($typ,0,1)+$cnt%10+$off;
		$g = substr($typ,1,1)+$cnt%10+$off;
		$b = substr($typ,2,1)+$cnt%10+$off;
		return sprintf("#%x%x%x",$r,$g,$b);
	}
}

//===================================================================
// Generate html select box
function selectbox($type,$sel){

	global $cndlbl;
	
	if($type == "oper"){
		$options = array("~"=>"~","!~"=>"!~","like"=>"like",">"=>">","="=>"=","!="=>"!=",">="=>">=","<"=>"<","&"=>"and","|"=>"or");
	}elseif($type == "comop"){
		$options = array(""=>"-","AND"=>"and","OR"=>"or",">"=>"Col > Col","="=>"Col = Col","!="=>"Col != Col","<"=>"Col < Col");
	}elseif($type == "limit"){
		$options = array("5"=>"5","10"=>"10","20"=>"20","50"=>"50","100"=>"100","200"=>"200","500"=>"500","1000"=>"1000","2000"=>"2000","0"=>"none!");
	}
	foreach ($options as $key => $txt){
	       $selopt = ($sel == "$key")?" selected":"";
	       echo "<option value=\"$key\"$selopt>$txt\n";
	}
	#TODO add this and opening tag to function? echo "</select>\n" or just return array, which can be used for sanity checks?
}

//===================================================================
// Generate html filter elements
function Filters($num=4){

	global $cols,$in,$op,$st,$co;
	global $collbl,$cndlbl,$vallbl;
?>
<script type="text/javascript" src="inc/datepickr.js"></script>
<link rel="stylesheet" type="text/css" href="inc/datepickr.css" />

<div style="margin: 2px 8px;padding: 2px 8px;">
<select name="in[]" title="<?= $collbl ?> 1">
<?php foreach ($cols as $k => $v){
	if( !preg_match('/(BL|IG|NS|NF)$/',$k) ){
		echo "<option value=\"$k\"".( ($in[0] == $k)?" selected":"").">$v\n";
	}
}?>
</select>
<select name="op[]" id="oa1"><?php selectbox("oper",$op[0]) ?></select>
<?php	if( $num == 1 ) echo '<br>'; ?>
<input  name="st[]" id="sa1" type="text" value="<?= $st[0] ?>" placeholder="<?= $cndlbl ?> 1" onfocus="select();" size="20">
<script>new datepickr('sa1', {'dateFormat': 'm/d/y'});</script>
<?php	if( $num == 1 ){ echo '</div>';return;} ?>
<select name="co[]" onchange="convis('1',this.value);">
<option value="">
<option value="AND"<?= ($co[0] == 'AND')?'selected':'' ?>>and
<option value="OR"<?=  ($co[0] == 'OR' )?'selected':'' ?>>or
<option value=">"<?=   ($co[0] == '>'  )?'selected':'' ?>>1 > 2
<option value="="<?=   ($co[0] == '='  )?'selected':'' ?>>1 = 2
<option value="!="<?=  ($co[0] == '!=' )?'selected':'' ?>>1 != 2
<option value="<"<?=   ($co[0] == '<'  )?'selected':'' ?>>1 < 2
</select>
<br>
<select name="in[]" id="ib1" title="<?= $collbl ?> 2">
<?php foreach ($cols as $k => $v){
	if( !preg_match('/(BL|IG|NS|NF)$/',$k) ){
		echo "<option value=\"$k\"".( ($in[1] == $k)?" selected":"").">$v\n";
	}
}?>
</select>
<select name="op[]" id="ob1"><?php selectbox("oper",$op[1]) ?></select>
<input  name="st[]" id="sb1" type="text" value="<?= $st[1] ?>" placeholder="<?= $cndlbl ?> 2" onfocus="select();" size="20">
<select name="co[]" id="cb1" onchange="fltvis(this.value);">
<option value="">
<option value="AND"<?= ($co[1] == 'AND')?' selected':'' ?>>and
<option value="OR"<?= ($co[1] == 'OR')?'selected':'' ?>>or
</select>
</div>
<div id="flt2" style="margin: 2px 8px;padding: 2px 8px;visibility: hidden">
<select name="in[]" id="ia2" title="<?= $collbl ?> 3">
<?php foreach ($cols as $k => $v){
	if( !preg_match('/(BL|IG|NS|NF)$/',$k) ){
		echo "<option value=\"$k\"".( ($in[2] == $k)?" selected":"").">$v\n";
	}
}?>
</select>
<select name="op[]" id="oa2" ><?php selectbox("oper",$op[2]) ?></select>
<input  name="st[]" id="sa2"  type="text" value="<?= $st[2] ?>" placeholder="<?= $cndlbl ?> 3" onfocus="select();" size="20">
<select name="co[]" id="ca2"  onchange="convis('2',this.value);">
<option value="">
<option value="AND"<?= ($co[2] == 'AND')?' selected':'' ?>>and
<option value="OR"<?=  ($co[2] == 'OR' )?'selected':'' ?>>or
<option value=">"<?=   ($co[2] == '>'  )?'selected':'' ?>>3 > 4
<option value="="<?=   ($co[2] == '='  )?'selected':'' ?>>3 = 4
<option value="!="<?=  ($co[2] == '!=' )?'selected':'' ?>>3 != 4
<option value="<"<?=   ($co[2] == '<'  )?'selected':'' ?>>3 < 4
</select>
<br>
<select name="in[]" id="ib2" title="<?= $collbl ?> 4">
<?php foreach ($cols as $k => $v){
	if( !preg_match('/(BL|IG|NS|NF)$/',$k) ){
		echo "<option value=\"$k\"".( ($in[3] == $k)?" selected":"").">$v\n";
	}
}?>
</select>
<select name="op[]" id="ob2" style="visibility: hidden"><?php selectbox("oper",$op[3]) ?></select>
<input  name="st[]" id="sb2" type="text" value="<?= $st[3] ?>" placeholder="<?= $cndlbl ?> 4" onfocus="select();" size="20">
</div>

<script>
function fltvis(val){

	if(val){
		document.getElementById('flt2').style.visibility="inherit";
	}else{
		document.getElementById('ca2').selectedIndex=0;
		window.onload = convis('2','');
		document.getElementById('flt2').style.visibility="hidden";
	}
}

function convis(sq,op){

	if( op.match(/[<>=]/) ){
		document.getElementById('oa'+sq).style.visibility="hidden";
		document.getElementById('sa'+sq).style.visibility="hidden";
		document.getElementById('ib'+sq).style.visibility="inherit";
		document.getElementById('ob'+sq).style.visibility="hidden";
		document.getElementById('sb'+sq).style.visibility="hidden";
		if( sq == '1' ){
			document.getElementById('cb'+sq).style.visibility="inherit";
		}
	}else if(op == 'AND' || op == 'OR'){
		document.getElementById('oa'+sq).style.visibility="inherit";
		document.getElementById('sa'+sq).style.visibility="inherit";
		document.getElementById('ib'+sq).style.visibility="inherit";
		document.getElementById('ob'+sq).style.visibility="inherit";
		document.getElementById('sb'+sq).style.visibility="inherit";
		if( sq == '1' ){
			document.getElementById('cb'+sq).style.visibility  = "inherit";
		}
	}else{
		document.getElementById('oa'+sq).style.visibility="inherit";
		document.getElementById('sa'+sq).style.visibility="inherit";
		document.getElementById('ib'+sq).style.visibility="hidden";
		document.getElementById('ob'+sq).style.visibility="hidden";
		document.getElementById('sb'+sq).style.visibility="hidden";
		if( sq == '1' ){
			document.getElementById('cb'+sq).style.visibility="hidden";
			document.getElementById('cb'+sq).selectedIndex=0;
			document.getElementById('flt2').style.visibility = "hidden";
		}
	}
}

window.onload = convis('1','<?= $co[0] ?>');
window.onload = convis('2','<?= $co[2] ?>');
window.onload = fltvis('<?= $co[1] ?>');

new datepickr('sb1', {'dateFormat': 'm/d/y'});
new datepickr('sa2', {'dateFormat': 'm/d/y'});
new datepickr('sb2', {'dateFormat': 'm/d/y'});

</script>
<?PHP
}

//===================================================================
// Generate condition header or SQL if mod=2
function Condition($in,$op,$st,$co,$mod=0){

	global $cols;

	$h = '';
	$w = '';

	$comok = 0;
	if( !count($in) ) return '';

	if( preg_match('/[<>=]/',$co[0]) ){								# subconditions 1 and 2 compare columns
		$w .= $in[0]." $co[0] ".$in[1];
		$h .= $cols[$in[0]]." $co[0] ".$cols[$in[1]];
		$comok = 1;
	}elseif( $op[0] and !( preg_match('/~|LIKE$/i',$op[0]) and $st[0] === '') ){			# process normally unless empty regexp/like in 1
		$w .= AdOpVal($in[0],$op[0],$st[0]);
		$h .= $cols[$in[0]]." $op[0] '".$st[0]."'";
		if($co[0] and $op[1] and !( preg_match('/~|LIKE$/i',$op[1]) and $st[1] === '') ){	# subcondition 2 unless empty regexp/like
			$w .= " $co[0] ".AdOpVal($in[1],$op[1],$st[1]);
			$h .= " $co[0] ".$cols[$in[1]]." $op[1] '".$st[1]."'";
			$comok = 1;
		}
	}
	if($comok and $co[1] ){										# Combining subconditions
		if( preg_match('/[<>=]/',$co[2]) ){							# subconditions 3 and 4 compares columns
			$w .= " $co[1] ".$in[2]." $co[2] ".$in[3];
			$h .= " $co[1] ".$cols[$in[2]]." $co[2] ".$cols[$in[3]];
		}elseif($op[2] and !( preg_match('/~|LIKE$/i',$op[2]) and $st[2] === '') ){		# process normally unless empty regexp/like in 3
			$w2 = AdOpVal($in[2],$op[2],$st[2]);
			$h2 = $cols[$in[2]]." $op[2] '".$st[2]."'";
			if($co[2] and $op[2] and !( preg_match('/~|LIKE$/i',$op[3]) and $st[3] === '') ){# subcondition 4 unless empty regexp/like
				$w2 .= " $co[2] ".AdOpVal($in[3],$op[3],$st[3]);
				$h2 .= " $co[2] ".$cols[$in[3]]." $op[3] '".$st[3]."'";
			}
			$w = "($w) $co[1] ($w2)";
			$h = "($h) $co[1] ($h2)";
		}
	}

	if($mod == 2){
		 return ($w)?"WHERE $w":'';
	}elseif($mod){
		 return $h;
	}else{
		if($h) echo "<h3>$h</h3>";
	}
}

//===================================================================
// Generate table header
// Opt	Bgcolor, column mode: 2 or 3=use all, 0 or 3=no sorting (1 shows selected columns with sorting arrow)
// Keys BL=blank, IG=ignored, NS=no-sort, NF=no-filter
function TblHead($bkg,$mode = 0){

	global $ord,$cols,$col,$altlbl,$srtlbl;

	if( isset($_GET['xls']) ){
		echo "<table><tr>";
	}else{
		echo "<table class=\"content\"><tr>";
	}

	if($mode == 2 or $mode == 3){
		$mycol = array_keys($cols);
	}else{
		$mycol = $col;
	}
	foreach( $mycol as $n ){
		if( !preg_match('/IG$/',$n) ){
			if( preg_match('/BL$/',$n) ){
				echo "<th class=\"$bkg\">&nbsp;</th>";
			}elseif( isset($_GET['xls']) or preg_match('/NS$/',$n) or $mode == 3 or !$mode ){
				echo "<th class=\"$bkg\">$cols[$n]</th>";
			}elseif( !array_key_exists($n,$cols) ){
				echo "<th class=\"$bkg\">$n</th>";
			}else{
				$nclr = preg_replace('/NF$/','',$n);
				if( !$ord ){
					echo "<th nowrap class=\"$bkg\">$cols[$n]<a href=\"?$_SERVER[QUERY_STRING]&ord=$nclr+desc\"><img src=img/dwn.png title=\"Sort by $nclr\"></a></th>\n";
				}elseif($ord == $nclr){
					echo "<th nowrap class=\"$bkg mrn\">$cols[$n] <a href=\"?";
					echo preg_replace('/&ord=[\w+]+/',"",$_SERVER['QUERY_STRING']);
					echo "&ord=$nclr+desc\"><img src=\"img/up.png\" title=\"$srtlbl\"></a></th>\n";
				}elseif($ord == "$nclr desc"){
					echo "<th nowrap class=\"$bkg mrn\">$cols[$n] <a href=\"?";
					echo preg_replace('/&ord=[\w+]+/',"",$_SERVER['QUERY_STRING']);
					echo "&ord=$nclr\"><img src=\"img/dwn.png\" title=\"$altlbl $srtlbl\"></a></th>\n";
				}else{
					echo "<th nowrap class=\"$bkg\">$cols[$n] <a href=\"?";
					echo preg_replace('/&ord=[\w+]+/',"",$_SERVER['QUERY_STRING']);
					echo "&ord=$nclr+desc\"><img src=\"img/dwn.png\" title=\"$srtlbl $nclr\"></a></th>\n";
				}
			}
		}
	}
	echo "</tr>\n";
}

//===================================================================
// Generate table row
function TblRow($bg,$static=0){


	if( isset($_GET['xls']) ){
		echo "<tr>";
	}elseif($static){
		echo "<tr class=\"$bg\">";
	}elseif(isset($_GET['print']) ){
		echo "<tr class=\"$bg\" onclick=\"this.className='warn'\" ondblclick=\"this.className='$bg'\">";
	}else{
		echo "<tr class=\"$bg\" onmouseover=\"this.className='imga'\" onmouseout=\"this.className='$bg'\">";
	}
}

//===================================================================
// Generate table cell
function TblCell($val="",$href="",$fmt="",$img="",$typ=""){

	$cval = '';
	$cfmt = '';
	$cimg = '';
	if( isset($_GET['xls']) ){
		$cval = $val;
	}else{
		if( isset($_GET['print']) ){
			if( !strstr($typ,"-imx") ) $cval = $val;
			if( $img and preg_match('/-im[gx]$/',$typ) ) $cimg = $img;
		}else{
			if( !strstr($typ,"-imx") ) $cval = ( $href )?"<a href=\"$href\">$val</a>":$val;
			if( $img ) $cimg = $img;
		}
		$cfmt = ($fmt)?" $fmt":'';
	}

	if( strstr($typ,"th") ){
		echo "<th$cfmt>$cimg$cval</th>";
	}else{
		echo "<td$cfmt>$cimg$cval</td>";
	}
}

//===================================================================
// Generate coloured bar graph element
// mode determines color (used as threshold, if numeric)
// style si=small icon, mi=medium icon, ms=medium shape, ls=large shape (mode=bgcol), li=large icon (default)
function Bar($val=1,$mode='',$style='',$tit=''){

	if($mode === "lvl250"){										# === doesn't fail if $mode is 0
			$img = "red";
			$bg = "#f08c8c";
	}elseif( $mode === "lvl200" ){
			$img = "org";
			$bg = "#f0b464";
	}elseif( $mode === "lvl150" ){
			$img = "yel";
			$bg = "#f0f08c";
	}elseif( $mode === "lvl100" ){
			$img = "blu";
			$bg = "#8c8cf0";
	}elseif( $mode === "lvl50" ){
			$img = "grn";
			$bg = "#8cf08c";
	}elseif( $mode === 0 or preg_match('/^lvl/',$mode) ){
			$img = "gry";
			$bg = "#b4b4b4";
	}elseif($mode > 0){
		if($val < $mode){
			$img = "grn";
			$bg = "#8cf08c";
		}elseif($val < 2 * $mode){
			$img = "org";
			$bg = "#f0b464";
		}else{
			$img = "red";
			$bg = "#f08c8c";
		}
	}elseif($mode < 0){
		if($val < -$mode/2){
			$img = "red";
			$bg = "#f08c8c";
		}elseif($val < -$mode){
			$img = "org";
			$bg = "#f0b464";
		}else{
			$img = "grn";
			$bg = "#8cf08c";
		}
	}else{
		$img = "gry";
		$bg  = $mode;
	}
	if($style == "ls"){
		$bar = "<div style=\"float:left;margin: 0 4px;padding-right: 4px;width:".round(log(round($val)+1)*20)."px;height:16px;border:1px solid #000;background-color: $bg\">$tit</div>";
	}elseif($style == "ms"){
		$bar = "<div style=\"float:left;margin: 0 2px;padding-right: 2px;width:".round(log(round($val)+1)*10)."px;height:14px;border:1px solid #000;background-color: $bg\">$tit</div>";
	}elseif($style == "mi"){
		$bar = "<img src=img/$img.png width=".round(log(round($val)+1)*10)." class=\"smallbar\" title=\"$tit\">";
	}elseif($style == "si"){
		$bar = "<img src=img/$img.png width=".round(log(round($val)+1)*4)." class=\"smallbar\" title=\"$tit\">";
	}else{
		if($val > 1000){
			$wdh = round(160+sqrt($val));
		}elseif($val > 100){
			$wdh = round(100+$val/6);
		}else{
			$wdh = round($val);
		}
		$bar = "<img src=img/$img.png width=\"$wdh\" class=\"bigbar\" title=\"$tit\">";
	}
	return $bar;
}

//===================================================================
// Return network type
function Nettype($ip,$ip6=""){

	#if ($ip == "0.0.0.0"){$img = "netr";$tit="Default";
	if (preg_match("/^127\.0\.0/",$ip) or preg_match("/^::1/",$ip6) ){$img = "netr";$tit="LocalLoopback";
	}elseif (preg_match("/^192\.168/",$ip)){$img = "nety";$tit="Private 192.168/16";
	}elseif (preg_match("/^10\./",$ip)){$img = "netp";$tit="Private 10/8";
	}elseif (preg_match("/^172\.[1][6-9]/",$ip)){$img = "neto";$tit="Private 172.16/12";
	}elseif (preg_match("/^172\.[2][0-9]/",$ip)){$img = "neto";$tit="Private 172.16/12";
	}elseif (preg_match("/^172\.[3][0-1]/",$ip)){$img = "neto";$tit="Private 172.16/12";

	}elseif (preg_match("/^224\.0\.0/",$ip)){$img = "netb";$tit="Local Multicast-224.0.0/24";
	}elseif (preg_match("/^224\.0\.1/",$ip)){$img = "netb";$tit="Internetwork  Multicast-224.0.1/24";
	}elseif (preg_match("/^(224|233)/",$ip)){$img = "netb";$tit="AD-HOC Multicast-224~233";
	}elseif (preg_match("/^232\./",$ip)){$img = "netb";$tit="Source-specific Multicast-232/8";
	}elseif (preg_match("/^233\./",$ip)){$img = "netb";$tit="GLOP Multicast-233/8";
	}elseif (preg_match("/^234\./",$ip)){$img = "netb";$tit="Unicast-Prefix Multicast-234/8";
	}elseif (preg_match("/^239\./",$ip)){$img = "netb";$tit="Public Multicast-239/8";

	}elseif (preg_match("/^fe80/",$ip6)){$img = "nety";$tit="IPv6 Link Local";
	}elseif (preg_match("/^fc00/",$ip6)){$img = "neto";$tit="IPv6 Unique Local";
	}elseif (preg_match("/^ff01/",$ip6)){$img = "netb";$tit="IPv6 Interface Local Multicast";
	}elseif (preg_match("/^ff02/",$ip6)){$img = "netb";$tit="IPv6 Link Local Multicast";
	}elseif (preg_match("/^2001:0000/",$ip6)){$img = "netp";$tit="IPv6 Teredo";

	}else{$img = "netg";$tit="Public";}
	
	return array("$img.png",$tit);
}

//===================================================================
// Return Smilie based on name
function Smilie($usr,$s=0){
	
	global $stslbl, $cfglbl, $dsclbl, $msglbl;

	$n = strtolower($usr);
	if($n == "statc"){
		return "<img src=\"img/32/conf.png\"".($s?"width=\"20\"":"")." title=\"$cfglbl $stslbl\">";
	}elseif($n == "statd"){
		return "<img src=\"img/32/radr.png\"".($s?"width=\"20\"":"")." title=\"$dsclbl $stslbl\">";
	}elseif($n == "state"){
		return "<img src=\"img/32/bell.png\"".($s?"width=\"20\"":"")." title=\"$msglbl $stslbl\">";
	}elseif($n == "stati"){
		return "<img src=\"img/32/port.png\"".($s?"width=\"20\"":"")." title=\"Interface $stslbl\">";
	}else{
		$si = ( ord($n) + ord(substr($n,1)) + ord(substr($n,-1)) + ord(substr($n,-2)) ) % 99;
		return "<img src=\"img/usr/$si.png\"".($s?"width=\"20\"":"")." title=\"$n\">";
	}
}

//===================================================================
// Replace time of a variable in query string
function SkewTime($istr,$var,$days){

	global $sta, $end;

	$s = $days * 86400;
	if($var == "all"){
		$repl = "sta=".urlencode(date("m/d/Y H:i", ($sta + $s)))."&";
		$ostr = preg_replace("/sta=[0-9a-z%\+]+&/i",$repl,$istr);
		$repl = "end=".urlencode(date("m/d/Y H:i",($end + $s)))."&";
		$ostr = preg_replace("/end=[0-9a-z%\+]+(&|$)/i",$repl,$ostr);
	}else{
		$repl = "$var=".urlencode(date("m/d/Y H:i",(${$var} + $s)))."&";
		$ostr = preg_replace("/$var=[0-9a-z%\+]+(&|$)/i",$repl,$istr);
	}

	return $ostr.(strpos($ostr,'sho')?'':'sho=1');
}

//===================================================================
// Return Hex Address
// echo IP6('fe80::3ee5:a6ff:feca:ea41');
function IP6($addr) {
	return bin2hex( inet_pton($addr) );
}

//===================================================================
// Return fileicon
function FileImg($f) {
	
	global $hislbl,$fillbl,$imglbl,$cfglbl,$cmdlbl,$mlvl;

	$l  = "";
	$ed = 0;
	if(preg_match("/\.(zip|tgz|tbz|tar|gz|7z|bz2|rar)$/i",$f))	{$i = "pkg"; $t = "Archive";}
	elseif(stristr($f,".csv"))				{$i = "list";$t = "CSV $fillbl";$l = $f;}
	elseif(stristr($f,".def"))				{$i = "geom";$t = "Device Definition";$l = "Other-Defgen.php?so=".urlencode(basename($f,".def"));}
	elseif(stristr($f,".log"))				{$i = "note";$t = "$hislbl";$l = $f;}
	elseif(stristr($f,".js"))				{$i = "dbmb";$t = "Javascript";$l = $f;}
	elseif(stristr($f,".pdf"))				{$i = "pdf"; $t = "PDF $fillbl";$l = $f;}
	elseif(stristr($f,".php"))				{$i = "php"; $t = "PHP Script";}
	elseif(stristr($f,".patch"))				{$i = "hlth";$t = "System Patch";}
	elseif(stristr($f,".reg"))				{$i = "nwin";$t = "Registry $fillbl";}
	elseif(stristr($f,".xml"))				{$i = "dcub";$t = "XML $fillbl";$l = $f;$ed = 1;}
	elseif(preg_match("/\.(bmp|gif|jpg|png|svg)$/i",$f))	{$i = "img";$t = "$imglbl";$l = "javascript:pop('$f','$imglbl')";}
	elseif(preg_match("/\.(txt|text)$/i",$f))		{$i = "abc"; $t = "TXT $fillbl";$l = $f;$ed = 1;}
	elseif(preg_match("/[.-](cfg|conf|config)$/i",$f))	{$i = "conf";$t = "$cfglbl";$ed = 1;}
	elseif(preg_match("/\.(exe)$/i",$f))			{$i = "cog";$t = "$cmdlbl";}
	elseif(preg_match("/\.(htm|html)$/i",$f))		{$i = "dif";$t = "HTML $fillbl";$l = $f;}
	elseif(preg_match("/\.(pcm|raw)$/i",$f))		{$i = "bell";$t = "Ringtone";}
	elseif(preg_match("/\.(msq|psq|sql)$/i",$f))		{$i = "db";$t = "DB Dump";}
	elseif(preg_match("/\.(btm|loads)$/i",$f))		{$i = "nhdd"; $t = "Boot Image";}
	elseif(preg_match("/\.(app|bin|img|sbn|swi|ipe|xos)$/i",$f)){$i = "cbox"; $t = "Binary Image";}
	elseif(preg_match("/\.(cer|crt|crl|spc|stl)$/i",$f)){$i = "lock"; $t = "Cert & Co";}
	else							{$i = "bbox";$t = "$mlvl[10]";}

	if($l){
		return array("<a href=\"$l\"><img src=\"img/16/$i.png\" title=\"$f - $t\"></a>",$ed);
	}else{
		return array("<img src=\"img/16/$i.png\" title=\"$f - $t\">",$ed);
	}
}

?>
