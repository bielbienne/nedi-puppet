#!/usr/bin/perl

=pod

=head1 PROGRAM nedi.pl

Main program which can initialize the DB and perform discovery and
other useful tasks.

=head1 SYNOPSIS

nedi.pl -lotsofoptions, see HELP!

=head2 LICENSE

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

=head2 AUTHORS

Remo Rickli & NeDi Community

Visit http://www.nedi.ch for more information.

=cut

our $VERSION = "1.0.9-010";

use strict;
use warnings;
no warnings qw(once);
use bignum;

use Getopt::Std;
use File::Path;
use Net::Ping;

#use threads;
#use threads::shared;
#my $threads = 5;

use vars qw($p $warn $nediconf $now $lasdis);
use vars qw(%nod %dev %int %mod %link %vlan %opt %net %usr);
@misc::donenam = ();											# Avoid 'used only once' warning (won't break evals like LWP in libweb this way)TODO still needed with no(warn once)?
$misc::mq = 0;
$misc::ncmd = "$0 ".join(" ",@ARGV);


getopts('a:A:bB:c:dDfFiIkKl:Nnoprs:S:t:Tu:U:vWy',\%opt) || &HELP_MESSAGE;
if(!defined $opt{'S'}){$opt{'S'} = ''}									# Avoid warnings if unused

select(STDOUT); $| = 1;											# Disable buffering

$p   = $0;
$p   =~ s/(.*)\/(.*)/$1/;
if($0 eq $p){$p = "."};
$now = time;
require "$p/inc/libmisc.pm";										# Use the miscellaneous nedi library
require "$p/inc/libsnmp.pm";										# Use the SNMP function library
require "$p/inc/libmon.pm";										# Use the Monitoring lib for notifications
require "$p/inc/libcli.pm";										# Use the CLI function library
require "$p/inc/libdb.pm";										# Use the DB function library

&misc::ReadConf();

$lasdis = $now - $misc::rrdstep * 1.1;									# Last discovery time with some slack

my $dsok = 0;
if($opt{'d'}){
	use Data::Dumper;
	eval 'use Devel::Size qw(size total_size);';
	if ($@){
		&misc::Prt("PERL:devl::size not available\n");
	}else{
		$dsok = 1;
		&misc::Prt("PERL:devel::size loaded\n");
	}
}

=head2 Debug Mode

The -D option skips discovery and loads internal variables (using misc::RetrVar), which where previousely
stored in the .db files (with -d, debug output). This works like a snapshot and allows for debugging the
post discovery functions.

=cut
if ( $opt{'D'} ){
# Debug VoIP Phones
#	require "$p/inc/libweb.pm";
#	$main::dev{'test'}{ip} = 'q/7912.html';
#	&web::CiscoPhone('test');
#die;
	&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass);
	&misc::ReadOUIs();
	&db::ReadDev();
	&db::ReadAddr();
	&misc::RetrVar();
# Functions to be debugged go here
#	&db::UnStock();
#	&misc::BuildArp();
#	print &db::Select('system', '','value','name="first"');

#	$misc::retire = $now - 1 * 86400;								# Test Retiring, e.g. with 1 day
	&db::ReadNod();
	&misc::BuildNod();
	&db::Disconnect();
