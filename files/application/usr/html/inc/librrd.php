<?php
#============================================================================
#
# Program: librrd.php
# Programmer: Remo Rickli
#
# Functions for RRDs either used by drawrrd.php or in the map.
#
#============================================================================

#===================================================================
# Replace characters, which could break the (PNG) image
# Parameters:	label
# Global:	-
# Return:	sanitized label
#===================================================================
function Safelabel($s){
	return preg_replace('/[$&]/','.', $s);
}

#===================================================================
# Stack traffic and errors
# Parameters:	rrd, type
# Global:	-
# Return:	in/out graphs, title
#===================================================================
function GraphTraffic($rrd,$t){

	global $errlbl,$trflbl,$debug,$stalbl;

	$c	= 0;
	$drawin = '';
	$drawout= '';
	$outdir = '';
	$odef   = '';
	$idef   = '';
	$inmod  = 'AREA';
	$n	= count($rrd);

	if($t == 'trf'){
		$idef = 'inoct';
		$odef = 'outoct';
		$tit = ($_SESSION['gbit'])?"$trflbl [Bit/s]":"$trflbl [Byte/s]";
	}elseif($t == 'err'){
		$idef = 'inerr';
		$odef = 'outerr';
		$tit = "$errlbl/s";
	}elseif($t == 'dsc'){
		$idef = 'indisc';
		$odef = 'outdisc';
		$tit = 'Discards/s';
	}elseif($t == 'sta'){
		$tit = "IF $stalbl";
	}else{
		$idef = 'inbcast';
		$tit = 'Broadcasts/s';
	}
	if($_SESSION['gneg']){
		$outdir = '-';
		$outmod = 'AREA';
	}else{
		$outmod = 'LINE2';
	}

	foreach (array_keys($rrd) as $i){
		$c++;
		$eol = ($c == $n)?"\\l":"";
		$il = str_replace(":","\:",$i);
		if($t == 'sta'){
			$drawin  .= "DEF:sta$c=$rrd[$i]:status:AVERAGE ";
			$drawin  .= "CDEF:sh$c=sta$c,0,EQ $inmod:sh$c#cc8844: ";
			$drawin  .= "CDEF:dn$c=sta$c,1,2,LIMIT,1,0,IF $inmod:dn$c#cccc44: ";#TODO remove $inmod and just multiply by $c to avoid stacking problem?
			$drawin  .= "CDEF:up$c=sta$c,2,GT $inmod:up$c#44cc44: ";
			$drawin  .= "CDEF:un$c=sta$c,UN $inmod:un$c#cccccc:\"$il ";
		}elseif($t == 'trf' and $_SESSION['gbit']){
			$drawin  .= "DEF:inbyte$c=$rrd[$i]:$idef:AVERAGE ";
			$drawin  .= "CDEF:$idef$c=inbyte$c,8,* $inmod:$idef$c". GetCol($t,$c,0) .":\"$il in ";
			$drawout .= "DEF:outbyte$c=$rrd[$i]:$odef:AVERAGE ";
			$drawout .= "CDEF:$odef$c=outbyte$c,${outdir}8,* $outmod:$odef$c". GetCol($t,$c,3) .":\"$il out";
		}else{
			$drawin  .= "DEF:$idef$c=$rrd[$i]:$idef:AVERAGE $inmod:$idef$c". GetCol($t,$c,0) .":\"$il in ";
			$drawout .= "DEF:outgr$c=$rrd[$i]:$odef:AVERAGE ";
			$drawout .= "CDEF:$odef$c=outgr$c,${outdir}1,* $outmod:$odef$c". GetCol($t,$c,3) .":\"$il out";
		}

		if ($t == 'trf' and $n == 1 ){#and !$_SESSION['gneg']){# Couldn't figure out yet, why 95% is incorrect on negative traffic??!?
			$drawin  .= "\" VDEF:tio95=$idef$c,95,PERCENT GPRINT:tio95:\"%7.2lf%s\" HRULE:tio95#ffcc44:\"95%\" ";
			$drawout .= "\" VDEF:too95=$odef$c,95,PERCENT GPRINT:too95:\"%7.2lf%s\" HRULE:too95#ff4444:\"95%\" ";
			$drawin  .= "GPRINT:$idef$c:MIN:\" %7.2lf%s min\" GPRINT:$idef$c:MAX:\" %7.2lf%s max\" GPRINT:$idef$c:AVERAGE:\" %7.2lf%s avg ";
			$drawout .= "GPRINT:$odef$c:MIN:\" %7.2lf%s min\" GPRINT:$odef$c:MAX:\" %7.2lf%s max\" GPRINT:$odef$c:AVERAGE:\" %7.2lf%s avg ";
		}

		$drawin  .= "$eol\" ";
		$drawout .= "$eol\" ";

		if($debug){
			$drawin  .= "\n\t";
			$drawout .= "\n\t";
		}
		$inmod  = 'STACK';
		$outmod = 'STACK';
	}

	if ($t == 'brc' or $t == 'sta'){
		return array($drawin,$tit);
	}else{
		return array($drawin.$drawout,$tit);
	}
}

#===================================================================
# Defines graphs according to parameters
# Parameters:	size (1=tiny,2=small,3=med,4=large,5=x-large), start, end, tile, option (bw for tiny else canvas)
# Global:	-
# Return:	in/out graphs, title
#===================================================================
function GraphOpts($siz,$sta,$end,$tit,$opt){

	global $datfmt;

	if($siz < 2){
		if($opt == 1){					# error graph
			return "-w50 -h30 -j -c CANVAS#eeccbb";
		}elseif($opt == 100){				# broadcast graph
			return "-w50 -h30 -u$opt -j -c CANVAS#dddddd";
		}elseif($opt){					# traffic graph
			return "-w50 -h30 -u$opt -j -c CANVAS#ccddee";
		}else{						# discardsm broadcast
			return "-w50 -h30 -j -c CANVAS#eeeeee";
		}
	}elseif($siz == 2){
		$dur = (($sta)?"-s${sta}":"-s-1d").(($end)?" -e${end} ":"");
		return "-w80 -h52 -g $dur -L5";
	}elseif($siz == 3){
		$dur = (($sta)?"-s${sta}":"-s-3d").(($end)?" -e${end} ":"");
		return "--title=\"$tit\" -g -w150 -h90 $dur -L6";
	}elseif($siz == 4){
		$dur = (($sta)?"-s${sta}":"-s-5d").(($end)?" -e${end} ":"");
		return "--title=\"$tit\" -w250 -h100 $dur -L6";
	}else{
		$sta = ($sta)?$sta:('date' - 7 * 86400);
		$dur = "-s${sta}".(($end)?" -e${end} ":"");
		return "--title=\"$tit ". date($datfmt,$sta)."\" -w800 -h200 $dur -L6";
	}
}

?>
