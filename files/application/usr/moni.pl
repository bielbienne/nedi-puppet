#!/usr/bin/perl

=pod

=head1 PROGRAM moni.pl

Monitoring daemon for polling uptime and checking connectivity of
services (not threaded for now, thus consider bigger pause, if you
monitor many targets). Targets will be skipped if it can't be
contacted (missing IP, doesn't exist in nodes or devices etc.) or if
a dependency is down.

=head1 SYNOPSIS

moni.pl [-D -v -d<level>]

=head2 DESCRIPTION

=head2 LICENSE

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

=head2 AUTHORS

Remo Rickli & NeDi Community

Visit http://www.nedi.ch for more information.

=cut

use strict;
use warnings;
no warnings qw(once);

use Getopt::Std;
use Net::SNMP qw(ticks_to_time);
use Net::Ping;

use vars qw($dnsok $ntpok $now $warn $p $mq $ttg $tts %opt %dev %usr %mon %depdevs %depdown %depcount %msgq);

#my %response = get_ntp_response('localhost');
#use Data::Dumper;
#&misc::Prt(' '.Dumper(%response)."\n");

getopts('Dd:v',\%opt) || &HELP_MESSAGE;

$now = time;
$p   = $0;
$p   =~ s/(.*)\/(.*)/$1/;
if($0 eq $p){$p = "."};
require "$p/inc/libmisc.pm";										# Include required libraries
require "$p/inc/libsnmp.pm";
require "$p/inc/libmon.pm";
require "$p/inc/libdb.pm";										# Use the DB function library

&misc::ReadConf();

$dnsok = 0;
eval 'use Net::DNS::Resolver;';
if ($@){
	&misc::Prt("PERL:Net::DNS::Resolver not available\n");
}else{
	$dnsok = 1;
	&misc::Prt("PERL:Net::DNS::Resolver loaded\n");
}

$ntpok = 0;
eval 'use Net::NTP;';
if ($@){
	&misc::Prt("PERL:Net::NTP not available\n");
}else{
	$ntpok = 1;
	&misc::Prt("PERL:Net::NTP loaded\n");
}

$misc::lwpok = 0;
require "$p/inc/libweb.pm";

if ($opt{'d'}){												# Creates incidents and bails
	&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass);
	my $ntgt = &mon::InitMon();
	my @tgts = keys %mon;
	if( scalar @tgts ){
		my $t  = pop @tgts;
		&db::Insert('incidents','level,name,deps,startinc,endinc,usrname,time,grp,comment,device',"$opt{d},'$t',0,$now,0,'',0,1,'','$mon{$t}{dv}'");
		&db::Insert('incidents','level,name,deps,startinc,endinc,usrname,time,grp,comment,device',"$opt{d},'$t',0,".($now+1).",".($now+$misc::pause).",'',0,1,'','$mon{$t}{dv}'");
		my $mq = &mon::Event(7,$opt{d},'moni',$t,$mon{$t}{dv},"test alert (device $mon{$t}{dv})","SMS test");
		my $af = &mon::AlertFlush("Monitoring Test",$mq);
		&db::Commit();
	}else{
		&misc::Prt("ERR :Need at least one target in monitoring!\n");
	}
	&db::Disconnect();
	exit;
}elsif ($opt{'D'}) {											# Daemonize or...
	&misc::Daemonize;
}else{
	select(STDOUT); $| = 1;										# ...disable buffering.
}

