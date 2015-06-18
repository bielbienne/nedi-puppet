#! /usr/local/bin/perl

eval '(exit $?0)' && eval 'exec /usr/local/bin/perl $0 ${1+"$@"}'
&& eval 'exec /usr/local/bin/perl $0 $argv:q'
if 0;

# ============================================================================

# $Id: nediDeviceConnections.pl,v 0.03 2008/01/30

# Copyright (c) 2008 Duane Walker
# All rights reserved.

# This program is free software; you may redistribute it and/or modify it
# under the same terms as Perl itself.

# ============================================================================
#
# This script takes a list of nodes (ip, mac or name) and lists the connections
# to a root device. Currently this is only a text list, in the future it could
# be graphical.
#
# perl nediDeviceConnections.pl [-help] {[-ip ip1[,ip2]] |
#                                        [-name name1[,name2]] |
#                                        [-mac mac1[,mac2]]} -root device
#
# run from the /opt/nedi/contrib directory
# ============================================================================

use strict;
use Getopt::Long;

#Load the NeDi Config and database libraries
use vars qw($p $nediconf );
use vars qw( %dev %opt %link );

my %nodeDetails;

$p = '/usr/share/nedi';

require "$p/inc/libmisc.pl";
require "$p/inc/libsnmp.pl";

my $rootDev = '';
my $helpFlag = 0;
my $ipList = '';
my $nameList = '';
my $macList = '';
my $maxSearchDepth = 10;

GetOptions(
     "help"      	=>		\$helpFlag,
     "ip=s"    		=> 		\$ipList,
     "name=s"			=> 		\$nameList,
     "mac=s"			=>		\$macList,
     "root=s"     =>		\$rootDev,
     "depth=s"		=>		\$maxSearchDepth,
);

usage() if ($helpFlag);
if ((length($ipList) == 0) && (length($nameList) == 0) &&
		(length($macList) == 0)){
	print("No nodes specified!\n");
	usage();
}

if (length($rootDev) <= 0){
	print("No root device specified!\n");
	usage();
}

# Include required libraries
&misc::ReadConf();
#require "../inc/lib" . lc($misc::backend) . ".pl" || die "Backend error ($misc::backend)!";
#Updated for 1.0-RC5
require "$p/inc/libdb-" . lc($misc::backend) . ".pl" || die "Backend error ($misc::backend)!";

# Connect to the database.
my $dbh = connectDB();

	#Make sure the root device exists
my $rootDevIP = &misc::Dec2Ip(getRootDev($dbh, $rootDev));
print("Root Device $rootDev IP $rootDevIP\n");
#my $rootDevIP = '10.70.252.1';

#Make sure the nodes exist
foreach my $n (split /,/, $ipList){
	getNode($dbh, 1, $n);
}

foreach my $n (split /,/, $nameList){
	getNode($dbh, 2, $n);
}

foreach my $n (split /,/, $macList){
	getNode($dbh, 3, $n);
}

#Read the links table into memory
ReadLinks();

#foreach my $na (keys %link){
#	#print("Device $na\n");
#	foreach my $if (keys %{$link{$na}}){
#		#print("Device $na Interface $if\n");
#		foreach my $nbr (keys %{$link{$na}{$if}}){
#			print("Device $na Interface $if Neighbour $nbr\n");
#		}
#	}
#}

#Now for each Node see if we can map a path back to the root device
my $shortestPathLength;
my $shortestPath;
foreach my $n (keys %nodeDetails){
	#Look for paths for each node
	$shortestPathLength = $maxSearchDepth;
	$shortestPath = "";
	pathToRoot($rootDev, $nodeDetails{$n}{device}, "", 0);
	printf("Node %s %s connects to %s on port %s\n", $nodeDetails{$n}{name},$nodeDetails{$n}{ip}, $nodeDetails{$n}{device},$nodeDetails{$n}{ifname});
	if (length($shortestPath) > 0){
		printf("Path for node %s to Root %s is\n",
			$nodeDetails{$n}{name}, $rootDev);
		foreach my $lnk (split /;/, $shortestPath){
			print("\t$lnk\n");
		}
		#printf("Path for node %s to Root %s in %d hops is %s\n",
		#	$nodeDetails{$n}{device}, $rootDev, $shortestPathLength, $shortestPath);
	} else {
		printf("Couldn't find Path for node %s to Root %s\n",
			$nodeDetails{$n}{device}, $rootDev);
	}
}

exit(0);

#===================================================================
# Check that the root device exists
#===================================================================
sub getRootDev
{
	my ($dbh, $name) = @_;

	my $query = <<_END_OF_TEXT_;
	SELECT name,ip
	FROM devices
	WHERE name = '$name';
_END_OF_TEXT_

	my $sth = $dbh->prepare($query);
	$sth->execute();
	my $ip;
	if (my $ref = $sth->fetchrow_hashref()) {
		$ip = $ref->{'ip'};
	} else {
		print("Couldn't find Root Device $rootDev\n");
		$sth->finish();
		exit(1);
	}
	$sth->finish();
	return($ip);
}

