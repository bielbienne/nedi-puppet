#!/usr/bin/perl
# $Id$
$VERSION = "1.0.0";

use strict;
use Getopt::Long qw(:config no_ignore_case_always auto_version);	# add debug to debug Getopt::Long
use File::Path;
use Pod::Usage;
use Data::Dumper;

use vars qw($p $now $nediconf $cdp $lldp $oui);
use vars qw(%nod %dev %int %mod %link %vlan %opt %net %usr);

GetOptions(\%opt, 
	qw(n=s debug=s legends options license man help|h),
	) or pod2usage(-exitstatus =>1, -output => \*STDOUT, -verbose =>'99', -sections => "SYNOPSIS");

pod2usage(-exitstatus => 0, -verbose => '2') 					if $opt{man};
pod2usage(-exitstatus => 0, -verbose => '99', -sections => "SYNOPSIS") 		if ($opt{help} or $opt{h});
pod2usage(-exitstatus => 0, -verbose => '99', -sections => "OPTIONS"	) 	if $opt{options};
pod2usage(-exitstatus => 0, -verbose => '99', -sections => "LICENSE"	) 	if $opt{license};

my $nconf = shift or pod2usage(-exitstatus => 0, -verbose => '99', -sections => "SYNOPSIS|CONFIGURATION FILES"	);

$opt{D} = $opt{debug} if $opt{debug};
$opt{v} = $opt{debug} if ($opt{debug} & 1);	#enable verbose if debug has bit 1 set

$p = $0;
$p =~ s/(.*)\/(.*)/$1/;
if($0 eq $p){$p = "."};

$now = time;

require "$p/inc/libmisc.pl";								# Use the miscellaneous nedi library

&misc::ReadConf($p, $nconf) or die "No nedi.conf found ($nconf)!";

require "$p/inc/libsnmp.pl";								# Use the SNMP function library
require "$p/inc/libmon.pl";								# Use the Monitoring lib for notifications
require "$p/inc/libweb.pl";								# Use the WEB functions for webdevs
require "$p/inc/libdb-" . lc($misc::backend) . ".pl" || die "Backend error ($misc::backend)!";
require "$p/inc/libcli-" . lc($misc::clilib) . ".pl" || die "Clilib error ($misc::clilib)!";

# Disable buffering so we can see what's going on right away.
select(STDOUT); $| = 1;

=head1 &GetOthers()

well need to get the links links in some smart way ...

=cut

sub GetOthers{
	my ($groupname, $name, $linkgroup_ref, $linksum_ref) = @_;

	for my $nbour (keys %{$$linksum_ref{$name}}){
		if ( !defined $$linkgroup_ref{$groupname}{$nbour} ){	# "done already"
			$$linkgroup_ref{$groupname}{$nbour} = 1;
			&GetOthers($groupname, $nbour, $linkgroup_ref, $linksum_ref);
		}
	}
	return 1;
}

{
	my ($link_ref, $error) = &db::GetArrayRef($misc::db,"SELECT device, neighbour FROM links limit 1000"); 
	my ($name_ref, $error) = &db::GetArrayRef($misc::db,"SELECT name FROM devices limit 1000");

# get a structure of links ...
	my %linksum;
	foreach my $f (@{$link_ref}){
		$linksum{$$f[0]}{$$f[1]} = "$$f[1]";
	}

# get a structure of groups ...
	my %linkgroup;
	my %device;
	foreach my $f (@{$name_ref}){
		my $name = $$f[0];
		$device{$name} = 1;						# one of "my" devices, a check for latter...
		my $ingroup = 0;						# the first $name won't be in a group ...
		for my $key (keys %linkgroup){
			$ingroup = 1 if (defined $linkgroup{$key}{$name});	# $name already in a group
		}
		if (!$ingroup){
			my $groupname = $name;
			$linkgroup{$groupname}{$name} = 1;				# put $name in own group $name
			&GetOthers($groupname, $name, \%linkgroup, \%linksum);		# get $names links (and links links ...) into linkgroup
		}
	}

# print out grouping
	for my $group (keys %linkgroup){
		print "\nGroup:\t$group\n";
		for my $dev (keys %{$linkgroup{$group}}){
			print "\t$dev\n" if defined $device{$dev};
		}
	}

	exit 0;
}

__END__


=head1 NAME

nedilauch.pl - NeDi's parallel discovery launcher

=head1 SYNOPSIS

nedilaunch.pl [options] F<nedi-configuration-file>

NeDi's parallel discovery launcher that starts several nedi.pl's taking care not to have groups with links between.

For more information, try:

=over 8

=item B<--man> 
full documentation

=item B<--options> 
show options

=item B<--version> 
show version

=item B<--license> 
show license

=back

=head1 ARUMENTS

The nedi.conf file that is to be used. See CONFIGURATION FILES for more details.

=head1 OPTIONS

B<Discovery Options>

Most can be combined, the default is static using a seedlist file in the working directory or the default gateway. 

=over 8

=item B<-n>
options to start nedi.pl with

=back

=head1 CONFIGURATION FILES

=over

=item F<nedi.conf>

 This program needs a configuration file to work as an argument (not an option).
 See nedi.conf.dist and edit to your requirements.

=back

=head1 CHANGES

 xx/12/09	v1.0.0	OXO hacking on v1.0.5rc4b: so a lot of changes here ...

=head1 LICENSE

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

(C) 2001-2009 Remo Rickli (and contributors)

=head1 DISCLAIMER

 NeDi is a quite powerful network management suite. 
 It works fine. It hasn't caused any damage or outage yet! 
 The author won't take any responsibility if you do manage to cause damage with NeDi!

=head1 AUTHOR

Remo Rickli. Visit http://www.nedi.ch for more information.

=cut
# If you are calling pod2usage() from a module and want to display that module's POD, you can use this:
# use Pod::Find qw(pod_where);
# pod2usage( -input => pod_where({-inc => 1}, __PACKAGE__) );

