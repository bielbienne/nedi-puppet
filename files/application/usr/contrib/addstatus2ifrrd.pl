#!/usr/bin/perl

=head2 Add status DS to interface RRDs

Based on this great article: http://michael.thegrebs.com/2007/12/30/adding-a-datastore-to-an-rrd-file/
and the beautiful RRD:Simple module from Cpan.

You'll need to do this on an Ubuntu system for example:

apt-get install libtest-deep-perl libtest-pod-perl libtest-pod-coverage-perl

Then download and build it: perl Makefile.pl;make;make install

Simply call from the nedi directory of a 1.0.7 installation

=cut

use strict;
use warnings;

use RRD::Simple();


my $rrd = RRD::Simple->new();

my @devs = <rrd/*>;
for my $dv (@devs){
	if( -d $dv ){
		print "$dv -----------\n";
		my @rrdfls = <$dv/*.rrd>;
		for my $rrdfl (@rrdfls) {
			unless( $rrdfl eq 'system.rrd' ){
				print "Processing $rrdfl...";
				$rrd->add_source($rrdfl, 'status' => 'GAUGE');
				print "ok.\n";
			}
		}
	}
}