#===================================================================
# Get details of a node
#===================================================================
sub getNode
{
	my ($dbh, $nodeType, $node) = @_;

	my $field;
	if ($nodeType == 1){
		$field = 'ip';
		$node = &misc::Ip2Dec($node);
	} elsif ($nodeType == 2){
		$field = 'name';
	} elsif ($nodeType == 3){
		$field = 'mac';
	}

	my $query = <<_END_OF_TEXT_;
		select name,ip,mac,device,ifname
		FROM nodes
		WHERE $field = '$node';
_END_OF_TEXT_

	my $sth = $dbh->prepare($query);
	$sth->execute();
	if (my $ref = $sth->fetchrow_hashref()) {
		$nodeDetails{$node}{name}		= $ref->{'name'};
		$nodeDetails{$node}{ip}			= &misc::Dec2Ip($ref->{'ip'});
		$nodeDetails{$node}{mac}		= $ref->{'mac'};
		$nodeDetails{$node}{device}	= $ref->{'device'};
		$nodeDetails{$node}{ifname}	= $ref->{'ifname'};
		$sth->finish();
		return(1);
	} else {
		$sth->finish();
		return(0);
	}
}

#===================================================================
# See if we can work out a path from the device to the root device.
# This is a recursive subroutine.
#
# Recursive subroutines are always difficult to understand.
# It basically checks to see if it has reached the goal (found a path) and
# if so records the details, if not, it searches it's neighbours.
#
# The search is fast because the neighbours are in memory, but we
# trim the scope as we go so we don't search millions of paths.
#
# We keep track of the shortest path so we don't keep searching long
# paths that are irrelevant. We also look at each neighbour to see if
# it is in the path already, don't want to go in circles.
#
# The path is purely physical connections (need NeDi links table).
# It won't show the logical path. A router may do router for a subnet
# on a vlan interface which may not be in the physical connections
# and this will not be shown.
#
#===================================================================
sub pathToRoot
{
	my ($root, $device, $path, $count) = @_;

	#print("pathToRoot: $root,$device,$path,$count \n");
	if ($count > $shortestPathLength){
		#Give up after a certain "depth" (we don't want to go forever)
		return();
	}

	if (lc($device) eq lc($root)){
		#We are there, display the result
		#print("Path: $path Hops $count\n");
		$shortestPathLength = $count;
		$shortestPath = $path;
		return();
	}
	$count++;

	#Lookup the neighbours of this device
	foreach my $if (keys %{$link{$device}}){
		foreach my $nbr (sort keys %{$link{$device}{$if}}){
			#Is the neighbour in the path already?
			#Don't want to go in circles
			if ($path =~ /$nbr/i){
				#Done already
				#print("Terminating traversal of this path; Neighbour $nbr Path $path\n");
			} else {
				#Traverse this path
				my $nbrif = $main::link{$device}{$if}{$nbr}{if};
				if (length($path) == 0){
					pathToRoot($root, $nbr, "$device,$if,$nbrif,$nbr", $count);
				} else {
					pathToRoot($root, $nbr, "$path;$device,$if,$nbrif,$nbr", $count);
				}
			}
		}
	}
	return();
}

#===================================================================
# Connect to the database
#===================================================================
sub connectDB
{
	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost",
			"$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	return($dbh);
}

#nodes Table
#+-----------+----------------------+------+-----+---------+-------+
#| Field     | Type                 | Null | Key | Default | Extra |
#+-----------+----------------------+------+-----+---------+-------+
#| name      | varchar(64)          | YES  | MUL | NULL    |       |
#| ip        | int(10) unsigned     | YES  | MUL | NULL    |       |
#| mac       | char(12)             | YES  | UNI | NULL    |       |
#| oui       | varchar(32)          | YES  |     | NULL    |       |
#| firstseen | int(10) unsigned     | YES  |     | NULL    |       |
#| lastseen  | int(10) unsigned     | YES  |     | NULL    |       |
#| device    | varchar(64)          | YES  |     | NULL    |       |
#| ifname    | varchar(32)          | YES  |     | NULL    |       |
#| vlanid    | smallint(5) unsigned | YES  | MUL | NULL    |       |
#| ifmetric  | tinyint(3) unsigned  | YES  |     | NULL    |       |
#| ifupdate  | int(10) unsigned     | YES  |     | NULL    |       |
#| ifchanges | int(10) unsigned     | YES  |     | NULL    |       |
#| ipupdate  | int(10) unsigned     | YES  |     | NULL    |       |
#| ipchanges | int(10) unsigned     | YES  |     | NULL    |       |
#| iplost    | int(10) unsigned     | YES  |     | NULL    |       |
#+-----------+----------------------+------+-----+---------+-------+

