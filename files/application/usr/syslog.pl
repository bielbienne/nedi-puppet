#!/usr/bin/perl
=pod

=head1 PROGRAM syslog.pl

Simple syslog daemon, which stores events directly in DB. Only monitored
targets receive classification of their events. They are forwarded
via mail or ignored completely depending on the settings in
Monitoring-Setup.

=head1 SYNOPSIS

syslog.pl [-D -v -p<port> -U<config>]

=head2 DESCRIPTION

Incoming messages are translated as follows:

Sev.  Level     Comment

0     Emergency (250) -

1,2   Alert     (200) -

3     Warning   (150) -

4     Notice    (100) -

x     Info      (50) Default for monitored targets

x     Other     (10) Default for any other IP

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

use strict;
use warnings;

use IO::Socket;
use Getopt::Std;

use vars qw($p $warn $now %opt %mon %srcna %usr);
$misc::pause = "";											# Avoid 'used only once:' warning without breaking evals (like LWP in libweb)

getopts('Dvp:U:',\%opt)  || &HELP_MESSAGE;

select(STDOUT); $| = 1;											# Disable buffering

$now = time;
$p   = $0;
$p   =~ s/(.*)\/(.*)/$1/;
if($0 eq $p){$p = "."};

$misc::dbname = $misc::dbhost = $misc::dbuser = $misc::dbpass = '';

require "$p/inc/libmisc.pm";										# Use the miscellaneous nedi library
require "$p/inc/libmon.pm";										# Use the SNMP function library
require "$p/inc/libdb.pm";										# Use the DB function library

&misc::ReadConf();

if ($opt{'D'}) {
	&misc::Daemonize;
}
my $maxlen	= 512;
my $port	= ($opt{'p'})?$opt{'p'}:514;
my $desup	= time;

&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass,1);
my $ntgt	= &mon::InitMon();

my $sock = IO::Socket::INET->new(LocalPort => $port, Proto => 'udp') or die "socket: $@";
&misc::Prt("Awaiting syslog events on port $port\n");
while($sock->recv(my $info, $maxlen)) {
	$now = time;
	my($client_port, $client_ip) = sockaddr_in($sock->peername);
	my $ip = inet_ntoa($client_ip);

# TODO put some aggregation here -> while(1) then recv with timeout instead and put Event?, Alertflush here and call every 10s or so...
	&Process($ip,$info);

	if($now - $misc::pause > $desup){								# update targets if older than a monitoring cycle, after processing current event
		$desup = $now;
		my $ntgt = &mon::InitMon();
	}
}
&db::Disconnect();
die "recv: $!";

=head2 FUNCTION Process()

Process Message

B<Options> source IP, message

B<Globals> -

B<Returns> -

=cut
sub Process {

	my ($src,$raw) = @_;
	my $info = $raw;
	my $pri  = $raw;
	my $level = 10;

	$info =~ s/<(\d+)>(.*)/$2/;
	$info =~ s/[^\w\t\/\Q(){}[]!@#$%^&*-+=",.:<>? \E]//g;
	$info = substr($info,0,255);

	if(exists $srcna{$src}){									# Source IP is monitored
		$src = $srcna{$src};
		$pri =~ s/<(\d+)>.*/$1/;
		if($pri !~ /^\d+$/){
			&misc::Prt("PRI : Is $pri in $raw\n");
			$pri = 7;
		}
		my $sev = ($pri & 7);
		if   ($sev == 4)	{$level = 100}
		elsif($sev == 3)	{$level = 150}
		elsif($sev =~ /[12]/)	{$level = 200}
		elsif($sev =~ /[0]/)	{$level = 250}
		else			{$level = 50}

		my $mq = &mon::Event(1,$level,$mon{$src}{cl},$src,$mon{$src}{dv},$info);
		&mon::AlertFlush("NeDi Syslog Forward for $src",$mq);
	}else{
		&misc::Prt("PROC:$src ($_[0])\tL:$level ($pri)\nMESG:$info\n");
		&db::Insert('events','level,time,source,info,class',"$level,$now,'$src','$info','ip'");
	}
}


=head2 FUNCTION HELP_MESSAGE()

Display some help

B<Options> -

B<Globals> -

B<Returns> -

=cut
sub HELP_MESSAGE {
	print "\n";
	print "usage: syslog.pl <Option(s)>\n\n";
	print "---------------------------------------------------------------------------\n";
	print "Options:\n";
	print "-D		daemonize moni.pl\n";
	print "-v		verbose output\n";
	print "-p x		listen on port x (default 514)\n\n";
	print "-U file	Use specified configuration\n";
	print "syslog (C) 2001-2013 Remo Rickli (and contributors)\n\n";
	die;
}