#	&misc::FloodFind() if $misc::notify =~ /n/i
#	&db::WriteNod();
	#&misc::TopRRD();
}elsif( $opt{'i'} or $opt{'I'} ){
	my $adminuser;
	my $adminpass;
	my $nedihost = 'localhost';
	if ($#ARGV eq 1){										# 2 arguments for -i?
		$adminuser = $ARGV[0];
		$adminpass = $ARGV[1];
	}else{												# interactive credentials then...
		print "\nInitialize NeDi DB".(($opt{'I'})?', delete configs and RRDs':'')."!!!\n";
		print "------------------------------------------------------------------------\n";
		print "$misc::backend admin user: ";
		$adminuser = <STDIN>;
		print "$misc::backend admin pass: ";
		$adminpass = <STDIN>;
		if($misc::dbhost ne $nedihost){
			print "NeDi host (where the discovery runs on: ";
			$nedihost = <STDIN>;
		}
	}
	$adminuser = (defined $adminuser)?$adminuser:"";						# Avoid errors on empty Webform
	$adminpass = (defined $adminpass)?$adminpass:"";
	chomp($adminuser,$adminpass,$nedihost);
	&db::InitDB($adminuser, $adminpass, $nedihost);

	if( $opt{'I'} ){
		my $nrrd = rmtree( "$misc::nedipath/rrd", {keep_root => 1} );
		my $ncfg = rmtree( "$misc::nedipath/conf", {keep_root => 1} );
		mkpath("$misc::nedipath/rrd");
		mkpath("$misc::nedipath/conf");
		print "INIT: $nrrd RRDs and $ncfg configurations (with dirs) deleted!\n\n";
	}
}elsif( $opt{'y'} ){
	print "Supported Devices (NeDi $main::VERSION) ----------------------------------------\n";
	chdir("$p/sysobj");
	my $nd = 0;
	my @defs = glob("*.def");
	foreach my $df (sort @defs){
		$nd++;
		open F, $df or print "couldn't open $df\n" && return;
		while (<F>) {
			next unless s/^Type\s*//;
			$_ =~ s/\r|\n//g;
			printf ("%-30s%s\n",$_,$df );
		}
	}
	print "$nd Definitions (and counting) ------------------------------------------------\n\n";
}elsif( $opt{'s'} ){
	&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass);
	print "Scanning Nodes ($main::VERSION) ---------------------------------------------\n";
	if($opt{'s'} =~ /update/) {&db::ReadNod("lastseen > $now-$misc::rrdstep order by osupdate")}
	elsif($opt{'s'} =~ /ip=/){
		my $ip = $opt{'s'};
		$ip =~ s/.*ip=//;
		&db::ReadNod("nodip = \"".&misc::Ip2Dec(join('.',unpack( 'C4',gethostbyname($ip))))."\"");
	}
	my $fp = ($opt{'s'} =~ /O/)?"-O":"";
	my $fm = ($opt{'s'} =~ /F/)?"-F":"";
	my $up = ($opt{'s'} =~ /U/)?"-sU":"";
	my $tp = ($opt{'s'} =~ /T/)?"-sS":"";

	&misc::Prt("SCAN:Using nmap $up $tp $fp $fm\n");
	foreach my $mc (keys %main::nod){
		if ($main::nod{$mc}{ip} ne "0.0.0.0"){
			my $alive = 1;
			my $tcp = my $udp = "";
			my $os = my $typ = "?";
			&misc::Prt("SCAN:$main::nod{$mc}{ip} $main::nod{$mc}{na}:");
			my @res = split(/\n/,`nmap $up $tp $fp $fm $main::nod{$mc}{ip} 2> /dev/null`);
			foreach my $l (@res){
				if($l =~ /Host seems down/){$alive = 0;}
				elsif($l =~ /^([0-9]+)\/tcp/){$tcp .= "$1 ";}
				elsif($l =~ /^([0-9]+)\/udp/){$udp .= "$1 ";}
				elsif($l =~ /^Running.*?:\s*(.*)/){$os = "$1";}
				elsif($l =~ /^OS details:\s*(.*)/){$os = "$1";}				# Comment out if not better than Running:
				elsif($l =~ /^Device type:\s*(.*)/){$typ = "$1";}
				#elsif($typ =~ /general purpose|\?/ and $l =~ /Device:\s*(.*)/){$typ = "$1";}
			}
			if($alive){
				if($misc::notify =~ /p/ and $main::nod{$mc}{ou}){			# Log port changes if desired and nodes was scanned before
					if($main::nod{$mc}{tp} ne $tcp){
						&db::Insert('events','level,time,source,info,class,device',"100,$main::now,'$nod{$mc}{ip}','TCP Port change from $nod{$mc}{tp} to $tcp detected','node','$nod{$mc}{dv}'");
					}
					if($nod{$mc}{up} ne $udp){
						&db::Insert('events','level,time,source,info,class,device',"100,$now,'$nod{$mc}{ip}','UDP Port change from $nod{$mc}{up} to $udp detected','node','$nod{$mc}{dv}'");
					}
				}
				&misc::Prt("$typ TCP:$tcp UDP:$udp\n");
				&db::Update('nodes',"tcpports=\"$tcp\",udpports=\"$udp\",nodtype=\"$typ\",nodos=\"$os\",osupdate=\"$now\"","mac = \"$mc\"");
			}else{
				&misc::Prt("is down\n");
			}
		}
		if(time - $misc::rrdstep > $now){
			&misc::Prt("SCAN:Running longer than ${misc::rrdstep}s, ending now.\n");
			last;
		}
	}
	&db::Disconnect();
}else{
	$misc::lwpok = 0;
	require "$p/inc/libweb.pm" unless $opt{'S'} =~ /W/;						# Use the WEB functions for webdevs if not skipped

	&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass);
	&misc::ReadOUIs();
	&db::ReadDev($opt{'A'});									# Needs to come before InitSeeds for -A
	&db::ReadAddr();
	&db::ReadLink("linktype = 'STAT'");								# Static links will override DP
	my $ntgt  = &mon::InitMon();
	my $nseed = &misc::InitSeeds();
	my $nthrd = &db::Select('system','','value',"name='threads'");
	my $first = &db::Select('system','','value',"name='first'");

	if ($opt{t}){
		print "MAIN:Ignoring $nthrd thread".(($nthrd > 1)?"s":"")." for testing\n" if $opt{'v'};
	}elsif ($nthrd > 0){
		print "MAIN:$nthrd thread".(($nthrd > 1)?"s":"").", 1st from ".localtime($first) if $opt{'v'};
		if ( ($now - $first) > 3 * $misc::rrdstep ){						# Check for stale threads (based on community ideas)
			print " is older than ".3*$misc::rrdstep."s, resetting!\n" if $opt{'v'};
			&db::Update('system',"value='$now'","name='first'");
			&db::Update('system',"value=1","name='threads'");				# Make sure we're back to 1!
			&db::Insert('events','level,time,source,info,class',"200,$now,'NeDi','$nthrd thread(s), 1st from ".localtime($first)." is older than $misc::rrdstep seconds!','bugn'");
		}else{
			print " seems ok adding this one\n" if $opt{'v'};
			my $casval = ($misc::backend eq 'Pg')?'cast(value as int)':'value';
			&db::Update('system',"value = $casval+1","name='threads'");	# Register new thread
		}
	}else{
		if ($nthrd < 0){
			my $err = "$nthrd thread(s) error, 1st from ".localtime($first)." make sure interval is longer than discovery takes!";
			print "MAIN:$err" if $opt{'v'};
			&db::Insert('events','level,time,source,info,class',"200,$now,'NeDi','$err','bugn'");
		}else{
			print "MAIN:No threads, set 1st at ".localtime($now)."\n" if $opt{'v'};
		}
		&db::Update('system',"value=1","name='threads'");					# Register first thread
		&db::Update('system',"value=$now","name='first'");
	}
	print "\nDiscovery ($VERSION) $misc::ncmd  at ". localtime($now)."\n";
	print "-------------------------------------------------------------------------------\n";
	print "Device				Status				Todo/Done-Time\n";
	print "===============================================================================\n";
	while ($#misc::todo ne "-1"){
		my $id = shift(@misc::todo);
		&misc::Discover($id);

		if($opt{'d'} ){
			print "-------------------------------------------------------------------------------\n";
			system('top -bC -g perl');
			if( $dsok ){
				print "\%dev\t\t".&misc::DecFix( total_size(\%dev) )."\n";
				print "\%int\t\t".&misc::DecFix( total_size(\%int) )."\n";
				print "\%mod\t\t".&misc::DecFix( total_size(\%mod) )."\n";
				print "\%vlan\t\t".&misc::DecFix( total_size(\%vlan) )."\n";
				print "\%net\t\t".&misc::DecFix( total_size(\%net) )."\n";

				print "\%misc::sysobj\t".&misc::DecFix( total_size(\%misc::sysobj) )."\n";
				print "\%misc::ifmac\t".&misc::DecFix( total_size(\%misc::ifmac) )."\n";
				print "\%misc::ifip\t".&misc::DecFix( total_size(\%misc::ifip) )."\n";
				print "\%misc::useip\t".&misc::DecFix( total_size(\%misc::useip) )."\n";
				print "\%misc::oui\t".&misc::DecFix( total_size(\%misc::oui) )."\n";
				print "\%misc::arp\t".&misc::DecFix( total_size(\%misc::arp) )."\n";
				print "\%misc::arp6\t".&misc::DecFix( total_size(\%misc::arp6) )."\n";
				print "\%misc::arpc\t".&misc::DecFix( total_size(\%misc::arpc) )."\n";
				print "\%misc::arpn\t".&misc::DecFix( total_size(\%misc::arpn) )."\n";
				print "\%misc::portprop\t".&misc::DecFix( total_size(\%misc::portprop) )."\n";
				print "\%misc::portnew\t".&misc::DecFix( total_size(\%misc::portnew) )."\n";
				print "\%misc::portdes\t".&misc::DecFix( total_size(\%misc::portdes) )."\n";
				print "\%misc::vlid\t".&misc::DecFix( total_size(\%misc::vlid) )."\n";

				print "\@misc::todo\t".&misc::DecFix( total_size(\@misc::todo) )."\n";
				print "\@misc::donenam\t".&misc::DecFix( total_size(\@misc::donenam) )."\n";
				print "\@misc::doneid\t".&misc::DecFix( total_size(\@misc::doneid) )."\n";
				print "\@misc::doneip\t".&misc::DecFix( total_size(\@misc::doneip) )."\n";
				print "\@misc::failid\t".&misc::DecFix( total_size(\@misc::failid) )."\n";
				print "\@misc::failip\t".&misc::DecFix( total_size(\@misc::failip) )."\n";
			}
		}

		last if defined $opt{'l'} and scalar(@misc::donenam) >= $opt{'l'};			# A limit was specified and we hit it...
	}
	print "===============================================================================\n";
	my $ndev = scalar @misc::donenam;
	my $nnod = 0;
	if ($ndev){
		&misc::Prt("MAIN:$ndev devices discovered\n");
		&misc::StorVar() if ($opt{'d'});

		undef %misc::map;
		if ($opt{'t'}){										# We're only testing
			unless($opt{'S'} =~ /f/ and $opt{'S'} =~ /a/){
				&misc::BuildArp();
				&db::ReadNod();
				&misc::BuildNod();
			}
		}else{
			unless($opt{'S'} =~ /f/ and $opt{'S'} =~ /a/){
				&misc::BuildArp();
				while( my $nlock = &db::Select('system','','value',"name='nodlock'") ){	# Wait until nodes are unlocked by a parallel thread;
					if( $nlock and kill 0,$nlock ){					# Registered thread and still running? (based on Steffen's idea)
						&misc::Prt("MAIN:Nodes already locked by PID $nlock, waiting for unlock\n","N");
						&db::Insert('events','level,time,source,info,class',"10,".time.",'NeDi',".$db::dbh->quote("PID $$ ($misc::ncmd) waiting for nodes unlock (locked by PID $nlock)").",'bugx'") if $misc::notify =~ /x/;
						sleep $misc::pause;
					}else{
						&misc::Prt("MAIN:PID $nlock not running, unlocking nodes!\n");
						&db::Update('system',"value=0","name='nodlock'");
						&db::Insert('events','level,time,source,info,class',"200,".time.",'NeDi',".$db::dbh->quote("PID $$ ($misc::ncmd) forces unlock nodes (locked by PID $nlock, which is not running anymore)").",'bugn'");
					}
				}
				&db::Update('system',"value='$$'","name='nodlock'");			# Set node lock in system table...
				&misc::Prt("MAIN:Nodes table locked at ".localtime(time)." by PID $$\n","Building nodes\n");
				&db::Insert('events','level,time,source,info,class',"10,".time.",'NeDi',".$db::dbh->quote("PID $$ ($misc::ncmd) locks nodes").",'bugx'") if $misc::notify =~ /x/;
				&db::ReadNod();
				$nnod = &misc::BuildNod();
				&db::WriteNod();
				&db::Update('system','value=0',"name='nodlock'");			# ...unlock them again
				&misc::Prt("\nMAIN:Nodes table unlocked at ".localtime(time)." by PID $$\n"," done\n");
				&db::Insert('events','level,time,source,info,class',"10,".time.",'NeDi',".$db::dbh->quote("PID $$ ($misc::ncmd) unlocks nodes").",'bugx'") if $misc::notify =~ /x/;
				&misc::Prt("MAIN:$misc::ipchg IP and $misc::ifchg IF changes detected\n") if ($misc::ipchg or $misc::ifchg);
			}
			my $nthrd = &db::Select('system','','value',"name='threads'");
			&misc::Prt("MAIN:$nthrd threads running right now\n");
			&misc::TopRRD() if $nthrd == 1 and $opt{'S'} !~ /o/;
			&mon::AlertFlush("Discovery Notification",$misc::mq);
		}
	}else{
		print "Nothing discovered, nothing written...\n";
	}
	my $casval = ($misc::backend eq 'Pg')?'cast(value as int)':'value';
	my $disdur = int((time - $now)/60);
	&db::Update('system',"value=$casval-1","name='threads'") if !$opt{'t'};				# Deregister thread, if not testing
	&db::Insert('events','level,time,source,info,class',"10,".time.",'NeDi','PID $$ ($misc::ncmd) ran for $disdur minutes, discovered $nnod nodes, ".scalar(@misc::donenam)." devices and failed on ".scalar(@misc::failip)."','bugx'") if $misc::notify =~ /x/;
	&db::Commit();
	&db::Disconnect();
	print "END :Took $disdur minutes\n\n";

	&misc::Prt("\%nod\t".&misc::DecFix( size(\%nod) )."\n") if $dsok and $opt{'d'};
}

