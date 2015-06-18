#!/usr/bin/perl

###############################################################################
# CheckNewMac.pl
# 2009-11-03
###############################################################################
# A tiny add-On for Nedi release 1.0.4 by josef kierberger
# j.kierberger@gmx.at
###############################################################################
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of the
# License, or (at your option) any later version.
###############################################################################
# Description: This script loops through the nodes-table from nedi and
# compares the year/month/day/hour part of the -firstseen- attribute with
# the current time. If there is a match, that means a new mac address was
# found in the last hour, an email with the new found mac address will be 
# sent via localhost.
# For correct function you should run it after the hourly nedi-run.
# Moreover the arp aging time in your switches should be one hour.
###############################################################################



# PERL MODULES
use DBI;
use DBD::mysql;
use Net::SMTP;

# CONFIG VARIABLES
$database = "nedi";
$host = "localhost";
$port = "3306";
$user = "nedi";
$pw = "dbpa55";
$emailAddress = 'support@yourdomain.com';


###############################################################################
# OPEN DATABASE

$dsn = "dbi:mysql:$database:$host:$port";
$connect = DBI->connect($dsn, $user, $pw);
$query = "SELECT firstseen,mac,name,ip FROM nodes";
$query_handle = $connect->prepare($query);
$query_handle->execute();
$query_handle->bind_columns(\$firstseen,\$mac,\$name,\$ip);

# LOOP THROUGH RESULTS

while($query_handle->fetch()) {

   ($sec,$min,$hour,$day,$month,$year) = localtime();
   $year = 1900 + $year;
   $month++;
   $thisHour = sprintf ("%02d/%02d/%02d/%02d", $year, $month, $day,$hour);

   ($sec,$min,$hour,$day,$month,$year) = localtime($firstseen);
   $year = 1900 + $year;
   $month++;
   $myfirstseen = sprintf ("%02d/%02d/%02d/%02d", $year, $month, $day, $hour);

   if ($myfirstseen eq $thisHour){
        $a = $ip>>24;
        $b = $ip<<8>>24;
        $c = $ip<<16>>24;
        $d = $ip<<24>>24;
        $myIP = $a.".".$b.".".$c.".".$d;
        $smtp = Net::SMTP->new('localhost');
        $smtp->mail($ENV{USER});
        $smtp->to($emailAddress);
        $smtp->data();
        $smtp->datasend("To: support\n");
        $smtp->datasend("\n");
        $smtp->datasend("New MAC found in network!\n");
        $smtp->datasend("MAC: ",$mac,"\n");
        $smtp->datasend("IP: ",$myIP,"\n");
        $smtp->datasend("Name: ",$name,"\n");
        $smtp->dataend();
        $smtp->quit;

   }
}