while(1){# TODO support dependency aware threading for better scalability!

	$now = time;
	$mq  = 0;
	$ttg = 0;
	&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass);
	my $ntgt = &mon::InitMon();
	&misc::Prt("\nInitializing " . localtime($now) . " --------------\n");
	foreach my $d (keys %mon){
		&misc::Prt(sprintf ("DEPS:%-10.10s = %-8.8s ", $d, $mon{$d}{dy}) );
		if( $mon{$d}{dy} ne '-' ){								# Dependency configured?
			if( exists $mon{$mon{$d}{dy}} ){						# Does it exist?
				push @{$mon{$mon{$d}{dy}}{da}},$d;					# Add to parent dependendants
			}else{
				&db::Update('monitoring',"depend='-'","name ='$d'");
				&db::Insert('events','level,time,source,info,class,device',"50,$now,'$d','Non existant dependency $mon{$d}{dy} removed.','moni','$mon{$d}{dv}'");
				&misc::Prt(" doesn't exist!");
			}
		}
		&misc::Prt("\n");
		$ttg++;
	}

	&misc::Prt("\nBuilding Tree ----------------------------------\n");
	foreach my $d (keys %mon){									# Recursively count all dependants
		&misc::Prt(sprintf ("TREE:%-12.12s", $d) );
		$mon{$d}{dc} = &CountDep($d,0);
		&misc::Prt(" Deps=$mon{$d}{dc}\n");
	}

	$tts = 0;
	&misc::Prt("\nTesting $ntgt targets in total, Pause ${misc::pause}s ---------------\n");
	foreach my $t (sort { $mon{$b}{dc} <=> $mon{$a}{dc} } keys %mon){				# Check sorted by # of dependants (check most important ones first)
		if($mon{$t}{ty} eq 'NeDi Agent'){
			&misc::Prt("SKIP:NeDi Agent $t is handled by master.pl!\n");
		}else{
			TestTgt($t);
			select(undef, undef, undef, 0.01);						# Wait a msec... (TODO until threading is implemented)
			$tts++;
		}
	}

	&mon::AlertFlush("Monitoring Alert",$mq);
	&misc::Prt("===============================================================================\n");
	my $took = time - $now;
	if ($misc::pause > $took){
		my $sl = $misc::pause - $took;
		&misc::Prt("$tts tests on $ttg targets took ${took}s, sleeping ${sl}s\n\n");
		&db::Commit();
		&db::Disconnect();									# Disconnect DB before sleep, TODO more efficient to stay connected?
		my $slept = sleep($sl);
		&misc::Prt("Paused ${slept}s, why am I doing this?\n\n") if $slept > $sl;		# VM seemed to have slept longer, TOOD remove if proven wrong...
	}else{
		&db::Insert('events','level,time,source,info,class',"150,$now,'NeDi','Monitoring took ${took}s, increase pause!','moni'");
		&misc::Prt("tts tests on $ttg targets took ${took}s, no time to pause!\n\n");
		&db::Commit();
		&db::Disconnect();
	}
}

=head2 FUNCTION TestTgt()

Perform actual test on target

B<Options> target name

B<Globals> -

B<Returns> -