=head2 FUNCTION HELP_MESSAGE()

Display some help

B<Options> -

B<Globals> -

B<Returns> -

=cut
sub HELP_MESSAGE(){
	print "\n";
	print "usage: nedi.pl [-i|-D|-t|-w|-y|-s|] <more option(s)>\n";
	print "Discovery Options  --------------------------------------------------------\n";
	print "-a ip	Add single device or ip range (e.g. 10.10.10)\n";
	print "-A cond	Add devices from DB cond=all or e.g.\"loc LIKE 'here%'\"\n";
	print "-t ip	Test IP only, but don't write anything\n";
	print "-c cmty	Prefer this community over those in nedi.conf\n";
	print "-p	Discover LLDP,CDP,FDP or NDP neighbours\n";
	print "-o	OUI discovery (based on ARP chache entries)\n";
	print "-r	Route table discovery (on L3 devices)\n";
	print "-u file	Use specified seedlist\n";
	print "-U file	Use specified configuration\n";
	print "-l x	Limit discovery to x devices\n";
	print "Actions -------------------------------------------------------------------\n";
	print "-b|Bx	Backup running config|also write files, delete old versions if > x\n";
	print "-S opt	Skip s=sys v=vlans m=modules g=devrrd a=arp f=fwd p=dprot o=toprrd\n";
	print "	W=web j=addr i=IFinfo t=trf e=err d=dscard b=bcast w=poe A=adm O=op \n";
	print "-f 	Use discovered IPs as devicenames (useful, if they're not unique)\n";
	print "-F 	Use FQDNs for devices. Allows \".\" in dev names (can mess up links!)\n";
	print "-n 	Don't resolve node names via DNS\n";
	print "-N 	Don't exclude devices from nodes\n";
	print "-W 	Retry SNMP writeaccess\n\n";
	print "Other Options -------------------------------------------------------------\n";
	print "-i u pw	Initialize database (-I deletes all RRDs & Configs in addition)\n";
	print "-k	Store ssh key in ~/.ssh/known_hosts\n";
	print "-K	Delete stored key from ~/.ssh/known_hosts\n";
	print "-s Xopt	Scan nodes, X=Tcp,Udp,Os,Fast opt=update or ip=(ip) e.g. TUFupdate\n";
	print "-d|D	Store internal variables and write to CLI logs|skip to debug mode\n";
	print "-v	Verbose output\n";
	print "-y	Show supported devices based on .def files (in sysobj)\n\n";
	print "Output Legend -------------------------------------------------------------\n";
	print "Statistics (lower case letters):\n";
	print "i#	Interfaces\n";
	print "j#	IF IP addresses\n";
	print "a#	ARP entries\n";
	print "f#	Forwarding entries\n";
	print "m#	Modules\n";
	print "v#	Vlans\n";
	print "c#	Config lines\n";
	print "pro x/y	Discovery protocol, route or OUI queueing (# added/# done already)\n";
	print "b#	Border hits\n\n";
	print "Warnings (upper case letters) -------------------------------------------\n";
	print "Jx	IF Addresses (a=IP m=Mask 6=ipv6 c=cisco-ip6 p=prefix v=vrf)\n";
	print "Ax	ARP (n=net2media p=net2phys 6=ipv6 c=cisco-ip6 i=no IF index)\n";
	print "Bx	Backup f=fetched n=new u=update w=write e=empty d=delete\n";
	print "Cx	CLI main c=connect p=pw e=enable i=impossible m=cmd\n";
	print "Dx	Discover p=poe a=IP l=LLDP c=CDP f=FDP r=rte o=IP127/0 L=loop x=IFix\n";
	print "	d=dupnbr n=no nbrname\n";
	print "Fx(#)	Forwarding table (i=IF p=Port #=vlan x=idx w=wlan)\n";
	print "Ix	Interface d=desc n=name t=type S/s=HC/speed m=mac a=admin p=oper,\n";
	print "	c=dscard b=bcast I/O=HCtrf i/o=trf e=err l=alias x=duplex v=vlan w=poe\n";
	print "Kx	SSH keyscan e=error, s=scanned, r=removed\n";
	print "Mx	Modules t=slot d=desc c=class h=hw f=fw s=sw #=SN m=model\n";
	print "Px	CLI prep p=prev err u=no user n=user not in conf\n";
	print "Rx	RRD d=mkdir u=update s=make sys i=make IF t=make top n=IF name\n";
	print "Sx	System w=write n=name c=con l=Loc v=SRV #=SN b=BI g=grp o=mode\n";
	print "	v=vlan y=type f=cfg w=PoE RRD:u=CPU m=Mem t=Tmp s=cus\n";
	print "---------------------------------------------------------------------------\n";
	print "NeDi $VERSION (C) 2001-2013 Remo Rickli & contributors\n\n";
	exit;
}
