=pod

=head1 LIBRARY
libcli-iopty.pm

Net::Telnet/IO::Pty based Functions

Only needs libio-pty-perl and relies on the ssh binary for secured connections

Ubuntu install hint: apt-get install libio-pty-perl

PrepDev() tries to find credentials on new devices (or if cliport set to 0).
After that BridgeFwd() reads MAC table on supported switches if PrepDev()
confirmed support (only IOS at the time).

Config() fetches the configuration and stores the "interesting" part.
All functions use the universal EnableDev() to get into enable mode.
The $obj->get is used to avoid problems with ^M and Escape sequences...

-d option shows more details on errors and pre-match and actual matches and
also creates input.log and output.log (open extra terminals with tail -f
on them to see what's happening right away)

=head2 AUTHORS

Remo Rickli & NeDi Community

=cut

package cli;
use warnings;

use Net::Telnet;

eval 'use IO::Pty();';
if ($@){
	$misc::usessh = 'never';									# Keeps nedi working without io::pty
	&misc::Prt("CLI :IO-Pty not available\n");
}else{
	&misc::Prt("CLI :IO-Pty loaded\n");
}

use vars (%cmd);

=head2 Important Variables

$clipause: Increase if devices hang between connects


$cmd: Holds commands, expected prompts and other OS specific parameters needed to handle
CLI access.

=over

=item *
ropr: Read only prompt, if no readonly prompt is used set to some string which won't occur otherwhise.

=item *
enpr: Enable prompt

=item *
enab: Enable command

=item *
conf: Command to display config

=item *
ctim: Wait additional seconds for config (default 10)

=item *
strt: Match start of config (use a . to match anything)

=item *
page: Command to disable paging

=item *
dfwd: Show dynamic bridge forwarding table (only IOS & CatOS)

=item *
sfwd: Show static bridge forwarding table (only IOS & CatOS)

=item *
arp: Show arp table (ASA, Nexus)

=item *
wsnr: Command for wireless SNR levels (only IOS-ap, but not finished yet)

=back

=head2 Tips & Tricks

=head3 OS Modes


OS        Read    Enable        Config

=============================================

IOS       Name>   Name#         Name(config)#

CatOS     Name>   Name>(enable) -

Omnistack -       Name#         

ProCurve  Name>   Name#         Name(config)# 

Comware   <Name>  -             [Name]

JunOS     -       Name>         Name#

ESX       -       Name#         -

etc.

=head3 Enable VMware ESX support

Enable CDP on vSwitch1 (ESX name needs to be in DNS, if you wish to discover it!):

esxcfg-vswitch -B both vSwitch1

Enable SNMP:

vi /etc/vmware/snmp.xml

<enabled>true</enabled>

<communities>public</communities>

/sbin/services.sh restart

Edit $cmd{'ESX'}{'conf'} to adjust path to *.vmx files for backup.

=cut

our $clipause = 1;

#TODO assign the 'strt' strings Software|# What:| General System Information)|\*\*\* CORE|<config>|\sversion

# Alcatel-Lucent
$cmd{'Omnistack'}{'ropr'} = 'GitsDoNid';
$cmd{'Omnistack'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Omnistack'}{'enab'} = 'enable';
$cmd{'Omnistack'}{'conf'} = 'show configuration snapshot';
$cmd{'Omnistack'}{'strt'} = '.';

#Aruba
$cmd{'ArubaOS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'ArubaOS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'ArubaOS'}{'enab'} = 'enable';
$cmd{'ArubaOS'}{'conf'} = 'show running-config';
$cmd{'ArubaOS'}{'strt'} = '^version';
$cmd{'ArubaOS'}{'page'} = 'no paging';
$cmd{'ArubaOS'}{'dfwd'} = 'show mac-address-table';

# AVAYA
$cmd{'Nortel'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'Nortel'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Nortel'}{'enab'} = 'enable';
$cmd{'Nortel'}{'conf'} = 'show run';
$cmd{'Nortel'}{'strt'} = '.';
$cmd{'Nortel'}{'page'} = 'terminal length 0';

# BROCADE
$cmd{'Ironware'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'Ironware'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Ironware'}{'enab'} = 'enable';
$cmd{'Ironware'}{'conf'} = 'show run';
$cmd{'Ironware'}{'strt'} = '.';
$cmd{'Ironware'}{'page'} = 'skip-page-display';

# CIENA
$cmd{'LEOS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'LEOS'}{'enpr'} = '[\w+().-]+>\s?$';
$cmd{'LEOS'}{'conf'} = 'configuration show';
$cmd{'LEOS'}{'strt'} = '.';
$cmd{'LEOS'}{'page'} = 'system shell set more off';

$cmd{'SAOS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'SAOS'}{'enpr'} = '[\w+().-]+>\s?$';
$cmd{'SAOS'}{'conf'} = 'configuration show';
$cmd{'SAOS'}{'strt'} = '.';
$cmd{'SAOS'}{'page'} = 'system shell set more off';

# CISCO
$cmd{'IOS'}{'ropr'} = '[\w+().-]+>\s?$';								# New approach to avoid problems with # in banners
$cmd{'IOS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS'}{'enab'} = 'enable';
$cmd{'IOS'}{'conf'} = 'show run';
$cmd{'IOS'}{'ctim'} = 30;
$cmd{'IOS'}{'strt'} = '^Current';
$cmd{'IOS'}{'end'}  = '^end$';
$cmd{'IOS'}{'page'} = 'terminal length 0';
$cmd{'IOS'}{'dfwd'} = 'show mac address-table | e CPU|Switch|Router|/.*,';				# tx colejv
$cmd{'IOS'}{'tech'} = "show tech-support | redirect tftp://%NeDi%/%FILE%";

$cmd{'IOS-old'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'IOS-old'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS-old'}{'enab'} = 'enable';
$cmd{'IOS-old'}{'conf'} = 'show run';
$cmd{'IOS-old'}{'strt'} = '^Current';
$cmd{'IOS-old'}{'end'}  = '^end$';
$cmd{'IOS-old'}{'page'} = 'terminal length 0';
$cmd{'IOS-old'}{'dfwd'} = 'show mac-address-table dyn';							# Older IOS, tx Rufer & Eviltrooper
$cmd{'IOS-old'}{'sfwd'} = 'show port-security addr';							# tx Duane Walker

$cmd{'IOS-rtr'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'IOS-rtr'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS-rtr'}{'enab'} = 'enable';
$cmd{'IOS-rtr'}{'conf'} = 'show run';
$cmd{'IOS-rtr'}{'strt'} = '^Current';
$cmd{'IOS-rtr'}{'end'}  = '^end$';
$cmd{'IOS-rtr'}{'page'} = 'terminal length 0';
$cmd{'IOS-rtr'}{'dfwd'} = 'show mac-address-table dyn';							# Some router are like IOS-old
$cmd{'IOS-rtr'}{'sfwd'} = 'show port-security addr';							# tx Duane Walker

$cmd{'IOS-ap'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'IOS-ap'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS-ap'}{'enab'} = 'enable';
$cmd{'IOS-ap'}{'conf'} = 'show run';
$cmd{'IOS-ap'}{'strt'} = '^Current';
$cmd{'IOS-ap'}{'end'}  = '^end$';
$cmd{'IOS-ap'}{'page'} = 'terminal length 0';
$cmd{'IOS-ap'}{'dfwd'} = 'show bridge | exclude \*\*\*';
$cmd{'IOS-ap'}{'wsnr'} = 'show dot11 statistics client-traffic'; 					# Credits to HB9DDO

$cmd{'IOS-wlc'}{'ropr'} = '\(Cisco Controller\) >$';
$cmd{'IOS-wlc'}{'enpr'} = $cmd{'IOS-wlc'}{'ropr'};
$cmd{'IOS-wlc'}{'conf'} = 'show run-config commands';
$cmd{'IOS-wlc'}{'strt'} = '.';
$cmd{'IOS-wlc'}{'page'} = 'config paging disable';

$cmd{'IOS-pix'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'IOS-pix'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS-pix'}{'enab'} = 'enable';
$cmd{'IOS-pix'}{'conf'} = 'show run';
$cmd{'IOS-pix'}{'strt'} = '^PIX Version';
$cmd{'IOS-pix'}{'page'} = 'no pager';									# PIX 6.3
$cmd{'IOS-pix'}{'arp'} = 'show arp';

$cmd{'IOS-fw'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'IOS-fw'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS-fw'}{'enab'} = 'enable';
#$cmd{'IOS-fw'}{'conf'} = 'show run';
$cmd{'IOS-fw'}{'conf'} = 'more system:running-config';							# displays plain-text keys, tx uestueno
$cmd{'IOS-fw'}{'strt'} = '^PIX|ASA';
$cmd{'IOS-fw'}{'end'}  = '^: end$';
$cmd{'IOS-fw'}{'page'} = 'no terminal pager';								# PIX 8.0.3, ASA 8.2
$cmd{'IOS-fw'}{'arp'} = 'show arp';

$cmd{'IOS-fv'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'IOS-fv'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'IOS-fv'}{'enab'} = 'enable';
$cmd{'IOS-fv'}{'conf'} = 'show run';
$cmd{'IOS-fv'}{'strt'} = '^FWSM';
$cmd{'IOS-fv'}{'page'} = 'terminal pager 0';

$cmd{'IOS-css'}{'ropr'} = 'GitsDoNid';
$cmd{'IOS-css'}{'enpr'} = '/# $/';									# Thanks to kai
$cmd{'IOS-css'}{'conf'} = 'show run';
$cmd{'IOS-css'}{'strt'} = '^!Generated' ;

$cmd{'CatOS'}{'ropr'} = '(.+)>\s?$';
$cmd{'CatOS'}{'enpr'} = '(.+)>\s?\(enable\)\s?$';
$cmd{'CatOS'}{'enab'} = 'enable';
$cmd{'CatOS'}{'dfwd'} = 'show cam dyn';
$cmd{'CatOS'}{'conf'} = 'show conf';
$cmd{'CatOS'}{'strt'} = '^begin';
$cmd{'CatOS'}{'page'} = 'set length 0';

$cmd{'NXOS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'NXOS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'NXOS'}{'enab'} = 'enable';
$cmd{'NXOS'}{'dfwd'} = 'sh mac address-table dyn';
$cmd{'NXOS'}{'conf'} = 'show running-config';
$cmd{'NXOS'}{'strt'} = '^begin|running-config';
$cmd{'NXOS'}{'page'} = 'terminal length 0';
$cmd{'NXOS'}{'arp'} = 'sh ip arp vrf all';

# ENTERASYS
$cmd{'EOS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'EOS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'EOS'}{'enab'} = 'enable';
$cmd{'EOS'}{'conf'} = 'show run';
$cmd{'EOS'}{'strt'} = '!PLATFORM';
$cmd{'EOS'}{'page'} = 'terminal length 0';

# EXTREME
$cmd{'Xware'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'Xware'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Xware'}{'enab'} = 'enable';
$cmd{'Xware'}{'conf'} = 'show configuration';
$cmd{'Xware'}{'strt'} = 'Software Version';
$cmd{'Xware'}{'page'} = 'disable clipaging session';

$cmd{'XOS'}{'ropr'} = '[\w\s+().-]+>\s?$';
$cmd{'XOS'}{'enpr'} = '[\w\s+().-]+#\s?$';
$cmd{'XOS'}{'enab'} = 'enable';
$cmd{'XOS'}{'conf'} = 'show configuration';
$cmd{'XOS'}{'strt'} = '^#';
$cmd{'XOS'}{'page'} = 'disable clipaging';

# Fortigate
$cmd{'FortiOS'}{'ropr'} = 'GitsDoNid';
$cmd{'FortiOS'}{'enpr'} = '[\w+().-]+\$\s?$';
$cmd{'FortiOS'}{'conf'} = 'show full-configuration';
$cmd{'FortiOS'}{'strt'} = '^config';
$cmd{'FortiOS'}{'more'} = '--More-- ';

# JUNIPER
$cmd{'JunOS'}{'ropr'} = 'GitsDoNid';
$cmd{'JunOS'}{'enpr'} = '[\w+().-]+>\s?$';
$cmd{'JunOS'}{'enab'} = 'enable';
$cmd{'JunOS'}{'conf'} = 'show configuration | no-more';
#$cmd{'JunOS'}{'dfwd'} = 'show ethernet-switching table | no-more';					# Sneuser: for switches only
$cmd{'JunOS'}{'strt'} = '^## Last commit';

$cmd{'NetScreen'}{'ropr'} = 'GitsDoNid';
$cmd{'NetScreen'}{'enpr'} = '[\w+().-]+>\s?$';
$cmd{'NetScreen'}{'enab'} = 'enable';
$cmd{'NetScreen'}{'conf'} = 'get config';
$cmd{'NetScreen'}{'page'} = 'set console page 0';
$cmd{'NetScreen'}{'strt'} = '^Total';

# Hirschmann
$cmd{'Hirschmann'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'Hirschmann'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Hirschmann'}{'enab'} = 'enable';
$cmd{'Hirschmann'}{'conf'} = 'show run';
$cmd{'Hirschmann'}{'strt'} = '!Current';

# HP
$cmd{'Comwar3'}{'ropr'} = 'GitsDoNid';
$cmd{'Comwar3'}{'enpr'} = '[\w+()<\[.-]+[>\]]\s?(\n%.+)?$';						# Sends a % line after login prompt!
$cmd{'Comwar3'}{'enab'} = 'super';
$cmd{'Comwar3'}{'conf'} = 'display current';
$cmd{'Comwar3'}{'strt'} = '#';
$cmd{'Comwar3'}{'more'} = '  ---- More ----';								# Experimental, to get old 3Com switches backed up

$cmd{'Comware'}{'ropr'} = 'GitsDoNid';
$cmd{'Comware'}{'enpr'} = '[\w+()<\[.-]+[>\]]\s?$';
$cmd{'Comware'}{'enab'} = 'super';
$cmd{'Comware'}{'conf'} = 'display current';
$cmd{'Comware'}{'strt'} = '.';
$cmd{'Comware'}{'page'} = 'screen-length disable';

$cmd{'ProCurve'}{'ropr'} = '(\x1b\[[;\?0-9A-Za-z]+)+[\w+\s().-]+>\s?(\x1b\[[;\?0-9A-Za-z]+)+$';		# Match nasty Escapes! ProCurve names contain spaces by default, thus \s
$cmd{'ProCurve'}{'enpr'} = '(\x1b\[[;\?0-9A-Za-z]+)+[\w+\s().-]+#\s?(\x1b\[[;\?0-9A-Za-z]+)+$';
$cmd{'ProCurve'}{'enab'} = 'enable';
$cmd{'ProCurve'}{'conf'} = 'show run';
$cmd{'ProCurve'}{'strt'} = 'Configuration Editor; ';
$cmd{'ProCurve'}{'page'} = 'no page';
$cmd{'ProCurve'}{'tech'} = "copy command-output \"sh tech all\" tftp %NeDi% %FILE%";

$cmd{'MSM'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'MSM'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'MSM'}{'enab'} = 'enable';
$cmd{'MSM'}{'conf'} = 'show all conf';
$cmd{'MSM'}{'ctim'} = 120;
$cmd{'MSM'}{'strt'} = 'enable';

$cmd{'SROS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'SROS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'SROS'}{'enab'} = 'enable';
$cmd{'SROS'}{'conf'} = 'show run';
$cmd{'SROS'}{'strt'} = '.';
$cmd{'SROS'}{'page'} = 'terminal length 0';

$cmd{'TMS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'TMS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'TMS'}{'enab'} = 'enable';
$cmd{'TMS'}{'conf'} = 'show run';
$cmd{'TMS'}{'strt'} = '.';
$cmd{'TMS'}{'page'} = 'no page';

$cmd{'VC'}{'ropr'} = 'GitsDoNid';
$cmd{'VC'}{'enpr'} = '->\s?$';
$cmd{'VC'}{'enab'} = 'super';
$cmd{'VC'}{'conf'} = 'show config';
$cmd{'VC'}{'strt'} = '#';
$cmd{'VC'}{'end'}  = 'SUCCESS';

#HuaweiVRP
$cmd{'HuaweiVRP'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'HuaweiVRP'}{'enpr'} = '[\w+().-]+>\s?$';
$cmd{'HuaweiVRP'}{'enab'} = 'enable';
$cmd{'HuaweiVRP'}{'conf'} = 'display current';
$cmd{'HuaweiVRP'}{'strt'} = '#';
$cmd{'HuaweiVRP'}{'page'} = 'screen-length 0 temporary';

# LANCOM
$cmd{'LANCOM'}{'ropr'} = '>\s$';
$cmd{'LANCOM'}{'enpr'} = '>\s$';
$cmd{'LANCOM'}{'conf'} = 'readconfig';                                                  		# Backs up main config
$cmd{'LANCOM'}{'strt'} = '.';

# Maipu
$cmd{'Maipu'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'Maipu'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Maipu'}{'enab'} = 'enable';
$cmd{'Maipu'}{'conf'} = 'show run';
$cmd{'Maipu'}{'strt'} = '.';

# MIKROTIK
$cmd{'ROS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'ROS'}{'enpr'} = '[\w+().-]+>\s?$';
$cmd{'ROS'}{'conf'} = 'export';
$cmd{'ROS'}{'strt'} = '.';
$cmd{'ROS'}{'page'} = '';

# Netgear
$cmd{'Netgear'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'Netgear'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'Netgear'}{'enab'} = 'enable';
$cmd{'Netgear'}{'conf'} = 'show run';
$cmd{'Netgear'}{'strt'} = '^Current';
$cmd{'Netgear'}{'page'} = 'terminal length 0';
$cmd{'Netgear'}{'dfwd'} = 'show mac-addr-table all'; 
$cmd{'Netgear'}{'sfwd'} = 'show port-security all '; 

# Ruckus
$cmd{'ZDOS'}{'ropr'} = '[\w+().-]+>\s?$';
$cmd{'ZDOS'}{'enpr'} = '[\w+().-]+#\s?$';
$cmd{'ZDOS'}{'enab'} = 'enable';
$cmd{'ZDOS'}{'conf'} = 'show config';
$cmd{'ZDOS'}{'strt'} = '.';

# VMWARE
$cmd{'ESX'}{'ropr'} = 'GitsDoNid';
$cmd{'ESX'}{'enpr'} = '(.+?)#\s$';
#$cmd{'ESX'}{'conf'} = 'cat /etc/vmware/esx.conf';							# Backs up main config
$cmd{'ESX'}{'conf'} = 'for file in /vmfs/volumes/datastore1/*/*.vmx; do echo \#== $file ================================; cat $file; done';	# List each VM config
$cmd{'ESX'}{'strt'} = '.';


=head2 FUNCTION Connect()

Connects to a device using telnet or SSH and sets user if enable is ok

Verbose output is divided into different stages:

CLI|SSH|TEL:check transport & policy conformance

CLI0:connecting

CLI1:detect anykey then login prompt

CLI2:login prompt without anykey

CLI3:1st level login

CLI4:Enable required?

CLI5:Enable prompts for username

CLI6:Enable password with username

CLI7:Enable password without username

CLI8:Status feedback

B<Options> ip, port, user, os

B<Globals> -

B<Returns> session, status

=cut
sub Connect{

	my ($ip, $po, $us, $os) = @_;

	my ($session, $err, $pty, $pre, $match);
	my $next = $err = "";
	my $errmod = 0?'die':'return';									# set to 1 for debugging, if necessary
	my $inlog  = $main::opt{'d'}?'input.log':'';
	my $outlog = $main::opt{'d'}?'output.log':'';

	my ($realus,$usidx) = split(/;/,$us);								# This allows for multiple pw for same user indexed by ;x
	if($po == 22){
		my $known = "-o 'StrictHostKeyChecking no'";
		if($misc::usessh =~ /never/){
			&misc::Prt("CLI :ssh connection prohibited by usessh policy\n");
			return (undef, "connection prohibited by usessh policy");
		}elsif($misc::usessh =~ /known/){
			$known = "";
		}
		&misc::Prt("SSH :$us\@$ip:$po Tout:${misc::timeout}s OS:$os EN:$cmd{$os}{'enpr'}\n");
		$pty = &Spawn("ssh $known -l $realus $ip");
		$session = new Net::Telnet(	fhopen		=> $pty,
						Timeout		=> $misc::timeout + 4,			# Add 4s to factor in auth server and ssh on slow devs
						Prompt		=> "/$cmd{$os}{'enpr'}/",
						Output_log	=> $inlog,
						Input_log	=> $outlog,
						Telnetmode	=> 0,
						Cmd_remove_mode => 1,
						Output_record_separator => "\r",
						Errmode		=> $errmod);
	}elsif($po != 1){
		if($misc::usessh =~ /always/){
			&misc::Prt("CLI :telnet connection prohibited by usessh policy\n");
			return (undef, "connection prohibited by usessh policy");
		}
		&misc::Prt("TEL :$us\@$ip:$po Tout:${misc::timeout}s OS:$os EN:$cmd{$os}{'enpr'}\n");
		$session = new Net::Telnet(	Host		=> $ip,
						Port		=> $po,
						Timeout		=> $misc::timeout + 2,			# Add 2s to factor in auth server timeout
						Prompt		=> "/$cmd{$os}{'enpr'}/",
						Input_log	=> $inlog,
						Output_log	=> $outlog,
						Errmode		=> $errmod);
	}else{
		&misc::Prt("CLI :connection disabled\n");
		return (undef, "connection disabled");
	}
	return (undef, "connection error on port $po") if !defined $session;				# To catch failed connections

	($pre, $match) = $session->waitfor("/are you sure|offending key|modulus too small|connection refused|ssh_exchange_identification|any key|Ctrl-Y|$misc::uselogin|password\\s?:|$cmd{$os}{ropr}|$cli::cmd{$os}{enpr}/i");
	$err = $session->errmsg;
	if($err){											# on OBSD $err=pattern match read eof
		$session->close if defined $session;
		&misc::Prt("ERR0:$err\n");
		return (undef, "connection $err");
	}elsif($match =~ /connection refused/i){
		$session->close if defined $session;
		&misc::Prt("CLI0:Connection refused\n");						# on Linux $match=Connection refused
		return (undef, "connection refused");
	}elsif($match =~ /Selected cipher type not supported/){
		$session->close if defined $session;
		&misc::Prt("CLI0:Selected cipher type not supported\n");				# Sneuser: Juniper with Export image, you need a domestic image!
		return (undef, "connection cipher type not supported");
	}elsif($match =~ /ssh_exchange_identification/i){
		$session->close if defined $session;
		&misc::Prt("CLI0:Connection ssh_exchange_identification\n");
		return (undef, "connection ssh_exchange_identification");
	}elsif($match =~ /are you sure/i){								# StrictHostKeyChecking
		$session->close if defined $session;
		&misc::Prt("CLI0:Turn StrictHostKeyChecking off or add key\n");
		return (undef, "connection hostkey not in known_hosts");
	}elsif($match =~ /offending key/i){								# Hostkey changed
		$session->close if defined $session;
		&misc::Prt("CLI0:Hostkey changed\n");
		return (undef, "connection hostkey changed");
	}elsif($match =~ /modulus too small/i){								# Size matters after all...
		$session->close if defined $session;
		&misc::Prt("CLI0:Hostkey too small\n");
		return (undef, "connection hostkey too small");
	}elsif($match =~ /any key|Ctrl-Y/i){
		&misc::Prt("CLI1:Matched '$match' sending ctrl-Y\n");
		$session->put("\cY");									# Since Nortel wants Ctrl-Y...
		($pre, $match) = $session->waitfor("/$misc::uselogin|password\\s?:|$cmd{$os}{ropr}|$cmd{$os}{enpr}/i");
		if($match =~ /$misc::uselogin/i){
			&misc::Prt("CLI1:Matched '$match' sending username\n");
			$next = "us";
		}elsif($match =~ /password\s?(:|for)/i){
			&misc::Prt("CLI1:Matched '$match' sending password\n");
			$next = "pw";
		}
	}elsif($match =~ /$misc::uselogin/i){
		&misc::Prt("CLI2:Matched '$match' sending username\n");
		$next = "us";
	}elsif($match =~ /password\s?(:|for)/i){
		&misc::Prt("CLI2:Matched '$match' sending password\n");
		$next = "pw";
	}

	if($next eq "us"){
		$session->print($realus);
		&misc::Prt("CLI3:Username $realus sent\n");
		($pre, $match) = $session->waitfor("/password\\s?:|invalid|incorrect|denied|authentication failed|$misc::uselogin|$cmd{$os}{ropr}|$cmd{$os}{enpr}/i");
		if($match =~ /password\s?(:|for)/i){
			&misc::Prt("CLI3:Matched '$match' sending password\n");
			$next = "pw";
		}else{
			&misc::Prt("CLI3:Login, no match ($pre)\n");
		}
	}
	if($next eq "pw"){
		$session->print($misc::login{$us}{pw});
		&misc::Prt("CLI3:Password sent\n");
		($pre, $match) = $session->waitfor("/any key|Ctrl-Y|password\\s?:|invalid|incorrect|denied|authentication failed|$misc::uselogin|$cmd{$os}{ropr}|$cmd{$os}{enpr}/i");
		#print "PRE :$pre\nMTCH:$match\n" if $main::opt{'d'}; #TODO find out why my enterasys disconnects here
	}
	$err = $session->errmsg;
	if($err){
		&misc::Prt("ERR3:$err\n");
		$session->close;
		return (undef, "login error");
	}elsif($match =~ /password\s?(:|for)|invalid|incorrect|denied|Authentication failed|$misc::uselogin/i){
		&misc::Prt("CLI3:Matched '$match' login failed\n");
		$session->close;
		return (undef, "invalid credentials");
	}elsif($match =~ /any key|Ctrl-Y/i){								# Some want this now (with SSH)...
		&misc::Prt("CLI3:Matched '$match' sending ctrl-Y\n");
		$session->put("\cY");									# Since Nortel wants Ctrl-Y...
		($pre, $match) = $session->waitfor("/$cmd{$os}{enpr}/i");
		$err = $session->errmsg;
	}else{
		if ($match =~ /$cmd{$os}{ropr}/ or $cmd{$os}{'ropr'} eq 'GitsDoNid' and $cmd{$os}{'enab'} and $misc::login{$us}{en}){	# Read-only prompt or general prompt and enable cmd and pw?
			&misc::Prt("CLI4:Matched $match (or gen. prompt with enpass & $cmd{$os}{'enab'} cmd), enabling\n");
			$session->print($cmd{$os}{'enab'});
			($pre, $match) = $session->waitfor("/$misc::uselogin|password\\s?:|$cmd{$os}{enpr}/i");
			$err = $session->errmsg;
			if($err){
				&misc::Prt("ERR4:$err\n");
				$session->close;
				return (undef, "login error");
			}elsif($match =~ /$misc::uselogin/i){
				&misc::Prt("CLI5:Matched '$match' sending username\n");
				$session->print($realus);
				($pre, $match) = $session->waitfor("/password\\s?:/i");
				$err = $session->errmsg;
				if($err){
					&misc::Prt("ERR5:$err\n");
					$session->close;
					return (undef, "login error");
				}elsif($match =~ /password\s?(:|for)/i){
					&misc::Prt("CLI6:Matched '$match' sending password\n");
					$session->print($misc::login{$us}{en});
					($pre, $match) = $session->waitfor("/password\\s?:|invalid|incorrect|denied|authentication failed|$cmd{$os}{enpr}/i");
					$err = $session->errmsg;
				}else{
					&misc::Prt("CLI6:Enabling with user, no match in -->$pre<--\n");
				}
			}elsif($match =~ /password\s?(:|for)/i){
				&misc::Prt("CLI7:Matched '$match' sending password\n");
				$session->print($misc::login{$us}{en});
				($pre, $match) = $session->waitfor("/password\\s?:|invalid|incorrect|denied|authentication failed|unable to verif |$cmd{$os}{enpr}/i");
				$err = $session->errmsg;
			}else{
				&misc::Prt("CLI7:Enabling, no match PRE:$pre\n");
			}
		}else{
			$err = "no read-only prompt";
		}
	}
	if($match =~ /$cmd{$os}{enpr}/i){								# Are we enabled?
		&misc::Prt("CLI8:Matched enable prompt, OK\n");
		return ($session, "OK");
	}else{
		$err = $session->errmsg;
		&misc::Prt("CLI8:Matched '$match' enable failed \n");
		$session->close;
		return (undef, "enable failed");
	}
}

=head2 FUNCTION PrepDev()

Find login, if device is compatible for mac-address-table or config retrieval

B<Options> device name, preparation mode (fwd table or config backup)

B<Globals> main::dev

B<Returns> status

=cut
sub PrepDev{

	my ($na, $mod) = @_;
	my ($session, $us);
	my $po    = 0;
	my $status= "init";
	my @users = @misc::users;

	&misc::Prt("\nPrepare (CLI)  ----------------------------------------------------------------\n");
	if($mod eq "fwd" and !$cmd{$main::dev{$na}{os}}{dfwd}){						# Bridge forwarding supported?
		return "not implemented";
	}elsif($mod eq "cfg" and !$cmd{$main::dev{$na}{os}}{conf}){					# Config backup supported?
		return "not implemented";
	}elsif($mod eq "arp" and !$cmd{$main::dev{$na}{os}}{arp}){					# Arp supported?
		return "not implemented";
	}
	if($main::dev{$na}{cp}){									# port=0 -> set to be prepd
		if(!scalar keys %misc::login){								# Any users in nedi.conf?
			&misc::Prt("PREP:No users in nedi.conf\n");
			return "no users in nedi.conf";
		}elsif(!$main::dev{$na}{us}){								# Do we have a  user?
			&misc::Prt("PREP:No working user\n");
			return "no working user";
		}elsif(exists $misc::login{$main::dev{$na}{us}}){					# OK if in nedi.conf
			&misc::Prt("PREP:$mod supported and user $main::dev{$na}{us} exists\n");
			return "OK-DB";									# OK from DB
		}else{
			&misc::Prt("PREP:No user $main::dev{$na}{us} in nedi.conf\n");			# User not in nedi.conf -> Prep
		}
	}

	$main::dev{$na}{us} = "";
	while ($status ne "OK"){									# Find a way to log in
		$us = shift (@users) unless $status =~ /^connection /;					# Try next user if connection worked
		if(!$us){
			$status= "no valid users";
			last;										# Not possible, no more tries!
		}
		unless($po){										# Port was set before
			if(exists $misc::map{$main::dev{$na}{ip}}{cp}){					# Port mapped
				$po = $misc::map{$main::dev{$na}{ip}}{cp};
			}elsif($misc::usessh eq "never"){
				$po = 23;
			}else{
				$po = 22;
			}
		}
		($session, $status) = Connect($main::dev{$na}{ip}, $po, $us, $main::dev{$na}{os});
		if($status eq "OK"){
			$main::dev{$na}{cp} = $po;
			$main::dev{$na}{us} = $us;
		}elsif($status =~ /^connection /){							# Connection problem
			if($po == 22 and $misc::usessh ne "always"){					# Telnet if ssh failed and ok with policy
				$po = 23;
			}else{
				$main::dev{$na}{cp} = 1;						# port=1, connect not possible
				last;									# Not possible, no more tries!
			}
		}else{
			$main::dev{$na}{cp} = $po;							# Connected, save port
			last if($#users == -1);
		}
		if(defined $session){									# OK, but we just found out
			$session->close;
			select(undef, undef, undef, $clipause);						# Wait to avoid hang in fwd or conf
		}
	}
	return $status;
}

=head2 FUNCTION BridgeFwd()

Get Ios mac address table

B<Options> device name

B<Globals> misc::portprop, misc::portnew

B<Returns> 0 on success, 1 on failure

=cut
sub BridgeFwd{

	my ($na) = @_;
	my ($line, @cam);
	my $nspo = 0;

	&misc::Prt("\nBridgeFwd (CLI)   -------------------------------------------------------------\n");
	($session, $status) = Connect($main::dev{$na}{ip}, $main::dev{$na}{cp}, $main::dev{$na}{us}, $main::dev{$na}{os});
	if($status ne "OK"){
		return $status;
	}else{
		$session->max_buffer_length(8 * 1024 * 1024);						# Increase buffer to 8Mb
		if($cmd{$main::dev{$na}{os}}{page}){
			my @page = $session->cmd($cmd{$main::dev{$na}{os}}{page});
			&CmdRes($na,$cmd{$main::dev{$na}{os}}{page},\@page);
		}
		@cam = $session->cmd($cmd{$main::dev{$na}{os}}{dfwd});
		&CmdRes($na,$cmd{$main::dev{$na}{os}}{dfwd},\@cam);
		if($misc::getfwd eq 'sec' and exists $cmd{$main::dev{$na}{os}}{sfwd}){
			&misc::Prt("CMD :$cmd{$main::dev{$na}{os}}{sfwd}\n");
			my @scam = $session->cmd($cmd{$main::dev{$na}{os}}{sfwd});
			&CmdRes($na,$cmd{$main::dev{$na}{os}}{sfwd},\@scam);
			push @cam, @scam;
		}
		$session->close;
	}

	foreach my $l (@cam){
		my $mc = "";
		my $po = "";
		my $vl = "";
		my $ul = 0;
		my $rt = 0;
		if($main::dev{$na}{os} =~ /^(IOS|NXOS)/){
			if ($l =~ /\s+(dynamic|static|forward|secure(dynamic|sticky))\s+/i){		# (secure) tx Duane Walker 7/2007
				my @mactab = split (/\s+/,$l);
				foreach my $col (@mactab){
					if ($col =~ /^(Eth|Fa|Gi|Te|Do|Po|Vi)/){$po = &misc::Shif($col)}
					elsif ($col =~ /^[0-9|a-f]{4}\./){$mc = $col}
					elsif ($main::dev{$na}{os} ne "IOS-wl" and !$vl and $col =~ /^[0-9]{1,4}$/){$vl = $col} # Only use, if no vlan yet and it's not a Cisco AP
				}
				if($po =~ /[0-9]\.[0-9]/){						# Does it look like a subinterface?
					my @sub = split(/\./,$po);
					if(exists $misc::portprop{$na}{$sub[0]}){			# Parent IF exists, treat as sub
						$vl = $sub[1];
						$misc::portprop{$na}{$po}{lnk} = $misc::portprop{$na}{$sub[0]}{lnk};
						$misc::portprop{$na}{$po}{rtr} = $misc::portprop{$na}{$sub[0]}{rtr};
					}
				}
			}
		}elsif($main::dev{$na}{os} eq "CatOS"){
			if ($l =~ /^[0-9]{1,4}\s/){
				my @mactab = split (/\s+/,$l);
				foreach my $col (@mactab){
					if ($col =~ /^[0-9]{1,4}$/){$vl = $col}
					elsif ($col =~ /^([0-9|a-f]{2}-){5}[0-9|a-f]{2}$/){$mc = $col}
					elsif ($col =~ /[0-9]{1,2}\/[0-9]{1,2}/){$po = $col}
				}
			}
		}

		$mc =~ s/[^0-9a-f]//g;									# Strip to pure hex
		if($po and $mc and $mc !~ /$misc::ignoredmacs/){					# Make sure we've a valid MAC entry
			if(exists($misc::portprop{$na}{$po}) ){						# IF exists?
				if ($vl !~ /$misc::ignoredvlans/){
					$misc::portprop{$na}{$po}{pop}++;
					&misc::Prt("FWDC:$mc on $po vl$vl");
					$mc .= $vl if $vl =~ /$misc::useivl/;				# Add vlid to mac
					$misc::portnew{$mc}{$na}{po} = $po;
					$misc::portnew{$mc}{$na}{vl} = $vl;
					$nspo++;
				}
				if(exists $misc::ifmac{$mc}){
					&misc::Prt(" belongs to ".join(", ",keys %{$misc::ifmac{$mc}}) );
					$misc::portprop{$na}{$po}{lnk}++;
					$main::int{$na}{$misc::portprop{$na}{$po}{idx}}{com} .= "MAC:".join(",",keys %{$misc::ifmac{$mc}}).', ' if $main::int{$na}{$misc::portprop{$na}{$po}{idx}}{com} !~ /^(C|F|LL|N)DP:/;
				}
				&misc::Prt("\n");
			}else{
				&misc::Prt("FWDC:$mc vl$vl, no IF $po\n");
			}
		}
	}
	&misc::Prt("FWDC:$nspo bridge forwarding entries found\n"," f$nspo");
	return "OK-Bridge";
}

=head2 FUNCTION BridgeFwd()

Get Ios-fw arp table

B<Options> device name

B<Globals> misc::portprop, misc::portnew

B<Returns> 0 on success, 1 on failure

=cut
sub Arp{

	my ($na) = @_;
	my (@arp, %myarpc);
	my $narp = 0;

	&misc::Prt("\nArp (CLI)   -------------------------------------------------------------------\n");
	($session, $status) = Connect($main::dev{$na}{ip}, $main::dev{$na}{cp}, $main::dev{$na}{us}, $main::dev{$na}{os});
	if($status ne "OK"){
		return $status;
	}else{
		$session->max_buffer_length(8 * 1024 * 1024);						# Increase buffer to 8Mb
		if ($cmd{$main::dev{$na}{os}}{page}){
			my @page = $session->cmd($cmd{$main::dev{$na}{os}}{page});
			&CmdRes($na,$cmd{$main::dev{$na}{os}}{page},\@page);
		}
		@arp = $session->cmd($cmd{$main::dev{$na}{os}}{arp});
		&CmdRes($na,$cmd{$main::dev{$na}{os}}{arp},\@arp);
		$session->close;
	}

	foreach my $l (@arp){

		my $mc = "";
		my $ip = "";
		my $po = "";
		my $vl = "";
		my $ul = 0;
		my $rt = 0;

		if ($l =~ /(([0-9A-Fa-f]{4}\.){2}[0-9A-Fa-f]{4})/i){					# based on sk95 NXOS contribution
			my @atab = split (/\s+/,$l);
			if($main::dev{$na}{os} =~ /^IOS/){
				$po = $atab[1];
				$ip = $atab[2];
				$mc = $atab[3];
			}elsif($main::dev{$na}{os} eq 'NXOS'){
				$ip = $atab[0];
				$mc = $atab[2];
				$po = $atab[3];
			}
			$mc =~ s/[^0-9a-f]//g;								# Strip to pure hex
			if($mc !~ /$misc::ignoredmacs/){
				$myarpc{$mc}++;								# Find multiple MACs
				$misc::arp{$mc} = $ip;				
				if( exists($misc::portprop{$na}{$po}) ){
					$misc::portprop{$na}{$po}{rtr} = 1;
					$misc::portprop{$na}{$po}{pop}++;
					&misc::Prt("ARPC:$mc $misc::arp{$mc} on $po\n");
					$misc::portnew{$mc}{$na}{po} = $po;
					$misc::portnew{$mc}{$na}{vl} = $vl;
					$narp++;

					if(exists $misc::ifmac{$mc}){
						&misc::Prt("LINK:Seeing ".join(", ",keys %{$misc::ifmac{$mc}})." on $po: uplink\n");
						$misc::portprop{$na}{$po}{lnk}++;
						$main::int{$na}{$misc::portprop{$na}{$po}{idx}}{com} .= "MAC:".join(",",keys %{$misc::ifmac{$mc}}).', ' if $main::int{$na}{$misc::portprop{$na}{$po}{idx}}{com} !~ /^ (C|F|LL|N)DP:/;
					}
				}else{
					&misc::Prt("ARPC:$mc $misc::arp{$mc}, no IF $po\n");
				}
			}

			foreach my $mc ( keys %myarpc ){
				if(exists $misc::arpc{$mc}){
					$misc::arpc{$mc} = $myarpc{$mc} if $misc::arpc{$mc} < $myarpc{$mc};
				}else{
					$misc::arpc{$mc} = $myarpc{$mc};
				}
				if(!exists $misc::ifmac{$mc} and $myarpc{$mc} > $misc::arppoison){	# Check for ARP poisoning
					$misc::mq += &mon::Event('N',150,'sec',$na,$na,"$myarpc{$mc} IP addresses for $mc exceed threshold of $misc::arppoison");
				}
			}
		}
	}
	&misc::Prt("ARPC:$narp ARP entries found\n"," a$narp ");
	return "OK-Arp";
}

=head2 FUNCTION Config()

Get Ios mac address table

B<Options> device name

B<Globals> misc::curcfg

B<Returns> 0 on success, error on failure

=cut
sub Config{

	my ($na) = @_;
	my ($session, $status, $go);
	my @run = ();

	my $os = $main::dev{$na}{os};

	&misc::Prt("\nConfig (CLI)   ----------------------------------------------------------------\n");
	($session, $status) = Connect($main::dev{$na}{ip}, $main::dev{$na}{cp}, $main::dev{$na}{us}, $os);
	#($session, $status, $pty) = Connect($main::dev{$na}{ip}, $main::dev{$na}{cp}, $main::dev{$na}{us}, $os);
	if($status ne "OK"){
		return $status;
	}else{
		$session->max_buffer_length(8 * 1024 * 1024);						# Increase buffer to 8Mb
		if($cmd{$os}{page}){
			my @page = $session->cmd($cmd{$os}{page});
			&misc::Prt("CMD :$cmd{$os}{page}, @page\n");
		}
		$session->timeout( ($misc::timeout + (exists $cmd{$os}{ctim})?$cmd{$os}{ctim}:10) );							# Increase for building config
		if(!$cmd{$os}{page} and $cmd{$os}{more}){						# No pager, but a more prompt, need to page manually
			$session->print($cmd{$os}{conf});
			my $morerun = "";
			my $mcol = 0;
			&misc::Prt("MORE:");
			do{
				($pre, $match) = $session->waitfor("/$cmd{$os}{more}|$cmd{$os}{enpr}/i");
				$pre =~ s/\r|\x1b\[16D\s*|\x1b\[42D\s*//g;				# Ugly hack to get rid of backspaces after more prompt
				$morerun .= $pre;
				$session->put(" ") if $match eq "$cmd{$os}{more}";
				&misc::Prt(".");
				$mcol++;
				&misc::Prt("\nMORE:") unless $mcol % 74;
			}while($match !~ /$cmd{$os}{enpr}/i);						# press space until prompt is received
			&misc::Prt(" $mcol\n");
			@run = split(/[\r\n]/,$morerun);	
		}else{
			@run = $session->cmd($cmd{$os}{conf});
		}
		$session->close;
		&CmdRes($na,$cmd{$main::dev{$na}{os}}{conf},\@run);
	}

	foreach my $line (@run){
		$line =~ s/[\r\n]//g;
		if ($line =~ /$cmd{$os}{strt}/){$go = 1}
		if ($go){
			$line =~ s/\x1b\[(42;1H|2K|1;24r|24;1H)//g;					# ProCurve special sauce
			&misc::Prt("CONF:$line\n");
			push @misc::curcfg,$line;
		}else{
			&misc::Prt("WAIT:$line\n");
		}
		if (exists $cmd{$os}{end} and $line =~ /$cmd{$os}{end}/){$go = 0}
	}
	if( scalar(@misc::curcfg) < 3 ){
		&misc::Prt("ERR :No config (".join(' ',@misc::curcfg).")\n","Be");
		return "config is less than 3 lines";
	}else{
		while($misc::curcfg[$#misc::curcfg] eq ""){						# Remove empty trailing lines
			pop @misc::curcfg;
		}
		my $nl = scalar(@misc::curcfg);
		&misc::Prt("CONF:$nl lines read\n"," c$nl");
		return "OK-${nl}lines";
	}
}


=head2 FUNCTION SendCmd()

Send commands to device (used by the GUI helper Devsend.pl)

B<Options> IP, port, user, pass, OS, command file

B<Globals> -

B<Returns> -

=cut
sub SendCmd{

	my ($ip, $po, $us, $pw, $os, $cf) = @_;
	my $err = '';

	if($misc::guiauth =~ /-pass$/){
		$misc::login{$us}{pw} = $pw;
	}

	open  (CFG, "$cf.php" );
	my @cmd = <CFG>;
	close(CFG);
	shift @cmd;
	chomp @cmd;
	$misc::timeout *= 10;

	&misc::Prt("$os $us CMD=$cf(". scalar @cmd .") T:${misc::timeout}s <p>\n");

	open  (LOG, ">$cf-$ip.log" ) or print " can't write to $cf-$ip.log";

	($session, $status) = Connect($ip, $po, $us, $os);
	if($status ne "OK"){
		if(defined $session){
			$err = $session->errmsg;
		}else{
			$err = $status;
		}
		close (LOG);
		&misc::Prt("ERR :$err\n");
	}else{
		my @page = $session->cmd($cmd{$os}{'page'}) if $cmd{$os}{'page'};
		foreach my $c (@cmd){
			print LOG "$c\n";
			&misc::Prt("CMD :$c\n");
			my @out = $session->cmd($c); 
			foreach my $line (@out){
				$line =~ s/\x1b\[(24;1H|2K|1;24r)//g;					# ProCurve clensing...
				print LOG $line;
				&misc::Prt("RES :$line");
				$err = $line if $line =~ /^(\s?% )?(Invalid|Unknown|Failed|cannot)/;	# Catch errors, but ignore "% Warnings" (doesn't seem to work on ProCurve switches using SSH!)
			}
			last if $err;
		}
		$session->close;
		close (LOG);
	}
	return $err;
}

=head2 FUNCTION Spawn()

Spawns a pty.

B<Options> command

B<Globals> -

B<Returns> pty

=cut
sub Spawn{

	my $pty = new IO::Pty or die $!;

	&misc::Prt("PTY :Forking $_[0]\n");
	$SIG{CHLD} = 'IGNORE';
	unless (my $pid = fork){
		die $! unless defined $pid;

		use POSIX ();
		POSIX::setsid or die $!;

		my $tty = $pty->slave;
		$pty->make_slave_controlling_terminal();
		my $tty_fd = $tty->fileno;
		close $pty;

		open STDIN, "<&$tty_fd" or die $!;
		open STDOUT, ">&$tty_fd" or die $!;
		open STDERR, ">&STDOUT" or die $!;
		close $tty;
		exec $_[0] or die $!;
	}
	$pty;
}

=head2 FUNCTION CmdRes()

Searches for errors in cmd output and creates event accordingly

B<Options> command

B<Globals> -

B<Returns>

=cut
sub CmdRes{

	my ($dv, $cmd, $res) = @_;

	my $err = '';
	foreach my $l (@{$res}){
		$err = $l if $l =~ /^(\s?% )?(Invalid|Unknown|Failed|cannot)/;	# Catch errors, but ignore "% Warnings" (doesn't seem to work on ProCurve switches using SSH!)
	}
	if($err){
		chomp $err;
		chomp $err;
		$misc::mq += &mon::Event('C',150,'nede',$dv,$dv,"Command $cmd returned $err");
		return $err;
	}else{
		&misc::Prt("CMD :$cmd OK\n");
	}
}

1;
