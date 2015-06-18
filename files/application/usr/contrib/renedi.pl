#! /usr/local/bin/perl

eval '(exit $?0)' && eval 'exec /usr/local/bin/perl $0 ${1+"$@"}'
&& eval 'exec /usr/local/bin/perl $0 $argv:q'
if 0;

# ============================================================================

# $Id: nediCMDBExport.pl,v 0.01 2007/02/06

# Copyright (c) 2007 Duane Walker
# All rights reserved.

# This program is free software; you may redistribute it and/or modify it
# under the same terms as Perl itself.

# ============================================================================
#
# This script runs several SQL queries (via ODBC) against the NeDi database
# and saves the results in a number of CSV files.
#
# perl nediCMDBExport.pl [-help]
#
# ============================================================================
# This script relies on the MySQL ODBC driver.
#
# The MySQL ODBC driver must be installed and a DSN called "nedi" defined
# pointing to the NeDi database. The script passes the username and password
# so this is not required in the ODBC definition.
#
# To talk to the NeDi database a few changes may be required on MySQL.
#
# 1. Allow MySQL to connect to network (not just localhost).
#    - comment out bind-address 127.0.0.1 in /etc/mysql/my.cnf
#
# 2. Grant nedi permission to access database over the network.
#    - login to mysql ui.
#      prompt> mysql -u root -p
#      (enter the mysql root password)
#    mysql> GRANT ALL ON nedi.* TO 'nedi'@'%' IDENTIFIED BY 'dbpa55';
#      (it will say 0 rows returned)
#    mysql> FLUSH PRIVILEGES;
#      (it will say 0 rows returned)
#    mysql> exit
#
# ============================================================================

use strict;
use DBI();
use DBD::ODBC;
use Getopt::Long;
use IO::Handle;

my $helpFlag;

GetOptions(
    "help"        =>  \$helpFlag,
);

if ($helpFlag){
  usage();
}

my $datasource = q/dbi:ODBC:nedi/;
my $user = q/nedi/;
my $pass = q/dbpa55/;

my $dbh = DBI->connect($datasource, $user, $pass)
 or die "Can't connect to $datasource: $DBI::errstr\n";

exportDevices($dbh);
exportModules($dbh);
exportNeighbours($dbh);
exportNodes($dbh);

#Disconnect from the Database
$dbh->disconnect();

#We are done, exit gracefully
exit 0;


#######################################################################
# ExportDevices
#######################################################################
sub exportDevices
{
my ($dbh) = @_;

my $csvFile = new IO::Handle;
open ($csvFile, "> devices.csv") or die "Open failed: $!\n";

my $qry = <<_END_OF_TEXT_;
select name,ip,serial,type,description,os,bootimage,location
FROM devices;
_END_OF_TEXT_

my $sth = $dbh->prepare($qry)
 or die "Can't prepare statement $qry: $DBI::errstr\n";
$sth->execute();
$csvFile->print("name,ip,serial,type,description,os,bootimage,location\n");
while (my @row = $sth->fetchrow_array){
 #Outout rows
 $csvFile->print("$row[0],");
 $csvFile->printf("%s,",Dec2Ip($row[1]));
 $csvFile->print("$row[2],");
 $csvFile->print("$row[3],");
 $csvFile->print("\"$row[4]\",");
 $csvFile->print("$row[5],");
 $csvFile->print("$row[6],");
 $csvFile->print("\"$row[7]\"\n");
}
$csvFile->close;
return();
}

#######################################################################
# ExportModules
#######################################################################
sub exportModules
{
my ($dbh) = @_;

my $csvFile = new IO::Handle;
open ($csvFile, "> modules.csv") or die "Open failed: $!\n";

my $qry = <<_END_OF_TEXT_;
select device,slot,model,description,serial,hw,fw,sw,status
FROM modules;
_END_OF_TEXT_

my $sth = $dbh->prepare($qry)
 or die "Can't prepare statement $qry: $DBI::errstr\n";
$sth->execute();
$csvFile->print("device,slot,model,description,serial,hw,fw,sw,status\n");
while (my @row = $sth->fetchrow_array){
 #Outout rows
 $csvFile->print("$row[0],");
 $csvFile->print("$row[1],");
 $csvFile->print("$row[2],");
 $csvFile->print("$row[3],");
 $csvFile->print("$row[4],");
 $csvFile->print("$row[5],");
 $csvFile->print("$row[6],");
 $csvFile->print("$row[7],");
 $csvFile->print("$row[8]\n");
}
$csvFile->close;
return();
}

#######################################################################
# ExportNeighbours
#######################################################################
sub exportNeighbours
{
my ($dbh) = @_;

my $csvFile = new IO::Handle;
open ($csvFile, "> neighbours.csv") or die "Open failed: $!\n";

my $qry = <<_END_OF_TEXT_;
select device,ifname,neighbour,nbrifname,bandwidth,type,nbrduplex,nbrvlanid
FROM links;
_END_OF_TEXT_

my $sth = $dbh->prepare($qry)
 or die "Can't prepare statement $qry: $DBI::errstr\n";
$sth->execute();
$csvFile->print("device,ifname,neighbour,nbrifname,bandwidth,type,duplex,vlan\n");
while (my @row = $sth->fetchrow_array){
 #Outout rows
 $csvFile->print("$row[0],");
 $csvFile->print("$row[1],");
 $csvFile->print("$row[2],");
 $csvFile->print("$row[3],");
 $csvFile->print("$row[4],");
 $csvFile->print("$row[5],");
 $csvFile->print("$row[6],");
 $csvFile->print("$row[7]\n");
}
$csvFile->close;
return();
}

#######################################################################
# ExportNodes
#######################################################################
sub exportNodes
{
my ($dbh) = @_;

my $csvFile = new IO::Handle;
open ($csvFile, "> nodes.csv") or die "Open failed: $!\n";

my $qry = <<_END_OF_TEXT_;
select name,ip,mac,oui,device,ifname,vlanid
FROM nodes;
_END_OF_TEXT_

my $sth = $dbh->prepare($qry)
 or die "Can't prepare statement $qry: $DBI::errstr\n";
$sth->execute();
$csvFile->print("name,ip,mac,oui,device,ifname,vlan\n");
while (my @row = $sth->fetchrow_array){
 #Outout rows
 $csvFile->print("$row[0],");
 $csvFile->printf("%s,",Dec2Ip($row[1]));
 $csvFile->print("$row[2],");
 $csvFile->print("\"$row[3]\",");
 $csvFile->print("$row[4],");
 $csvFile->print("$row[5],");
 $csvFile->print("$row[6]\n");
}
$csvFile->close;
return();
}


#######################################################################
#
# Command line options processing
#
#######################################################################

sub usage(){

my $usage = <<_END_OF_TEXT_;

This script runs several SQL queries (via ODBC) against the NeDi database
and saves the results in a number of CSV files.

usage: perl $0 [-help]

-help          : display this text

example: perl $0

The command line options can be shortened to unique values.
eg. -help can be shortened to -he

_END_OF_TEXT_

   print STDERR $usage;
   exit;
}

#From NeDi inc/libmisc.pl
#===================================================================
# Converts IP addresses to dec for efficiency in DB
#===================================================================
sub Ip2Dec {
if(!$_[0]){$_[0] = 0}
   return unpack N => pack CCCC => split /\./ => shift;
}

#From NeDi inc/libmisc.pl
#===================================================================
# Of course we need to convert them back...
#===================================================================
sub Dec2Ip {
return join '.' => map { ($_[0] >> 8*(3-$_)) % 256 } 0 .. 3;
}