=cut
sub TestTgt{

	my ($d) = @_;
	my $latency = my $uptime = 0;

	&misc::Prt(sprintf ("\nTRGT:%-12.12s Deps=%-4.4s Test=%-6.6s\n", $d, $mon{$d}{dc}, $mon{$d}{te}) );
	if($mon{$d}{ds} ne 'up'){									# Check if dep is up
		&misc::Prt("SKIP:Deps=$mon{$d}{ds}($mon{$d}{dc})\n");
		return;
	}elsif($mon{$d}{te} eq "ping"){
		$latency = &mon::PingService($mon{$d}{'ip'});
	}elsif($mon{$d}{te} =~ /^dns$/ and $mon{$d}{to}){
			if($main::dnsok){
				my $start = Time::HiRes::time;
				my $res = Net::DNS::Resolver->new(nameservers => [qw($mon{$d}{ip})]);
				my $query = $res->search($mon{$d}{to});
				foreach my $rr ($query->answer) {
					next unless $rr->type eq "A";
					my $rip = $rr->address;
					if( $rip =~ /$mon{$d}{tr}/){
						$latency = int(1000 * (Time::HiRes::time - $start) );
						&misc::Prt("DNS :Latency=${latency}ms Reply to $mon{$d}{to} is $rip and matches /$mon{$d}{tr}/\n");
					}else{
						$latency = -1;
						&misc::Prt("DNS :Reply to $mon{$d}{to} is $rip and does not match /$mon{$d}{tr}/\n");
					}
				}
				}else{
					&misc::Prt("ERR :Net::DNS::Resolver not available!\n");
					$latency = -1;
				}
	}elsif($mon{$d}{te} =~ /^ntp$/){
			if($main::ntpok){
				my $start = Time::HiRes::time;
				my %res = ();
				eval{
					%res = &main::get_ntp_response( $mon{$d}{ip} );
				};
				if( $@ ){
					$latency = -1;
					&misc::Prt("NTP :$@\n");
				}elsif( $res{$mon{$d}{to}} =~ /$mon{$d}{tr}/){
					$latency = int(1000 * (Time::HiRes::time - $start) );
					&misc::Prt("NTP :Latency=${latency}ms Reply to $mon{$d}{to} is $res{$mon{$d}{to}} and matches /$mon{$d}{tr}/\n");
				}else{
					$latency = -1;
					&misc::Prt("NTP :Reply to $mon{$d}{to} is $res{$mon{$d}{to}} and does not match /$mon{$d}{tr}/\n");
				}
			}else{
				&misc::Prt("ERR :Net::NTP not available!\n");
				$latency = -1;
			}
	}elsif($mon{$d}{te} =~ /^(http|https)$/ and $mon{$d}{to}){
			if($web::lwpok){
				my $start = Time::HiRes::time;
				my $res = &web::GetHTTP($mon{$d}{ip},$mon{$d}{te},$mon{$d}{to});
				if($res =~ /$mon{$d}{tr}/){
					$latency = int(1000 * (Time::HiRes::time - $start) );
					&misc::Prt("WEB :Latency=${latency}ms Reply (${latency}ms) to $mon{$d}{to} is $res and matches /$mon{$d}{tr}/\n");
				}else{
					$latency = -1;
					&misc::Prt("WEB :Reply to $mon{$d}{to} does not match $mon{$d}{tr}\n");
				}
			}else{
				&misc::Prt("ERR :LWP not available!\n");
				$latency = -1;
			}
	}elsif($mon{$d}{te} =~ /^(http|https|telnet|ssh|mysql|cifs)$/){
		$latency = &mon::PingService($mon{$d}{'ip'},'tcp',$mon{$d}{te});
	}elsif($mon{$d}{te} eq 'uptime'){
		($latency, $uptime) = &mon::GetUptime($mon{$d}{'ip'},$mon{$d}{'rv'},$mon{$d}{'rc'});
		if( $mon{$d}{up} > 4294900000 ){							# Ignore alleged reboot, due to 32bit overflow
			$mq += &mon::Event(1,100,'moni',$d,$mon{$d}{dv},'Was up for '.ticks_to_time($mon{$d}{up}).', ignoring uptime due to potential overflow');
		}elsif( $latency != -1 and $mon{$d}{up} > $uptime  ){
			$mq += &mon::Event($mon{$d}{al},150,'moni',$d,$mon{$d}{dv},'Rebooted '.ticks_to_time($uptime).' ago! Was up for '.ticks_to_time($mon{$d}{up}),'Rebooted!');
		}
	}else{
		&misc::Prt("SKIP:No test configured...\n");
		return;
	}
	if($latency != -1){
		my $ok = ++$mon{$d}{ok};
		my $latmax = ($latency > $mon{$d}{lm})?$latency:$mon{$d}{lm};				# Update max if higher than previous
		my $latavg = sprintf("%.0f",( ($ok - 1) * $mon{$d}{la} + $latency)/$ok);		# This is where school stuff comes in handy (sprintf to round)

		&db::Update('monitoring',"status=0,lastok=$now,uptime=$uptime,ok=$ok,latency=$latency,latmax=$latmax,latavg=$latavg","name ='$d'");
		&misc::Prt("UP  :");
		&MarkDep($d,'up',0);									# Mark everytime to avoid errors when moni is restarted
		if($mon{$d}{st} >= $mon{$d}{nr}){
			my $msg = "recovered".(($mon{$d}{dc})?", affects $mon{$d}{dc} more targets!":"");
			my $dnt  = sprintf("was down for %.1fh", $mon{$d}{st}*$misc::pause/3600);
			&db::Update('incidents',"endinc=$now","name ='$d' AND endinc=0");
			$mq += &mon::Event($mon{$d}{al},50,'moni',$d,$mon{$d}{dv},"$msg, $dnt",$msg);
		}else{
			&misc::Prt("Last status=$mon{$d}{st}\n");
		}
		&db::Insert('events','level,time,source,info,class,device',"'150',$now,'$d','Latency ${latency}ms exceeds threshold of $mon{$d}{lw}ms','moni','$mon{$d}{dv}'") if($latency > $mon{$d}{lw});
	}else{
		my $st = ++$mon{$d}{st};
		my $lo = ++$mon{$d}{lo};
		&db::Update('monitoring',"status=$st,lost=$lo","name ='$d'");
		&MarkDep($d,'down',0);									# Mark everytime to avoid errors when moni is restarted

		my $lvl = 200;
		my $msg = "is down";
		if($mon{$d}{dc}){
			$lvl = 250;
			$msg .= ", affects $mon{$d}{dc} more targets!";
		}
		if($mon{$d}{st} == $mon{$d}{nr}){
			&db::Insert('incidents','level,name,deps,startinc,endinc,usrname,time,grp,comment,device',"$lvl,'$d',$mon{$d}{dc},$now,0,'',0,1,'','$mon{$d}{dv}'");
			$mq += &mon::Event($mon{$d}{al},$lvl,'moni',$d,$mon{$d}{dv},$msg,$msg);
			&misc::Prt("DOWN:For $mon{$d}{nr} times, generated $mq alerts\n");
		}elsif( !($mon{$d}{st} % 100) and $mon{$d}{al} & 128){					# Keep nagging every 100th time, if enabled
			$msg .= " (unreachable for $mon{$d}{st} times)";
			$mq += &mon::Event($mon{$d}{al},$lvl,'moni',$d,$mon{$d}{dv},$msg);
			&misc::Prt("DOWN:$mon{$d}{al} For $mon{$d}{st} times, generated $mq nags\n");
		}else{
			&misc::Prt("DOWN:$mon{$d}{al} For $mon{$d}{st} times\n");
		}
	}
}