#devices table
#+-------------+----------------------+------+-----+---------+-------+
#| Field       | Type                 | Null | Key | Default | Extra |
#+-------------+----------------------+------+-----+---------+-------+
#| name        | varchar(64)          | YES  | UNI | NULL    |       |
#| ip          | int(10) unsigned     | YES  |     | NULL    |       |
#| serial      | varchar(32)          | YES  |     | NULL    |       |
#| type        | varchar(32)          | YES  |     | NULL    |       |
#| firstseen   | int(10) unsigned     | YES  |     | NULL    |       |
#| lastseen    | int(10) unsigned     | YES  |     | NULL    |       |
#| services    | tinyint(3) unsigned  | YES  |     | NULL    |       |
#| description | varchar(255)         | YES  |     | NULL    |       |
#| os          | varchar(8)           | YES  |     | NULL    |       |
#| bootimage   | varchar(64)          | YES  |     | NULL    |       |
#| location    | varchar(255)         | YES  |     | NULL    |       |
#| contact     | varchar(255)         | YES  |     | NULL    |       |
#| vtpdomain   | varchar(32)          | YES  |     | NULL    |       |
#| vtpmode     | tinyint(3) unsigned  | YES  |     | NULL    |       |
#| snmpversion | tinyint(3) unsigned  | YES  |     | NULL    |       |
#| community   | varchar(32)          | YES  |     | NULL    |       |
#| cliport     | smallint(5) unsigned | YES  |     | NULL    |       |
#| login       | varchar(32)          | YES  |     | NULL    |       |
#| icon        | varchar(16)          | YES  |     | NULL    |       |
#+-------------+----------------------+------+-----+---------+-------+



#######################################################################
#
# Command line options processing
#
#######################################################################

sub usage(){

my $usage = <<_END_OF_TEXT_;

This script takes a list of nodes (ip, mac or name) and lists the connections
to a root device. Currently this is only a text list, in the future it could
be graphical.

usage: $0 [-help] | { [-ip ip1[,ip2..]
                      [-name name1[,name2..]
                      [-mac mac1[,mac2..] }
                    -root device
                    [-depth n]

perl nediDeviceConnections.pl [-help]

 -help          : display this text
 -ip            : list of one or more comma separated ip addresses (no spaces)
 -mac           : list of one or more comma separated mac addresses (no spaces)
 -name          : list of one or more comma separated names (no spaces)
                  (exact match not regexp, probably fully qualified)
 -root          : the network device that all connections will be mapped back to
 -depth					: search depth (default 10)

The search depth is critical to completing searches quickly. Many networks
are complex meshes with thousands of paths. This limits the amount of time
wasted on irrelevant paths. if no result is found try increasing the depth.

example: $0 -ip -nodes 10.70.60.114,10.70.10.70 -root rssmcc65001

The command line options can be shortened to unique values.
eg. -nodes can be shortened to -no

_END_OF_TEXT_

    print STDERR $usage;
    exit;
}

#links table
#+-----------+----------------------+------+-----+---------+----------------+
#| Field     | Type                 | Null | Key | Default | Extra          |
#+-----------+----------------------+------+-----+---------+----------------+
#| id        | int(10) unsigned     | NO   | MUL | NULL    | auto_increment |
#| device    | varchar(64)          | YES  | MUL | NULL    |                |
#| ifname    | varchar(32)          | YES  |     | NULL    |                |
#| neighbour | varchar(32)          | YES  |     | NULL    |                |
#| nbrifname | varchar(32)          | YES  |     | NULL    |                |
#| bandwidth | bigint(20) unsigned  | YES  |     | NULL    |                |
#| type      | char(1)              | YES  |     | NULL    |                |
#| power     | int(10) unsigned     | YES  |     | NULL    |                |
#| nbrduplex | char(2)              | YES  |     | NULL    |                |
#| nbrvlanid | smallint(5) unsigned | YES  |     | NULL    |                |
#+-----------+----------------------+------+-----+---------+----------------+

#===================================================================
# Read links table into memory.
#===================================================================
sub ReadLinks {

	my $nlink = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $sth = $dbh->prepare("SELECT * FROM links");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		my $na = $f[1];
		my $ndv = $f[3];
		my $if = $f[2];
		$main::link{$na}{$if}{$ndv}{if}	= $f[4];
		$main::link{$na}{$if}{$ndv}{bw}	= $f[5];
		$main::link{$na}{$if}{$ndv}{ty}	= $f[6];
		$main::link{$na}{$if}{$ndv}{pw}	= $f[7];
		$main::link{$na}{$if}{$ndv}{du}	= $f[8];
		$main::link{$na}{$if}{$ndv}{vl}	= $f[9];

		$nlink++;
	}
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nlink	links read from MySQL:$misc::dbname.links\n";

}
