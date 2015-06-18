#!/usr/bin/perl
=pod

=head1 PROGRAM trap.pl

A simple SNMP Trap handler for NeDi.

=head2 DESCRIPTION

Put this in /etc/snmp/snmptrapd.con:
disableAuthorization yes # optional, if traps are rejected
traphandle      /nedipath/trap.pl

Start snmptrapd (e.g. using System-Services). Incoming traps will be added to Monitoring-Events.

Upon receiving a trap, the script will check whether a device with the source IP exists. The default level will be set to 50 if it does (10 if not).

The script conaints some basic mappings to further raise authentication and configuration related events. Look at the source, if you want to add more mappings. Trap handling has not been further pursued in favour of syslog messages.

Test with: echo Test\\n1.2.3.4\\nThis is a test\\nLooking good\\nI think!|/var/nedi/trap.pl

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

use vars qw($p $now $info %mon %srcna %opt);
$now = time;

$opt{'d'} = 1;
$opt{'v'} = 1;

$p = $0;
$p =~ s/(.*)\/(.*)/$1/;
if($0 eq $p){$p = "."};

$misc::dbname = $misc::dbhost = $misc::dbuser = $misc::dbpass = '';

require "$p/inc/libmisc.pm";										# Use the miscellaneous nedi library
&misc::ReadConf();
require "$p/inc/libdb.pm";										# Use the DB function library
require "$p/inc/libmon.pm";										# Use the SNMP function library
my $now = time;

# process the trap:
my $src = <STDIN>;
chomp($src);
my $ip = <STDIN>;
chomp($ip);
$ip =~ s/UDP:\s?\[([0-9.]+)\]:.*/$1/;

while(<STDIN>) {
	chomp;
	$info .= ', '.$_;
}
$info =~ s/[^\w\t\/\Q(){}[]!@#$%^&*-+=',.:<>? \E]//g;							# Remove unwanted characters
$info =~ s/.*snmpTrapOID.0//;										# Cut before TrapOID

my $level = 10;
&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass,1);
&db::ReadMon( &misc::Ip2Dec($ip) );
&db::ReadUser("groups & 8 = 8 AND (phone != '' OR email != '')");

if(exists $srcna{$ip}){											# Source IP lookup (%srcna created by db::readmon)
	my $tgt = $srcna{$ip};
	$level = 50;

# TODO remove this legacy and put meaningful actions?
	if($info =~ s/IF-MIB::ifIndex/Ifchange/){
		$level = 150;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.45.1.6.4.3.5.1.0/Baystack Auth/){
		$level = 150;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.2.9.3.1.1.1.1/Cisco Auth/){
		$level = 150;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.2.1.5.0/Cisco Auth Failure!/){
		$level = 150;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.2.9.3.1.1.2.1/Cisco TCPconnect/){
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.9.43/IOS Config change/){
		$level = 100;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.5.1.1.28/CatOS Config change/){
		$level = 100;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.9.46/Cisco VTP/){
	}
	my $mq = &mon::Event(1,$level,'trap',$tgt,$tgt,"$info","$info");
	&mon::AlertFlush("NeDi Trap Forward for $tgt",$mq);
}else{
	&db::Insert('events','level,time,source,info,class',"$level,$now,'$ip','".substr($info,2)."','trap'");
}
&db::Disconnect();