=head2 FUNCTION MarkDep()

Recursively mark dependendants

B<Options> target name, up/down, iteration

B<Globals> main::depdown

B<Returns> -

=cut
sub MarkDep{

	my ($d, $stat, $iter) = @_;

	if($iter < 90 and exists $mon{$d}{da} ){
		foreach my $d (@{$mon{$d}{da}}){
#			&misc::Prt(" $d");
			$mon{$d}{ds} = $stat;
			&MarkDep($d,$stat,$iter+1);
		}
#	}else{
#			&misc::Prt("(NoDeps)");
	}
}


=head2 FUNCTION CountDep()

Recursively count dependants. If you see perl warnings about deep
recursion, you should look for loops in your dependecy settings.

B<Options> target, iteration

B<Globals> -

B<Returns> # of dependants

=cut
sub CountDep{

	my ($d, $iter) = @_;

	if($iter < 90){
		if(exists $mon{$d}{da} ){
			my $c = scalar @{$mon{$d}{da}};
			&misc::Prt(" I=$iter:$d+$c");
			foreach my $d (@{$mon{$d}{da}}){
				$c += &CountDep($d,$iter+1);
			}
			return $c;
		}else{
			return 0;
		}
	}else{
		&misc::Prt(" Dependency Loop ","DL ");
		return 0;
	}

}

=head2 FUNCTION HELP_MESSAGE()

Display some help

B<Options> -

B<Globals> -

B<Returns> -

=cut
sub HELP_MESSAGE{
	print "\n";
	print "usage: moni.pl <Option(s)>\n\n";
	print "---------------------------------------------------------------------------\n";
	print "Options:\n";
	print "-d <lev>	debug with level (creates mail and SMS if set, an event and 2 incidents\n";
	print "-v		verbose output\n";
	print "-D		daemonize moni.pl\n\n";
	print "(C) 2001-2013 Remo Rickli (and contributors)\n\n";
	exit;
}
