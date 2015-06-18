#! /usr/local/bin/perl

eval '(exit $?0)' && eval 'exec /usr/local/bin/perl $0 ${1+"$@"}'
&& eval 'exec /usr/local/bin/perl $0 $argv:q'
if 0;

# ============================================================================

# $Id: nediPortCapacity.pl,v 0.01 2007/02/13

# Copyright (c) 2007 Duane Walker
# All rights reserved.

# This program is free software; you may redistribute it and/or modify it
# under the same terms as Perl itself.

# ============================================================================
#
# This script reads the devices from the nedi database and then queries the
# devices' interface tables to produce summaries of how many ports are in
# use or available.
#
# perl nediPortCapacity.pl [-d]
#
# ============================================================================

use strict;
use Getopt::Std;
use IO::Handle;


#Load the NeDi Config and database libraries
use vars qw( $nediconf );
use vars qw( %dev %opt );
require './inc/libmisc.pl';
require './inc/libsnmp.pl';

getopts('d',\%opt); # or &Help();

# Include required libraries
&misc::ReadConf();
require "./inc/lib" . lc($misc::backend) . ".pl" || die "Backend error ($misc::backend)!";

#Read the devices from the database
&db::ReadDev();

#Open the CSV file
my $csvFile = new IO::Handle;
open ($csvFile, "> DevicePortCapacity.csv") or die "Open failed: $!\n";

$csvFile->print("device,active,idle,available\n");

foreach my $device (sort(keys %dev)){
#Get device interfaces
getInterfaces($device);
}
$csvFile->close;

#We are done, exit gracefully
exit 0;


#######################################################################
# Get Device Interfaces
#######################################################################
sub getInterfaces
{
my $session = "";
my $error = "";
my $r  = "";
my $err  = "";

my %ifde = ();  #If description
my %iftp = ();  #If type
my %ifos = ();  #If oper status
my %iflc = ();  #If oper status

my $notice = 0;

my %ifna = ();  #If name

my %ifal = ();
my %ifax = ();
my @port = ();
my %usedoid = ();

my $ifnamO = '1.3.6.1.2.1.31.1.1.1.1'; #If name
my $ifdesO = '1.3.6.1.2.1.2.2.1.2';  #If description
my $iftypO = '1.3.6.1.2.1.2.2.1.3';  #If type
my $ifoprO = '1.3.6.1.2.1.2.2.1.8';  #If oper status
my $iflcg0 = '1.3.6.1.2.1.2.2.1.9';  #If last change
my $ifaliO = '1.3.6.1.2.1.31.1.1.1.18';#If alias

my $dv = $_[0];

if($misc::sysobj{$main::dev{$dv}{so}}{al}){
 $ifaliO = $misc::sysobj{$main::dev{$dv}{so}}{al};
}
my $ifalxO = $misc::sysobj{$main::dev{$dv}{so}}{ax};

($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$dv}{ip},
     -community => $main::dev{$dv}{cm},
     -timeout   => $misc::timeout,
     -version   => $main::dev{$dv}{sp},
     -translate => [-timeticks => 0x0], #Leave time in "ticks"
#      -translate => [-octetstring => 0x0],
     -port      => '161');

printf("Device %s IP %s\n", $dv, $main::dev{$dv}{ip}) if $main::opt{d};
if ($error){
 print("Cannot connect to device\n") if $main::opt{d};
 print "$error\n" if $main::opt{d};
 return();
}

#MIB2 Sysinfo sysuptime
my @oids = ( ".1.3.6.1.2.1.1.3.0" );

#Retrieve the MIB2 sysinfo
if (!defined($session->get_request(Varbindlist => \@oids))){
 print("Cannot get sysuptime\n") if $main::opt{d};
  return(undef);
}

#Sysuptime in ticks (hundredths of a second
my $sysuptime = $session->var_bind_list()->{$oids[0]};

$r = $session->get_table($ifdesO);        # Walk interface description.
$err = $session->error;
if ($err){print "Id";print "$err\n" if $main::opt{d};$notice++}else{%ifde  = %{$r}}

$r = $session->get_table($ifnamO);        # Walk interface name.
$err = $session->error;
if ($err){print "In";print "$err\n" if $main::opt{d};$notice++}else{ %ifna = %{$r}}

$r = $session->get_table($iftypO);        # Walk interface type.
$err = $session->error;
if ($err){print "It";print "$err\n" if $main::opt{d};$notice++}else{%iftp  = %{$r}}

$r = $session->get_table($ifoprO);        # Walk interface oper status
$err = $session->error;
if ($err){print "Io";print "$err\n" if $main::opt{d};$notice++}else{%ifos  = %{$r}}

$r = $session->get_table($iflcg0);        # Walk interface last change time
$err = $session->error;
if ($err){print "Lc";print "$err\n" if $main::opt{d};$notice++}else{%iflc  = %{$r}}

#Get the Interface Indexes
foreach my $x (keys (%ifna)){
 my $i = $x;
 #Remove the leading snmp string leaving only the index
 $i =~ s/$ifnamO\.//;
 push(@port, $i);
}

#Did we get a list of indexes?
if (@port == 0){
 #No so lets build them from a different value
 foreach my $x (keys (%ifos)){
  my $i = $x;
  #Remove the leading snmp string leaving only the index
  $i =~ s/$ifoprO\.//;
  push(@port, $i);
 }
}

#Sort the indexes into numerical order
my @sortedport = sort { $a <=> $b } @port;

#Summary counters
my $activeInt = 0;
my $idleInt = 0;
my $availInt = 0;

foreach my $p (@sortedport){
 #Ignore management and virtual interfaces
 my $ifdes = $ifde{"$ifdesO.$p"};
 my $ifopr = $ifos{"$ifoprO.$p"};
 if (ignoreInterface($ifdes)){
  #Don't display management or virtual interfaces
  #printf("%-4s %-20s %-20s Bypassed\n",$p, $ifdes, $ifopr);
 } else {
  #Is the interface up or down
  if ($ifopr == 1){
   #It was up so mark it active
   $activeInt++;
   printf("%-20s %-20s Up\n", $ifdes, $ifopr) if $main::opt{d};
  } else {
   #It is down, how long since the status changed
   my $lastchange = $sysuptime - $iflc{"$iflcg0.$p"};
   my ($days, $hours, $mins, $secs) = convertTicks($lastchange);

   if ($days < 30){
    #Interface has been used recently, mark it as Idle for the moment
    $idleInt++;
    printf("%-20s %-20s Idle\n", $ifdes, $ifopr) if $main::opt{d};
   } else {
    $availInt++;
    printf("%-20s %-20s Available - Last Used %d Days Ago\n", $ifdes, $ifopr, $days) if $main::opt{d};
   }
  }
 }
}
printf("%-20s Active %4d Idle %4d Available %4d\n", $dv, $activeInt, $idleInt, $availInt);
$csvFile->print("$dv,$activeInt,$idleInt,$availInt\n");
return();
}


sub ignoreInterface
{
#Ignore management, virtual and sub interfaces

my ($desc) = @_;

return (1) if ($desc =~ /^sl/i);
return (1) if ($desc =~ /^me/i);
return (1) if ($desc =~ /^sc/i);
return (1) if ($desc =~ /^cpu/i);
return (1) if ($desc =~ /^eobc/i);
return (1) if ($desc =~ /^vl/i);
return (1) if ($desc =~ /vlan/i);
return (1) if ($desc =~ /^null/i);
return (1) if ($desc =~ /dialer/i);
return (1) if ($desc =~ /channel/i);
return (1) if ($desc =~ /tunnel/i);
return (1) if ($desc =~ /^lo/i);
return (1) if ($desc =~ /loopback/i);
return (1) if ($desc =~ /\./);
return (1) if ($desc =~ /\:/);
return(0);
}

sub convertTicks
{
my ($ticks) = @_;

my $days = int($ticks/8640000);
my $remain = $ticks - ($days * 8640000);

my $hours = int($remain/360000);
my $remain = $remain - ($hours * 360000);

my $mins = int($remain/6000);
my $remain = $remain - ($mins * 6000);

my $secs = int($remain/100);

return($days, $hours, $mins, $secs);
}

#===================================================================
# Display some help
#===================================================================
sub Help {
print "\n";
print "usage: nediPortCapacity.pl [-d]\n";
print "Options (can be combined, default is static) ------------------------------\n";
print "-d verbose mode\n";
print "---------------------------------------------------------------------------\n";
die "NeDi 1.0.w (X-mas Edition) 20.Dez 2006\n";
}