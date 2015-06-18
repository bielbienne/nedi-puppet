=pod

=head1 LIBRARY
libdb-msq.pm

Functions for the MYSQL Database

=head2 AUTHORS

Remo Rickli & NeDi Community

=cut

package db;
use warnings;

use DBI;
use Socket;												# Pg needs libsocket6-perl to pack ipv6

my $s6ok = 0;
eval 'use Socket6;';
if ($@){
	&misc::Prt("DB  :No Socket6, IPv6 will not work properly with Pg backend!\n");
}else{
	$s6ok = 1;
	&misc::Prt("DB  :Socket6 loaded\n");
}

use vars qw($dbh);

=head2 FUNCTION Connect()

Connect to Backend according to nedi.conf settings. Dies upon failure...

B<Options> dbuser, dbpass

B<Globals> -

B<Returns> -

=cut
sub Connect{

	my ($dbname,$dbhost,$dbuser,$dbpass,$ac) = @_;
	$ac = (!defined $ac)?0:$ac;
	$dbh = DBI->connect("DBI:$misc::backend:dbname=$dbname;host=$dbhost", $dbuser, $dbpass, { RaiseError => 1, AutoCommit => $ac} ) or die $DBI::errstr;
	&misc::Prt("DB  :Connecting to '$dbname\@$dbhost' as '$dbuser' with".(($ac)?'':'out')." autocommit ".((defined $dbh)?'OK':'FAIL')."\n") if $main::opt{'d'};
}

=head2 FUNCTION Commit()

Commit commands

B<Options> dbhandle

B<Globals> -

B<Returns> -

=cut
sub Commit{

	$dbh->commit;
}

=head2 FUNCTION IPtoDB()

Convert IPv6 Binary for writing to Pg-inet
May be used to handle IPv4 in the future as well...

B<Options> dbhandle

B<Globals> -

B<Returns> -

=cut
sub IPtoDB{

	my ($ip6,$addr) = @_;

	if($misc::backend eq 'Pg'){
		if($addr and $ip6){
			return sprintf("%x:%x:%x:%x:%x:%x:%x:%x",unpack("n8",$addr));
		}else{
			return  undef;									# Pg accepts NULL but not empty :-/
		}
	}else{
		return  $addr;
	}
}

=head2 FUNCTION Disconnect()

Disconnect from backend

B<Options> dbhandle

B<Globals> -

B<Returns> -

=cut
sub Disconnect{

	$dbh->disconnect;
}

=head2 FUNCTION InitDB()

Connect as admin, drop existing DB and create nedi db and add important
values (like the admin user).

B<Options> $adminuser,$adminpass,$nedihost

B<Globals> -

B<Returns> -

=cut
sub InitDB{

	&db::Connect( ($misc::backend eq 'Pg')?'postgres':'mysql',$misc::dbhost,$_[0],$_[1],1);
	my $dbver;
	my $sth = $dbh->prepare("SELECT VERSION()");
	$sth->execute();
	while ((my @f) = $sth->fetchrow) {
		$dbver = $f[0];
	}
	print "DB Version	: $dbver\n";
	print "----------------------------------------------------------------------\n";
	$dbh->do("DROP DATABASE IF EXISTS $misc::dbname");
	#$dbh->do("DROP FUNCTION public.inet_ntoa(bigint)");TODO remove, if not needed
	print "Old DB '$misc::dbname' dropped!\n";

	print "Creating $misc::dbname\n";
	my $serid = 'serial';
	my $tinun = 'smallint';
	my $smaun = 'integer';
	my $intun = 'bigint';
	my $bigun = 'bigint';
	my $char  = 'character varying';
	my $vchar = 'character varying';
	my $ipv6  = 'inet';
	my $text  = 'text';
	if($misc::backend eq 'Pg'){
		unless( &Select('pg_user','','usename',"usename = '$misc::dbuser'") ){
			$dbh->do("CREATE ROLE $misc::dbuser WITH login PASSWORD '$misc::dbpass';");
		}
		$dbh->do("CREATE DATABASE $misc::dbname OWNER=$misc::dbuser");
		$dbh->do("GRANT ALL PRIVILEGES ON DATABASE $misc::dbname TO $misc::dbuser");
	}else{
		$dbh->do("CREATE DATABASE $misc::dbname");
		$dbh->do("GRANT ALL PRIVILEGES ON $misc::dbname.* TO \'$misc::dbuser\'\@\'$_[2]\' IDENTIFIED BY \'$misc::dbpass\'");
		if($dbver =~ /5\.0/) {									#fix for mysql 5.0 with old client libs
			$dbh->do("SET PASSWORD FOR \'$misc::dbuser\'\@\'$_[2]\' = OLD_PASSWORD(\'$misc::dbpass\')");
		}
		$serid = 'INT UNSIGNED NOT NULL AUTO_INCREMENT';
		$tinun = 'TINYINT UNSIGNED';
		$smaun = 'SMALLINT UNSIGNED';
		$intun = 'INT UNSIGNED';
		$bigun = 'BIGINT UNSIGNED';
		$char  = 'CHAR';
		$vchar = 'VARCHAR';
		$ipv6  = "VARBINARY(16) DEFAULT ''";
		$text  = 'MEDIUMTEXT';
	}
	print "for $misc::dbuser\@$_[2]\n";
	&db::Disconnect();

#---Connect as nedi db user and create tables.
	&db::Connect($misc::dbname,$misc::dbhost,$misc::dbuser,$misc::dbpass);

	print "TABLES\ndevices\n";
	my $index = ($misc::backend eq 'Pg')?'':',INDEX (device(8)),PRIMARY KEY (device)';
	$dbh->do("CREATE TABLE devices(
		device $vchar(64) NOT NULL UNIQUE,
		devip $intun DEFAULT 0,
		serial $vchar(32) DEFAULT '',
		type $vchar(32)  DEFAULT '',
		firstdis $intun DEFAULT 0,
		lastdis $intun DEFAULT 0,
		services $tinun DEFAULT 0,
		description $vchar(255) DEFAULT '',
		devos $vchar(16) DEFAULT '',
		bootimage $vchar(64) DEFAULT '',
		location $vchar(255) DEFAULT '',
		contact $vchar(255) DEFAULT '',
		devgroup $vchar(32) DEFAULT '',
		devmode $tinun DEFAULT 0,
		snmpversion $tinun DEFAULT 0,
		readcomm $vchar(32) DEFAULT '',
		cliport $smaun DEFAULT 0,
		login $vchar(32) DEFAULT '',
		icon $vchar(16) DEFAULT '',
		origip $intun DEFAULT 0,
		cpu $tinun DEFAULT 0,
		memcpu $bigun DEFAULT 0,
		temp $tinun DEFAULT 0,
		cusvalue $bigun DEFAULT 0,
		cuslabel $vchar(32) DEFAULT '',
		sysobjid $vchar(255) DEFAULT '',
		writecomm $vchar(32) DEFAULT '',
		devopts $char(32) DEFAULT '',
		size $tinun DEFAULT 0,
		stack $tinun DEFAULT 1,
		maxpoe $smaun DEFAULT 0,
		totpoe $smaun DEFAULT 0,
		cfgchange $intun DEFAULT 0,
		cfgstatus $char(2) DEFAULT '--'
		$index)" );
 	$dbh->commit;

	print "modules\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(device(8)), INDEX(slot(8))';
	$dbh->do("CREATE TABLE modules(
		device $vchar(64) NOT NULL,
		slot $vchar(64) DEFAULT '',
		model $vchar(32) DEFAULT '',
		moddesc $vchar(255) DEFAULT '',
		serial $vchar(32) DEFAULT '',
		hw $vchar(128) DEFAULT '',
		fw $vchar(128) DEFAULT '',
		sw $vchar(128) DEFAULT '',
		modidx $smaun DEFAULT 0,
		modclass $tinun DEFAULT 1,
		status $smaun DEFAULT 0,
		modloc $vchar(255) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "interfaces\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(device(8)), INDEX(ifname(8)), INDEX(ifidx)';
	$dbh->do("CREATE TABLE interfaces(
		device $vchar(64) NOT NULL,
		ifname $vchar(32) NOT NULL,
		ifidx $bigun NOT NULL,
		linktype $char(4) DEFAULT '',
		iftype $smaun DEFAULT 0,
		ifmac $char(12) DEFAULT '',
		ifdesc $vchar(255) DEFAULT '',
		alias $vchar(64) DEFAULT '',
		ifstat $tinun DEFAULT 0,
		speed $bigun DEFAULT 0,
		duplex $char(2) DEFAULT '',
		pvid $smaun DEFAULT 0,
		inoct $bigun DEFAULT 0,
		inerr $intun DEFAULT 0,
		outoct $bigun DEFAULT 0,
		outerr $intun DEFAULT 0,
		dinoct $bigun DEFAULT 0,
		dinerr $intun DEFAULT 0,
		doutoct $bigun DEFAULT 0,
		douterr $intun DEFAULT 0,
		indis $intun DEFAULT 0,
		outdis $intun DEFAULT 0,
		dindis $intun DEFAULT 0,
		doutdis $intun DEFAULT 0,
		inbrc $intun DEFAULT 0,
		dinbrc $intun DEFAULT 0,
		lastchg $intun DEFAULT 0,
		poe $smaun DEFAULT 0,
		comment $vchar(255) DEFAULT '',
		trafalert $tinun DEFAULT 0,
		brcalert $smaun DEFAULT 0,
		macflood $smaun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "networks\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(device(8)), INDEX(ifname(8)), INDEX(ifip)';
	$dbh->do("CREATE TABLE networks(
		device $vchar(64) NOT NULL,
		ifname $vchar(32) DEFAULT '',
		ifip $intun DEFAULT 0,
		ifip6 $ipv6,
		prefix $tinun DEFAULT 0,
		vrfname $vchar(32) DEFAULT '',
		status $smaun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "configs\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(device(8)), PRIMARY KEY(device)';
	$dbh->do("CREATE TABLE configs(
		device $vchar(64) NOT NULL UNIQUE,
		config $text DEFAULT '',
		changes $text DEFAULT '',
		time $intun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "stock\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(serial)';
	$dbh->do("CREATE TABLE stock(
		state $tinun DEFAULT 0,
		serial $vchar(32) NOT NULL UNIQUE,
		type $vchar(32) DEFAULT 0,
		asset $vchar(32) DEFAULT '',
		location $vchar(255) DEFAULT '',
		source $vchar(32) default '-',
		cost $intun DEFAULT 0,
		ponumber $vchar(32) DEFAULT '',
		time $intun DEFAULT 0,
		partner $vchar(32) DEFAULT '',
		startmaint $intun DEFAULT 0,
		endmaint $intun DEFAULT 0,
		lastwty $intun DEFAULT 0,
		comment $vchar(255) DEFAULT '',
		usrname $vchar(32) DEFAULT 0,
		asupdate $intun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "vlans\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(vlanid), INDEX(device(8))';
	$dbh->do("CREATE TABLE vlans(
		device $vchar(64) NOT NULL,
		vlanid $smaun DEFAULT 0,
		vlanname $vchar(32) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "links\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(id), INDEX(device(8)), INDEX(ifname(8)), INDEX(neighbor(8)), INDEX(nbrifname(8)), PRIMARY KEY(id)';
	$dbh->do("CREATE TABLE links(
		id $serid,
		device $vchar(64) NOT NULL,
		ifname $vchar(32) DEFAULT '',
		neighbor $vchar(64) NOT NULL,
		nbrifname $vchar(32) DEFAULT '',
		bandwidth $bigun DEFAULT 0,
		linktype $char(4) DEFAULT '',
		linkdesc $vchar(255) DEFAULT '',
		nbrduplex $char(2) DEFAULT '',
		nbrvlanid $smaun DEFAULT 0,
		time $intun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "locations\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(region), PRIMARY KEY(id)';
	$dbh->do("CREATE TABLE locations(
		id $serid,
		region $vchar(32) NOT NULL,
		city $vchar(32) DEFAULT '',
		building $vchar(32) DEFAULT '',
		x $smaun DEFAULT 0,
		y $smaun DEFAULT 0,
		ns INT DEFAULT 0,
		ew INT DEFAULT 0,
		locdesc $vchar(255) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "events\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(id), INDEX(source(8)), INDEX(level), INDEX(time), INDEX(class), INDEX(device(8)), PRIMARY KEY(id)';
	$dbh->do("CREATE TABLE events(
		id $serid,
		level $tinun DEFAULT 0,
		time $intun DEFAULT 0,
		source $vchar(64) DEFAULT '',
		info $vchar(255) DEFAULT '',
		class $char(4) DEFAULT 'dev',
		device $vchar(64) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "monitoring\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(name(8)), INDEX(device(8))';
	$dbh->do("CREATE TABLE monitoring(
		name $vchar(64) NOT NULL UNIQUE,
		monip $intun,
		class $char(4) DEFAULT 'dev',
		test $char(6) DEFAULT '',
		testopt $vchar(64) DEFAULT '',
		testres $vchar(64) DEFAULT '',
		lastok $intun DEFAULT 0,
		status $intun DEFAULT 0,
		lost $intun DEFAULT 0,
		ok $intun DEFAULT 0,
		latency $smaun DEFAULT 0,
		latmax $smaun DEFAULT 0,
		latavg $smaun DEFAULT 0,
		uptime $intun DEFAULT 0,
		alert $tinun DEFAULT 0,
		eventfwd $vchar(255) DEFAULT '',
		eventlvl $tinun DEFAULT 0,
		eventdel $vchar(255) DEFAULT '',
		depend $vchar(64) DEFAULT '-',
		device $vchar(64) NOT NULL,
		notify $char(32) DEFAULT '',
		lostalert $tinun DEFAULT 2,
		latwarn $smaun DEFAULT 100,
		cpualert $tinun DEFAULT 75,
		memalert $intun DEFAULT 1024,
		tempalert $tinun DEFAULT 60,
		poewarn $tinun DEFAULT 8,
		arppoison $smaun DEFAULT 1,
		supplyalert $tinun DEFAULT 5
		$index)" );
 	$dbh->commit;

	print "incidents\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(id), INDEX(name(8)), INDEX(device(8)), PRIMARY KEY(id)';
	$dbh->do("CREATE TABLE incidents(
		id $serid,
		level $tinun DEFAULT 0,
		name $vchar(64) DEFAULT '',
		deps $intun DEFAULT 0,
		startinc $intun DEFAULT 0,
		endinc $intun DEFAULT 0,
		usrname $vchar(32) DEFAULT '',
		time $intun DEFAULT 0,
		grp $tinun DEFAULT 0,
		comment $vchar(255) DEFAULT '',
		device $vchar(64) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "nodes\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(name(8)), INDEX(nodip), INDEX(mac), INDEX(vlanid), INDEX(device(8))';
	$dbh->do("CREATE TABLE nodes(
		name $vchar(64) DEFAULT '',
		nodip $intun DEFAULT 0,
		mac $vchar(16) NOT NULL,
		oui $vchar(32) DEFAULT '',
		firstseen $intun DEFAULT 0,
		lastseen $intun DEFAULT 0,
		device $vchar(64) DEFAULT '',
		ifname $vchar(32) DEFAULT '',
		vlanid $smaun DEFAULT 0,
		ifmetric $intun DEFAULT 0,
		ifupdate $intun DEFAULT 0,
		ifchanges $intun DEFAULT 0,
		ipupdate $intun DEFAULT 0,
		ipchanges $intun DEFAULT 0,
		iplost $intun DEFAULT 0,
		arpval $smaun DEFAULT 0,
		nodip6 $ipv6,
		tcpports $vchar(64) DEFAULT '',
		udpports $vchar(64) DEFAULT '',
		nodtype $vchar(64) DEFAULT '',
		nodos $vchar(64) DEFAULT '',
		osupdate $intun DEFAULT 0,
		noduser $vchar(32) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "nodetrack\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(device(8)), INDEX(ifname(8))';
	$dbh->do("CREATE TABLE nodetrack(
		device $vchar(64) DEFAULT '',
		ifname $vchar(32) DEFAULT '',
		value $vchar(64) DEFAULT '',
		source $char(8) DEFAULT '',
		usrname $vchar(32) DEFAULT '',
		time $intun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "iftrack\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(mac), INDEX(vlanid), INDEX(device(8))';
	$dbh->do("CREATE TABLE iftrack(
		mac $vchar(16) NOT NULL,
		ifupdate $intun DEFAULT 0,
		device $vchar(64) DEFAULT '',
		ifname $vchar(32) DEFAULT '',
		vlanid $smaun DEFAULT 0,
		ifmetric $intun DEFAULT 0
		$index)" );
 	$dbh->commit;

	print "iptrack\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(mac), INDEX(vlanid), INDEX(device(8))';
	$dbh->do("CREATE TABLE iptrack(
		mac $vchar(16) NOT NULL,
		ipupdate $intun DEFAULT 0,
		name $vchar(64) DEFAULT '',
		nodip $intun DEFAULT 0,
		vlanid $smaun DEFAULT 0,
		device $vchar(64) NOT NULL DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "stolen\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(mac), INDEX(device(8)), PRIMARY KEY(mac)';
	$dbh->do("CREATE TABLE stolen(
		name $vchar(64) DEFAULT '',
		stlip $intun DEFAULT 0,
		mac $char(12) NOT NULL,
		device $vchar(64) DEFAULT '',
		ifname $vchar(32) DEFAULT '',
		usrname $vchar(32) DEFAULT '',
		time $intun DEFAULT 0,
		comment $vchar(255) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "users\n";
	$index = ($misc::backend eq 'Pg')?'':', PRIMARY KEY(usrname)';
	$dbh->do("CREATE TABLE users(
		usrname $vchar(32) NOT NULL UNIQUE,
		password $vchar(64) NOT NULL DEFAULT '',
		groups $smaun NOT NULL DEFAULT '0',
		email $vchar(64) DEFAULT '',
		phone $vchar(32) DEFAULT '',
		time $intun DEFAULT 0,
		lastlogin $intun DEFAULT 0,
		comment $vchar(255) DEFAULT '',
		language $vchar(16) NOT NULL DEFAULT 'english',
		theme $vchar(16) NOT NULL DEFAULT 'default',
		volume $tinun NOT NULL DEFAULT '48',
		columns $tinun NOT NULL DEFAULT '6',
		msglimit $tinun NOT NULL DEFAULT '5',
		miscopts $smaun NOT NULL DEFAULT '2',
		dateformat $vchar(16) NOT NULL DEFAULT 'j.M y G:i470',
		viewdev $vchar(255) DEFAULT ''
		$index)" );
	$sth = $dbh->prepare("INSERT INTO users (usrname,password,groups,time,comment,volume,columns,msglimit,miscopts) VALUES ( ?,?,?,?,?,?,?,?,? )");
	$sth->execute ( 'admin','3cac26b5bd6addd1ba4f9c96a58ff8c2c2c8ac15018f61240f150a4a968b8562','255',$main::now,'default admin','48','8','10','3' );
 	$dbh->commit;

	print "system\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(name)';
	$dbh->do("CREATE TABLE system (
		name $vchar(32) NOT NULL UNIQUE,
		value $vchar(32) DEFAULT ''
		$index)" );
	$sth = $dbh->prepare("INSERT INTO system (name,value) VALUES ( ?,? )");
	$sth->execute ( 'nodlock','0' );
	$sth->execute ( 'threads','0' );
	$sth->execute ( 'first','0' );
 	$dbh->commit;

	print "chat\n";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(time), INDEX (usrname(8))';
	$dbh->do("CREATE TABLE chat (
		time $intun,
		usrname $vchar(32) DEFAULT '',
		message $vchar(255) DEFAULT ''
		$index)" );
 	$dbh->commit;

	print "wlan";
	$index = ($misc::backend eq 'Pg')?'':', INDEX(mac)';
	$dbh->do("CREATE TABLE wlan (
		mac $char(8) NOT NULL,
		time $intun DEFAULT 0
		$index)" );
	my @wlan = ();
	if(-e "$main::p/inc/wlan.txt"){
		open  ("WLAN", "$main::p/inc/wlan.txt" );
		@wlan = <WLAN>;
		close("WLAN");
		chomp(@wlan);
	}
	$sth = $dbh->prepare("INSERT INTO wlan (mac,time) VALUES ( ?,? )");
	for my $mc (sort @wlan ){ $sth->execute ( $mc,$main::now ) }
	$dbh->commit;

	if($misc::backend eq 'Pg'){
		print "\nFUNCTIONS";
		my $inet_ntoa = <<END;

create or replace function inet_ntoa(bigint) returns inet as '
	select ''0.0.0.0''::inet+\$1;'
	language sql immutable;
END

		$dbh->do($inet_ntoa);
#		$dbh->do("ALTER FUNCTION inet_ntoa(bigint) OWNER TO nedi;");TODO remove, if not needed
		$dbh->commit;
	}

	$sth->finish if $sth;
	&db::Disconnect();
	print "... done.\n\n";
}


=head2 FUNCTION ReadDev()

Read devices table.

B<Options> match statement

B<Globals> main::dev

B<Returns> -

=cut
sub ReadDev{

	my $npdev = 0;
	my $where = (defined $_[0])?$_[0]:"";

	if($where eq 'all'){
		$where = "";
		&misc::Prt("RDEV:Reading all devices\n");
	}elsif($where){
		$where = "WHERE $where";
		&misc::Prt("RDEV:Reading devices $where\n");
	}

	my $sth = $dbh->prepare("SELECT * FROM devices $where");
	$sth->execute();
	if($sth->rows){
		undef (%main::dev);									# Replace entries only when we got some new ones. Avoid problems in moni.pl while nedi.pl updates devices
	}
	while ((my @f) = $sth->fetchrow_array) {
		$main::dev{$f[0]}{ip} = &misc::Dec2Ip($f[1]);
		$main::dev{$f[0]}{oi} = &misc::Dec2Ip($f[19]);
		$main::dev{$f[0]}{sn} = $f[2];
		$main::dev{$f[0]}{ty} = $f[3];
		$main::dev{$f[0]}{fs} = $f[4];
		$main::dev{$f[0]}{ls} = $f[5];
		$main::dev{$f[0]}{sv} = $f[6];
		$main::dev{$f[0]}{de} = $f[7];
		$main::dev{$f[0]}{os} = $f[8];
		$main::dev{$f[0]}{bi} = $f[9];
		$main::dev{$f[0]}{lo} = $f[10];
		$main::dev{$f[0]}{co} = $f[11];
		$main::dev{$f[0]}{dg} = $f[12];
		$main::dev{$f[0]}{dm} = $f[13];
		$main::dev{$f[0]}{rv} = $f[14]  & 3;							# 1st 2 bits, SNMP read version
		$main::dev{$f[0]}{wv} = ($f[14] & 12) / 4;						# 2nd 2 bits, SNMP write version
		$main::dev{$f[0]}{hc} = $f[14]  & 192;							# 8th bit, HC, 7th bit using RFC2233
		$main::dev{$f[0]}{rc} = $f[15];								# SNMP read community
		$main::dev{$f[0]}{cp} = $f[16];								# CLI port (0=new,1=impossible,22=ssh,anything else=telnet)
		$main::dev{$f[0]}{us} = $f[17];
		$main::dev{$f[0]}{ic} = $f[18];
		$main::dev{$f[0]}{cpu}= $f[20];
		$main::dev{$f[0]}{mcp}= $f[21];
		$main::dev{$f[0]}{tmp}= $f[22];
		$main::dev{$f[0]}{cuv}= $f[23];
		$main::dev{$f[0]}{cul}= $f[24];
		$main::dev{$f[0]}{so} = $f[25];
		$main::dev{$f[0]}{wc} = $f[26];								# SNMP write community
		$main::dev{$f[0]}{opt}= $f[27];
		$main::dev{$f[0]}{siz}= $f[28];
		$main::dev{$f[0]}{stk}= $f[29];
		$main::dev{$f[0]}{mpw}= $f[30];
		$main::dev{$f[0]}{tpw}= $f[31];
		$main::dev{$f[0]}{cfc}= $f[32];
		$main::dev{$f[0]}{bup}= ($f[33])?substr($f[33],0,1):'?';
		$main::dev{$f[0]}{cst}= ($f[33])?substr($f[33],1,1):'?';

		$main::dev{$f[0]}{pls} = $main::dev{$f[0]}{ls};						# Preserve lastseen for calculations
		$main::dev{$f[0]}{pip} = $main::dev{$f[0]}{ip};

		$misc::seedini{$main::dev{$f[0]}{ip}}{rv} = $main::dev{$f[0]}{rv};			# Tie comm & ver to IP,
		$misc::seedini{$main::dev{$f[0]}{ip}}{rc} = $main::dev{$f[0]}{rc};
		$misc::seedini{$main::dev{$f[0]}{ip}}{na} = $f[0];
		$misc::seedini{$main::dev{$f[0]}{oi}}{rv} = $main::dev{$f[0]}{rv};			# it's all we have at first
		$misc::seedini{$main::dev{$f[0]}{oi}}{rc} = $main::dev{$f[0]}{rc};
		$misc::seedini{$main::dev{$f[0]}{oi}}{na} = $f[0];

		$misc::map{$main::dev{$f[0]}{ip}}{na} = $f[0] if $main::dev{$f[0]}{os} eq 'MSMc';	# MSM APs always send their SN via CDP!

		$npdev++;
	}
	$sth->finish if $sth;

	&misc::Prt("RDEV:$npdev devices read from $misc::dbname.devices\n");
	return $npdev;
}


=head2 FUNCTION ReadLink()

Read links table.

B<Options> match statement

B<Globals> main::link

B<Returns> -

=cut
sub ReadLink{

	my $nlink = 0;
	my $where = ($_[0])?"WHERE $_[0]":'';

	my $sth = $dbh->prepare("SELECT * FROM links $where");
	$sth->execute();
	while ((my @l) = $sth->fetchrow_array) {
		$main::link{$l[1]}{$l[2]}{$l[3]}{$l[4]}{bw} = $l[5];
		$main::link{$l[1]}{$l[2]}{$l[3]}{$l[4]}{ty} = $l[6];
		$main::link{$l[1]}{$l[2]}{$l[3]}{$l[4]}{pw} = $l[7];
		$main::link{$l[1]}{$l[2]}{$l[3]}{$l[4]}{du} = $l[8];
		$main::link{$l[1]}{$l[2]}{$l[3]}{$l[4]}{vl} = $l[9];
		$nlink++;
	}
	$sth->finish if $sth;

	&misc::Prt("RLNK:$nlink links ($where) read from $misc::dbname.links\n");
	return $nlink;
}


=head2 FUNCTION ReadNod()

Read nodes table.

B<Options> match statement

B<Globals> main::nod

B<Returns> -

=cut
sub ReadNod{

	my $nnod = 0;
	my $where = ($_[0])?"WHERE $_[0]":"";

	my $sth = $dbh->prepare("SELECT * FROM nodes $where");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		$main::nod{$f[2]}{na} = $f[0];
		$main::nod{$f[2]}{ip} = &misc::Dec2Ip($f[1]);
		$main::nod{$f[2]}{nv} = $f[3];
		$main::nod{$f[2]}{fs} = $f[4];
		$main::nod{$f[2]}{ls} = $f[5];
		$main::nod{$f[2]}{dv} = $f[6];
		$main::nod{$f[2]}{if} = $f[7];
		$main::nod{$f[2]}{vl} = $f[8];
		$main::nod{$f[2]}{im} = $f[9];
		$main::nod{$f[2]}{iu} = $f[10];
		$main::nod{$f[2]}{ic} = $f[11];
		$main::nod{$f[2]}{au} = $f[12];
		$main::nod{$f[2]}{ac} = $f[13];
		$main::nod{$f[2]}{al} = $f[14];
		$main::nod{$f[2]}{av} = $f[15];
		if($f[16]){
			$main::nod{$f[2]}{i6} = ($misc::backend eq 'Pg' and $s6ok)?inet_pton(AF_INET6,$f[16]):$f[16];
		}else{
			$main::nod{$f[2]}{i6} = '';
		}
		$main::nod{$f[2]}{tp} = $f[17];
		$main::nod{$f[2]}{up} = $f[18];
		$main::nod{$f[2]}{os} = $f[19];
		$main::nod{$f[2]}{ty} = $f[20];
		$main::nod{$f[2]}{ou} = $f[21];
		$main::nod{$f[2]}{us} = $f[22];
		$nnod++;
	}
	$sth->finish if $sth;

	&misc::Prt("RNOD:$nnod nodes read ($where) from $misc::dbname.nodes\n");
	return $nnod;
}


=head2 FUNCTION BackupCfg()

Backup configuration and any changes.

B<Options> device name

B<Globals> -

B<Returns> -

=cut
sub BackupCfg{

	my ($dv) = @_;
	my $cfg  = join("\n",@misc::curcfg);
	my $chg  = "";

	my $sth = $dbh->prepare("SELECT config,changes FROM configs where device = '$dv'");
	$sth->execute();

	if($sth->rows == 0 and !$main::opt{'t'}){								# No previous config found, therefore write new.
		$sth = $dbh->prepare("INSERT INTO configs(device,config,changes,time) VALUES ( ?,?,?,? )");
		$sth->execute ($dv,$cfg,$chg,$main::now);
		&misc::WriteCfg($dv) if defined $main::opt{'B'};
		&misc::Prt('','Bn');
		$misc::mq += &mon::Event('B','100','cfgn',$dv,$dv,"New config with ".length($cfg)." characters added");
	}elsif($sth->rows == 1){									# Previous config found, get changes
		my @pc = $sth->fetchrow_array;
		my @pcfg = split(/\n/,$pc[0]);
		my $achg = &misc::CfgChanges(\@pcfg, \@misc::curcfg);
		if(!$main::opt{'t'}){
			if($achg){									# Only write new, if changed
				$chg  = $pc[1] . "#--- " . localtime($main::now) ." ---#\n". $achg;
				$dbh->do("DELETE FROM configs where device = '$dv'");
				$sth = $dbh->prepare("INSERT INTO configs(device,config,changes,time) VALUES ( ?,?,?,? )");
				$sth->execute ($dv,$cfg,$chg,$main::now);
				&misc::WriteCfg($dv) if defined $main::opt{'B'};
				my $len = length($achg);
				$achg =~ s/["']//g;
				my $msg = "Config changed by $len characters:\n$achg";
				my $lvl = ($len > 1000)?100:50;
				$misc::mq += &mon::Event('B',$lvl,'cfgc',$dv,$dv,$msg);
				&misc::Prt('',"Bu");
			} else {
			    &misc::WriteCfg($dv) if defined $main::opt{'B'} and ! -e "$misc::nedipath/conf/$dv";	# Write config file anyway if no dev folder exists
			}
		}
	}
	$sth->finish if $sth;
}


=head2 FUNCTION WriteDev()

Write a device to devices table.

B<Options> devicename

B<Globals> -

B<Returns> -

=cut
sub WriteDev{

	my ($dv) = @_;

	$dbh->do("DELETE FROM  devices where device = '$dv'");
	$sth = $dbh->prepare("INSERT INTO devices(	device,devip,serial,type,firstdis,lastdis,services,
							description,devos,bootimage,location,contact,
							devgroup,devmode,snmpversion,readcomm,cliport,login,icon,
							origip,cpu,memcpu,temp,cusvalue,cuslabel,sysobjid,writecomm,devopts,size,stack,maxpoe,totpoe,cfgchange,cfgstatus
							) VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )");
	$sth->execute (	$dv,
			&misc::Ip2Dec($main::dev{$dv}{ip}),
			(defined $main::dev{$dv}{sn})?$main::dev{$dv}{sn}:'',
			(defined $main::dev{$dv}{ty})?substr($main::dev{$dv}{ty},0,31):'',		# substr here to catch all sources
			$main::dev{$dv}{fs},
			$main::dev{$dv}{ls},
			(defined $main::dev{$dv}{sv})?$main::dev{$dv}{sv}:0,
			(defined $main::dev{$dv}{de})?$main::dev{$dv}{de}:'',
			(defined $main::dev{$dv}{os})?$main::dev{$dv}{os}:'',
			(defined $main::dev{$dv}{bi})?$main::dev{$dv}{bi}:'',
			(defined $main::dev{$dv}{lo})?$main::dev{$dv}{lo}:'',
			(defined $main::dev{$dv}{co})?$main::dev{$dv}{co}:'',
			(defined $main::dev{$dv}{dg})?$main::dev{$dv}{dg}:'',
			(defined $main::dev{$dv}{dm})?$main::dev{$dv}{dm}:0,
			((defined $main::dev{$dv}{rv})?$main::dev{$dv}{rv}:0) + ((defined $main::dev{$dv}{wv})?$main::dev{$dv}{wv}:0) * 4 + ((defined $main::dev{$dv}{hc})?$main::dev{$dv}{hc}:0),
			(defined $main::dev{$dv}{rc})?$main::dev{$dv}{rc}:'',
			(defined $main::dev{$dv}{cp})?$main::dev{$dv}{cp}:0,
			(defined $main::dev{$dv}{us})?$main::dev{$dv}{us}:'',
			&misc::DevIcon($main::dev{$dv}{sv},$main::dev{$dv}{ic}),
			&misc::Ip2Dec($main::dev{$dv}{oi}),
			(defined $main::dev{$dv}{cpu})?$main::dev{$dv}{cpu}:0,
			(defined $main::dev{$dv}{mcp})?$main::dev{$dv}{mcp}:0,
			(defined $main::dev{$dv}{tmp})?$main::dev{$dv}{tmp}:0,
			(defined $main::dev{$dv}{cuv})?$main::dev{$dv}{cuv}:0,
			(defined $main::dev{$dv}{cul})?$main::dev{$dv}{cul}:'',
			(defined $main::dev{$dv}{so})?$main::dev{$dv}{so}:'',
			(defined $main::dev{$dv}{wc})?$main::dev{$dv}{wc}:'',
			(defined $main::dev{$dv}{opt})?$main::dev{$dv}{opt}:'',
			(defined $main::dev{$dv}{siz})?$main::dev{$dv}{siz}:0,
			($main::dev{$dv}{stk})?$main::dev{$dv}{stk}:1,
			(defined $main::dev{$dv}{mpw})?$main::dev{$dv}{mpw}:0,
			(defined $main::dev{$dv}{tpw})?$main::dev{$dv}{tpw}:0,
			(defined $main::dev{$dv}{cfc})?$main::dev{$dv}{cfc}:0,
			((defined $main::dev{$dv}{bup})?$main::dev{$dv}{bup}:'-').((defined $main::dev{$dv}{cst})?$main::dev{$dv}{cst}:'-')
			);
	$dbh->commit;
	$sth->finish if $sth;

	&misc::Prt("WDEV:$dv written to $misc::dbname.devices\n");
	my $regex = ($misc::backend eq 'Pg')?'~':'regexp';
	my $ldel = &db::Delete('links',"device = '$dv' AND linktype $regex '^[a-z]{1,2}DP\$' AND time < '$misc::retire'");
	$mq += &mon::Event('l',100,'nedl',$dv,$dv,"$ldel links older than ".localtime($misc::retire)." have been retired") if $ldel;
}

=head2 FUNCTION ReadAddr()

Reads IP and MAC addresses of all IF in DB for topology awareness. 

B<Options> -

B<Globals> misc::ifmac, misc::ifip

B<Returns> -

=cut
sub ReadAddr{

	my $nmac = 0;
	my $nip  = 0;
	my $nip6 = 0;
		
	my $sth = $dbh->prepare("SELECT device,ifmac,ifname FROM interfaces where ifmac != ''");
	$sth->execute();
	while((my @i) = $sth->fetchrow_array){
		push @{$misc::ifmac{$i[1]}{$i[0]}},$i[2];
 		$nmac++;
	}
	$sth->finish if $sth;

	$sth = $dbh->prepare("SELECT device,ifname,inet_ntoa(ifip),ifip6 FROM networks where ifip != 2130706433");# Ignore 127.0.0.1
	$sth->execute();
	while ((my @i) = $sth->fetchrow_array) {
		if($i[3]){
			my $ip6 = ($misc::backend eq 'Pg' and $s6ok)?inet_pton(AF_INET6,$i[3]):$i[3];
			push @{$misc::ifip{$ip6}{$i[0]}},$i[1];
			$nip6++;
		}else{
			push @{$misc::ifip{$i[2]}{$i[0]}},$i[1];
			$nip++;
		}
	}
	$sth->finish if $sth;

	&misc::Prt("RADDR:$nmac MAC, $nip IP and $nip6 IPv6 addresses read.\n");
}

=head2 FUNCTION ReadInt()

Reads IF information.

B<Options> devicename

B<Globals> main::int

B<Returns> -

=cut
sub ReadInt{

	my $where   = ($_[0])?"WHERE $_[0]":"";
	my $nint = 0;

	my $sth = $dbh->prepare("SELECT * FROM interfaces $where");
	$sth->execute();
	while((my @i) = $sth->fetchrow_array){
		$main::int{$i[0]}{$i[2]}{ina} = $i[1];
		$main::int{$i[0]}{$i[2]}{lty} = $i[3];
		$main::int{$i[0]}{$i[2]}{typ} = $i[4];
		$main::int{$i[0]}{$i[2]}{mac} = $i[5];
		$main::int{$i[0]}{$i[2]}{des} = $i[6];
		$main::int{$i[0]}{$i[2]}{ali} = $i[7];
		$main::int{$i[0]}{$i[2]}{sta} = $i[8];
		$main::int{$i[0]}{$i[2]}{spd} = $i[9];
		$main::int{$i[0]}{$i[2]}{dpx} = $i[10];
		$main::int{$i[0]}{$i[2]}{vid} = $i[11];
		$main::int{$i[0]}{$i[2]}{ioc} = $i[12];
		$main::int{$i[0]}{$i[2]}{ier} = $i[13];
		$main::int{$i[0]}{$i[2]}{ooc} = $i[14];
		$main::int{$i[0]}{$i[2]}{oer} = $i[15];
		$main::int{$i[0]}{$i[2]}{dio} = $i[16];
		$main::int{$i[0]}{$i[2]}{die} = $i[17];
		$main::int{$i[0]}{$i[2]}{doo} = $i[18];
		$main::int{$i[0]}{$i[2]}{doe} = $i[19];
		$main::int{$i[0]}{$i[2]}{idi} = $i[20];
		$main::int{$i[0]}{$i[2]}{odi} = $i[21];
		$main::int{$i[0]}{$i[2]}{did} = $i[22];
		$main::int{$i[0]}{$i[2]}{dod} = $i[23];
		$main::int{$i[0]}{$i[2]}{ibr} = $i[24];
		$main::int{$i[0]}{$i[2]}{dib} = $i[25];
		$main::int{$i[0]}{$i[2]}{chg} = $i[26];
		$main::int{$i[0]}{$i[2]}{poe} = $i[27];
		$main::int{$i[0]}{$i[2]}{tra} = $i[29];
		$main::int{$i[0]}{$i[2]}{bra} = $i[30];
		$main::int{$i[0]}{$i[2]}{mcf} = $i[31];

		$main::int{$i[0]}{$i[2]}{plt} = $i[3];							# Needed for link tracking in misc::CheckIf
		$main::int{$i[0]}{$i[2]}{pst} = $i[8];
		$main::int{$i[0]}{$i[2]}{psp} = $i[9];
		$main::int{$i[0]}{$i[2]}{pdp} = $i[10];
		$main::int{$i[0]}{$i[2]}{pvi} = $i[11];
		$main::int{$i[0]}{$i[2]}{pcg} = $i[26];
		$main::int{$i[0]}{$i[2]}{pco} = $i[28];

		$nint++;
	}
	$sth->finish if $sth;

	&misc::Prt("RIF :$nint IF read ($where) from $misc::dbname.interfaces\n");
	return $nint;
}


=head2 FUNCTION WriteInt()

Write the interfaces table, calculate deltas and notify if desired.

B<Options> devicename

B<Globals> main::int

B<Returns> -

=cut
sub WriteInt{

	my ($dv,$skip) = @_;
	my $tint = 0;

	$dbh->do("DELETE FROM  interfaces where device = '$dv'");
	$sth = $dbh->prepare("INSERT INTO interfaces(	device,ifname,ifidx,linktype,iftype,ifmac,ifdesc,alias,ifstat,speed,duplex,pvid,
							inoct,inerr,outoct,outerr,dinoct,dinerr,doutoct,douterr,indis,outdis,dindis,doutdis,inbrc,dinbrc,lastchg,poe,comment)
							VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )");
	foreach my $i ( sort keys %{$main::int{$dv}} ){
		if(!$main::int{$dv}{$i}{new}){
			&misc::Prt("WIF :Index $i not found, not writing\n");
		}else{
			&misc::CheckIF($dv,$i,$skip);
			$sth->execute (	$dv,
					$main::int{$dv}{$i}{ina},
					$i,
					($main::int{$dv}{$i}{lty})?$main::int{$dv}{$i}{lty}:'',
					($main::int{$dv}{$i}{typ})?$main::int{$dv}{$i}{typ}:0,
					($main::int{$dv}{$i}{mac})?$main::int{$dv}{$i}{mac}:'',
					($main::int{$dv}{$i}{des})?substr($main::int{$dv}{$i}{des},0,255):'',
					($main::int{$dv}{$i}{ali})?substr($main::int{$dv}{$i}{ali},0,64):'',
					($main::int{$dv}{$i}{sta})?$main::int{$dv}{$i}{sta}:0,
					($main::int{$dv}{$i}{spd})?$main::int{$dv}{$i}{spd}:0,
					($main::int{$dv}{$i}{dpx})?$main::int{$dv}{$i}{dpx}:'',
					($main::int{$dv}{$i}{vid})?$main::int{$dv}{$i}{vid}:0,
					($main::int{$dv}{$i}{ioc})?$main::int{$dv}{$i}{ioc}:0,
					($main::int{$dv}{$i}{ier})?$main::int{$dv}{$i}{ier}:0,
					($main::int{$dv}{$i}{ooc})?$main::int{$dv}{$i}{ooc}:0,
					($main::int{$dv}{$i}{oer})?$main::int{$dv}{$i}{oer}:0,
					($main::int{$dv}{$i}{dio})?$main::int{$dv}{$i}{dio}:0,
					($main::int{$dv}{$i}{die})?$main::int{$dv}{$i}{die}:0,
					($main::int{$dv}{$i}{doo})?$main::int{$dv}{$i}{doo}:0,
					($main::int{$dv}{$i}{doe})?$main::int{$dv}{$i}{doe}:0,
					($main::int{$dv}{$i}{idi})?$main::int{$dv}{$i}{idi}:0,
					($main::int{$dv}{$i}{odi})?$main::int{$dv}{$i}{odi}:0,
					($main::int{$dv}{$i}{did})?$main::int{$dv}{$i}{did}:0,
					($main::int{$dv}{$i}{dod})?$main::int{$dv}{$i}{dod}:0,
					($main::int{$dv}{$i}{ibr})?$main::int{$dv}{$i}{ibr}:0,
					($main::int{$dv}{$i}{dib})?$main::int{$dv}{$i}{dib}:0,
					($main::int{$dv}{$i}{chg})?$main::int{$dv}{$i}{chg}:0,
					($main::int{$dv}{$i}{poe})?$main::int{$dv}{$i}{poe}:0,
					($main::int{$dv}{$i}{com})?substr($main::int{$dv}{$i}{com},0,255):'' );
			$tint++;
		}
	}
	$dbh->commit;
	$sth->finish if $sth;

	&misc::Prt("WIF :$tint interfaces written to $misc::dbname.interfaces\n");
}


=head2 FUNCTION WriteMod()

Write the modules table, detect changes and notify if desired.

B<Options> devicename

B<Globals> -

B<Returns> -

=cut
sub WriteMod{

	my ($dv) = @_;
	my $nmod = 0;
	my %dbmod= ();

	if(exists $main::mon{$dv} and $misc::notify =~ /m/i){						# Track existing mods if enabled
		my $sth = $dbh->prepare("SELECT * FROM modules WHERE device = '$dv'");
		$sth->execute();
		while ((my @f) = $sth->fetchrow_array) {
			$dbmod{$f[8]} = 1;
			if(exists $main::mod{$dv}{$f[8]}){						# Check idx to avoid defining entry..
				if($f[3] ne $main::mod{$dv}{$f[8]}{de}){				# ..this would define!
					$misc::mq += &mon::Event('M',150,'nedo',$dv,$dv,"Module $f[3] SN:$f[4] in $f[1] was changed to a $main::mod{$dv}{$f[8]}{de} with SN:$main::mod{$dv}{$f[8]}{sn}");
				}elsif($f[4] and $f[4] ne $main::mod{$dv}{$f[8]}{sn}){
					$misc::mq += &mon::Event('M',150,'nedo',$dv,$dv,"Module $f[3] SN:$f[4] in $f[1] got replaced with same model and SN:$main::mod{$dv}{$f[8]}{sn}");
				}
			}else{
				$misc::mq += &mon::Event('M',150,'nedo',$dv,$dv,"Module $f[3] SN:$f[4] in $f[1] has been removed");
			}
		}
	}
	$sth->finish if $sth;
	$dbh->do("DELETE FROM  modules where device = '$dv'");
	my $sth = $dbh->prepare("INSERT INTO modules(device,slot,model,moddesc,serial,hw,fw,sw,modidx,modclass,status) VALUES ( ?,?,?,?,?,?,?,?,?,?,? )");
	foreach my $i ( sort keys %{$main::mod{$dv}} ){
		$sth->execute (	$dv,
				$main::mod{$dv}{$i}{sl},
				$main::mod{$dv}{$i}{mo},
				$main::mod{$dv}{$i}{de},
				$main::mod{$dv}{$i}{sn},
				$main::mod{$dv}{$i}{hw},
				$main::mod{$dv}{$i}{fw},
				$main::mod{$dv}{$i}{sw},
				$i,
				$main::mod{$dv}{$i}{mc},
				$main::mod{$dv}{$i}{st}
				);
		if(exists $main::mon{$dv} and $main::dev{$dv}{fs} ne $main::now and !exists $dbmod{$i}){
			$misc::mq += &mon::Event('M',150,'nedo',$dv,$dv,"New $main::mod{$dv}{$i}{de} module with SN:$main::mod{$dv}{$i}{sn} found in $main::mod{$dv}{$i}{sl}");
		}
		$nmod++;
	}
	$dbh->commit;
	$sth->finish if $sth;

	&misc::Prt("WMOD:$nmod modules written to $misc::dbname.modules\n");
}


=head2 FUNCTION WriteVlan()

Rewrites the vlans of a given device.

B<Options> devicename

B<Globals> -

B<Returns> -

=cut
sub WriteVlan{

	my ($dv) = @_;
	my $nvlans = 0;

	$dbh->do("DELETE FROM  vlans where device = '$dv'");
	my $sth = $dbh->prepare("INSERT INTO vlans(device,vlanid,vlanname) VALUES ( ?,?,? )");
	foreach my $i ( sort keys %{$main::vlan{$dv}} ){
		$sth->execute ( $dv,$i,$main::vlan{$dv}{$i} );
		$nvlans++;
	}
	$dbh->commit;
	$sth->finish if $sth;

	&misc::Prt("WVLN:$nvlans vlans written to $misc::dbname.vlans\n");
}


=head2 FUNCTION WriteNet()

Rewrites the networks of a given device.

B<Options> devicename

B<Globals> -

B<Returns> -

=cut
sub WriteNet{

	my ($dv) = @_;
	my $nip  = 0;

	$dbh->do("DELETE FROM  networks where device = '$dv'");
	my $sth = $dbh->prepare("INSERT INTO networks( device,ifname,ifip,ifip6,prefix,vrfname,status ) VALUES ( ?,?,?,?,?,?,? )");
	foreach my $n ( sort keys %{$main::net{$dv}} ){
		$sth->execute (	$dv,
				$main::net{$dv}{$n}{ifn},
				((!$main::net{$dv}{$n}{ip6})?&misc::Ip2Dec($n):0),
				IPtoDB($main::net{$dv}{$n}{ip6},$n),
				$main::net{$dv}{$n}{pfx},
				$main::net{$dv}{$n}{vrf},
				$main::net{$dv}{$n}{sta} );
		$nip++;
	}
	$dbh->commit;
	$sth->finish if $sth;

	&misc::Prt("WNET:$nip networks written to $misc::dbname.networks\n");
}


=head2 FUNCTION WriteLink()

Writes the links of a given device. Will just return without argument
or if there are no links for this device.

B<Options> devicename

B<Globals> -

B<Returns> -

=cut
sub WriteLink{

	my ($dv,$i,$ne,$ni) = @_;

	my $sth = $dbh->prepare("SELECT * FROM links WHERE device='$dv' AND ifname='$i' AND neighbor='$ne' AND nbrifname='$ni' AND linktype='$main::link{$dv}{$i}{$ne}{$ni}{ty}'");
	$sth->execute();
	if($sth->rows){
		&misc::Prt("WLNK:Link exists from $dv,$i to $ne,$ni. Updating $main::link{$dv}{$i}{$ne}{$ni}{ty} link\n");
		$sth = $dbh->prepare("UPDATE links SET	device=?,ifname=?,neighbor=?,nbrifname=?,bandwidth=?,linktype=?,
					linkdesc=?,nbrduplex=?,nbrvlanid=?,time=? WHERE device='$dv' AND ifname='$i' AND neighbor='$ne' AND nbrifname='$ni' AND linktype='$main::link{$dv}{$i}{$ne}{$ni}{ty}'");
		$sth->execute (	$dv,$i,$ne,$ni,
				$main::link{$dv}{$i}{$ne}{$ni}{bw},
				$main::link{$dv}{$i}{$ne}{$ni}{ty},
				$main::link{$dv}{$i}{$ne}{$ni}{de},
				$main::link{$dv}{$i}{$ne}{$ni}{du},
				$main::link{$dv}{$i}{$ne}{$ni}{vl},
				$main::now );
	}else{
		&misc::Prt("WLNK:No link from $dv,$i to $ne,$ni. Creating $main::link{$dv}{$i}{$ne}{$ni}{ty} link\n");
		$sth = $dbh->prepare("INSERT INTO links(device,ifname,neighbor,nbrifname,bandwidth,linktype,linkdesc,nbrduplex,nbrvlanid,time) VALUES ( ?,?,?,?,?,?,?,?,?,? )");
		$sth->execute (	$dv,$i,$ne,$ni,
				$main::link{$dv}{$i}{$ne}{$ni}{bw},
				$main::link{$dv}{$i}{$ne}{$ni}{ty},
				$main::link{$dv}{$i}{$ne}{$ni}{de},
				$main::link{$dv}{$i}{$ne}{$ni}{du},
				$main::link{$dv}{$i}{$ne}{$ni}{vl},
				$main::now );
	}
	$dbh->commit;
	$sth->finish if $sth;
}


=head2 FUNCTION UnStock()

Update Devices/Modules in Stock, which are discovered on the network.

B<Options> devicename

B<Globals> -

B<Returns> -

=cut
sub UnStock{

	my $dv = $_[0];

	if( $dbh->do("UPDATE stock SET time='$main::now',comment='Discovered as $dv with IP $main::dev{$dv}{ip}',state=100 where serial = '$main::dev{$dv}{sn}' and state != 100") + 0){
		&misc::Prt("STOK:Discovered device $main::dev{$dv}{sn} set active in $misc::dbname.stock\n");
	}
	foreach my $i ( sort keys %{$main::mod{$dv}} ){
		if($main::mod{$dv}{$i}{sn}){
			if( $dbh->do("UPDATE stock SET time='$main::now',comment='Discovered in $dv $main::mod{$dv}{$i}{sl}',state=100 where serial = '$main::mod{$dv}{$i}{sn}' and state != 100") + 0){
				&misc::Prt("STOK:Discovered module $main::mod{$dv}{$i}{sn} set active in $misc::dbname.stock\n");
			}
		}
	}
}


=head2 FUNCTION WriteNod()

Writes the nodes table by only connecting once and preparing all actions combined to scale for large networks using multiple threads.
In addition entries from IF and IP track tables are deleted upon retiring a node.

B<Options> -

B<Globals> main::nod

B<Returns> -

=cut
sub WriteNod{

	my $dnod = my $inod = my $unod = 0;

	my $std = $dbh->prepare("DELETE FROM nodes WHERE mac=?");
	my $stf = $dbh->prepare("DELETE FROM iftrack WHERE mac=?");
	my $sta = $dbh->prepare("DELETE FROM iptrack WHERE mac=?");

	my $sti = $dbh->prepare("INSERT INTO nodes(	name,nodip,mac,oui,firstseen,lastseen,device,ifname,vlanid,ifmetric,ifupdate,ifchanges,
							ipupdate,ipchanges,iplost,arpval,nodip6,tcpports,udpports,nodtype,nodos,osupdate,noduser) VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )");

	my $stu = $dbh->prepare("UPDATE nodes SET	name=?,nodip=?,mac=?,oui=?,firstseen=?,lastseen=?,device=?,ifname=?,vlanid=?,ifmetric=?,ifupdate=?,ifchanges=?,
							ipupdate=?,ipchanges=?,iplost=?,arpval=?,nodip6=?,tcpports=?,udpports=?,nodtype=?,nodos=?,osupdate=?,noduser=? WHERE mac=?");

	foreach my $mcvl ( sort keys %main::nod ){							# Based on Lukas' idea
		if( $main::nod{$mcvl}{fs} > $main::nod{$mcvl}{ls} ){					# Not sure why, but can happen!
			my $msg = "Node $mcvl firstseen ".localtime($main::nod{$mcvl}{fs})." after lastseen ".localtime($main::nod{$mcvl}{ls});
			&misc::Prt("WNOD:$msg\n");
			&db::Insert('events','level,time,source,info,class',"'10','".time."','NeDi','$msg','bugx'") if $misc::notify =~ /x/;
			$main::nod{$mcvl}{fs} = $main::nod{$mcvl}{ls};
		}
		if($main::nod{$mcvl}{ls} < $misc::retire){
			$std->execute($mcvl);
			$sta->execute($mcvl);
			$stf->execute($mcvl);
			$dnod++;
		}elsif($main::nod{$mcvl}{fs} == $main::now){
			$sti->execute(	$main::nod{$mcvl}{na},
					&misc::Ip2Dec($main::nod{$mcvl}{ip}),
					$mcvl,
					$main::nod{$mcvl}{nv},
					$main::nod{$mcvl}{fs},
					$main::nod{$mcvl}{ls},
					$main::nod{$mcvl}{dv},
					$main::nod{$mcvl}{if},
					$main::nod{$mcvl}{vl},
					$main::nod{$mcvl}{im},
					$main::nod{$mcvl}{iu},
					$main::nod{$mcvl}{ic},
					$main::nod{$mcvl}{au},
					$main::nod{$mcvl}{ac},
					$main::nod{$mcvl}{al},
					$main::nod{$mcvl}{av},
					IPtoDB(1,$main::nod{$mcvl}{i6}),
					$main::nod{$mcvl}{tp},
					$main::nod{$mcvl}{up},
					$main::nod{$mcvl}{os},
					$main::nod{$mcvl}{ty},
					$main::nod{$mcvl}{ou},
					$main::nod{$mcvl}{us} );
			$inod++;
		}elsif($main::nod{$mcvl}{ls} == $main::now){
			$stu->execute(	$main::nod{$mcvl}{na},
					&misc::Ip2Dec($main::nod{$mcvl}{ip}),
					$mcvl,
					$main::nod{$mcvl}{nv},
					$main::nod{$mcvl}{fs},
					$main::nod{$mcvl}{ls},
					$main::nod{$mcvl}{dv},
					$main::nod{$mcvl}{if},
					$main::nod{$mcvl}{vl},
					$main::nod{$mcvl}{im},
					$main::nod{$mcvl}{iu},
					$main::nod{$mcvl}{ic},
					$main::nod{$mcvl}{au},
					$main::nod{$mcvl}{ac},
					$main::nod{$mcvl}{al},
					$main::nod{$mcvl}{av},
					IPtoDB(1,$main::nod{$mcvl}{i6}),
					$main::nod{$mcvl}{tp},
					$main::nod{$mcvl}{up},
					$main::nod{$mcvl}{os},
					$main::nod{$mcvl}{ty},
					$main::nod{$mcvl}{ou},
					$main::nod{$mcvl}{us},
					$mcvl );

			$unod++;
		}

	}
	$dbh->commit;
	$std->finish if $std;
	$stf->finish if $stf;
	$sta->finish if $sta;
	$sti->finish if $sti;
	$stu->finish if $stu;

	&misc::Prt("WNOD:$dnod nodes retired, $inod inserted and $unod updated in $misc::dbname.nodes\n");
}

=head2 FUNCTION ReadMon()

Read monitoring table.

B<Options> type = dev, devip(decimal) or node

B<Globals> main::mon

B<Returns> -

=cut
sub ReadMon{

	my $nmon  = 0;
	my $sth = "";

	if($_[0] =~ /^[0-9]+$/){									# For single dev (used in trap.pl)
		$sth = $dbh->prepare("SELECT * FROM monitoring WHERE monip = $_[0]");
	}elsif($_[0] eq 'dev'){
		$sth = $dbh->prepare("SELECT monitoring.*,type,snmpversion & 3,readcomm FROM monitoring LEFT OUTER JOIN devices ON (monitoring.name = devices.device ) WHERE class = 'dev'");
	}elsif($_[0] eq 'node'){
		$sth = $dbh->prepare("SELECT * FROM monitoring WHERE class = 'node'");
	}

	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		my $na = $f[0];
		my $ip = &misc::Dec2Ip($f[1]);
		$main::srcna{$ip} = $na;
		$main::mon{$na}{ip} = $ip;
		$main::mon{$na}{cl} = $f[2];
		$main::mon{$na}{te} = $f[3];
		$main::mon{$na}{to} = $f[4];
		$main::mon{$na}{tr} = $f[5];
		$main::mon{$na}{lk} = $f[6];
		$main::mon{$na}{st} = $f[7];
		$main::mon{$na}{lo} = $f[8];
		$main::mon{$na}{ok} = $f[9];
		$main::mon{$na}{ly} = $f[10];
		$main::mon{$na}{lm} = $f[11];
		$main::mon{$na}{la} = $f[12];
		$main::mon{$na}{up} = $f[13];
		$main::mon{$na}{al} = $f[14];
		$main::mon{$na}{ef} = $f[15];
		$main::mon{$na}{el} = $f[16];
		$main::mon{$na}{ed} = $f[17];
		$main::mon{$na}{dy} = $f[18];
		$main::mon{$na}{dv} = $f[19];								# Used for viewdev
		$main::mon{$na}{no} = $f[20];								# Per Target notify string
		$main::mon{$na}{nr} = $f[21];								# Per Target no-reply threshold
		$main::mon{$na}{lw} = $f[22];
		$main::mon{$na}{ca} = $f[23];
		$main::mon{$na}{ma} = $f[24];
		$main::mon{$na}{ta} = $f[25];
		$main::mon{$na}{pw} = $f[26];
		$main::mon{$na}{ap} = $f[27];
		$main::mon{$na}{sa} = $f[28];
		$main::mon{$na}{dc} = 0;								# Dependendant count
		$main::mon{$na}{ds} = 'up';								# Dependency status
		$main::mon{$na}{ty} = ($f[2] eq 'dev')?$f[29]:0;
		$main::mon{$na}{rv} = ($f[2] eq 'dev')?$f[30]:0;
		$main::mon{$na}{rc} = ($f[2] eq 'dev')?$f[31]:'';
		$nmon++;
	}
	$sth->finish if $sth;

	&misc::Prt("RMON:$nmon entries ($_[0]) read from $misc::dbname.monitoring\n");
	return $nmon;
}


=head2 FUNCTION ReadUser()

Read users table.

B<Options> match statement

B<Globals> -

B<Returns> -

=cut
sub ReadUser{

	my $nusr  = 0;
	my $where = ($_[0])?"WHERE $_[0]":'';

	my $sth = $dbh->prepare("SELECT usrname,email,phone,viewdev FROM users $where");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		$main::usr{$f[0]}{ml} = $f[1];
		$main::usr{$f[0]}{ph} = $f[2];
		$main::usr{$f[0]}{ph} =~ s/\D//g;							# Strip anything that isn't a number
		$main::usr{$f[0]}{vd} = '';
		if($f[3]){
			my @vd =  split(' ', $f[3]);
			my $vdin = shift @vd;
			my $vdop = shift @vd;
			my $vdst = join(' ', @vd);
			$vdst =~ s/["']//g;								# pre 1.0.9 had quotes in string
			if($misc::backend eq 'Pg'){
				$vdin = "CAST($vdin AS text)" if $vdop =~ /~/;
			}else{
				if( $vdop eq '!~' ){
					$vdop = 'not regexp';
				}elsif( $vdop eq '~' ){
					$vdop = 'regexp';
				}
			}
			$main::usr{$f[0]}{vd} = "$vdin $vdop '$vdst'";
		}
		$main::usr{$f[0]}{sms}= '';
		@{$main::usr{$f[0]}{mail}} = ();
		$nusr++;
	}
	$sth->finish if $sth;

	&misc::Prt("RUSR:$nusr entries ($_[0]) read from $misc::dbname.users\n");
	return $nusr;
}


=head2 FUNCTION Insert()

Insert DB Record

B<Options> table, string of columns, string of values

B<Globals> -

B<Returns> -

=cut
sub Insert{# TODO consider using hashref as argument, with that this can be used for writing stuff with ' and " (like configs) or simply try dbh->quote!!!

	&misc::NagPipe($_[2]) if $_[0] eq 'events' and $misc::nagpipe;

	my $r = '(only testing)';
	unless($main::opt{'t'}){
		$r = $dbh->do("INSERT INTO $_[0] ($_[1]) VALUES ($_[2])") || die "ERR :INSERT INTO $_[0] ($_[1]) VALUES ($_[2])\n";
	}
	&misc::Prt("INS :$r ROWS INTO $_[0] ($_[1]) VALUES ($_[2])\n") if $main::opt{'d'};

	return $r;
}


=head2 FUNCTION Delete()

Delete DB Record.

B<Options> table,match statement

B<Globals> -

B<Returns> -

=cut
sub Delete{

	my $r = $dbh->do("DELETE FROM  $_[0] WHERE $_[1]") || die "ERR : DELETE FROM  $_[0] WHERE $_[1]\n";

	&misc::Prt("ERR :$dbh->errstr\n") if(!$r);							# Something went wrong
	$r = 0 if($r eq '0E0');										# 0E0 actually means 0

	&misc::Prt("DEL :$r ROWS FROM $_[0] WHERE $_[1]\n") if $main::opt{'d'};
	return $r;
}


=head2 FUNCTION Update()

Update DB value(s).

B<Options> table, set statement, match statement

B<Globals> -

B<Returns> result

=cut
sub Update{

	my ($table, $set, $match) = @_;

	my $r = $dbh->do("UPDATE $table SET $set WHERE $match") || die "ERR : UPDATE $table SET $set WHERE $match\n";

	&misc::Prt("UPDT:$r ROWS FROM $table SET $set WHERE $match\n") if $main::opt{'d'};
	return $r;
}

=head2 FUNCTION Select()

Select values from a table.

B<Options> table, [hashkey], columns, match statement, join, using column(s)

B<Globals> -

B<Returns> value if only 1 row and column is the result, hashref (if key provided) or arrayref otherwhise

=cut
sub Select{

	my ($t, $key, $c, $m, $j, $u) = @_;

	my $qry = ($c)?"SELECT $c FROM $t":"SELECT * FROM $t";
	$qry   .= ($j and $u)?" LEFT JOIN $j USING ($u)":"";
	$qry   .= ($m)?" WHERE $m":"";

	my $res = "";
	my $nre = 0;
	if($key){
		$res = $dbh->selectall_hashref($qry, $key);
		$nre = scalar keys %$res;
	}else{
		my $a = $dbh->selectall_arrayref($qry);
		$nre = scalar @$a;
		if($c !~ /[,*]/ and $nre == 1){								# dereference single values
			$res =  $$a[0][0];
		}elsif($nre == 0){
			$res = '';
		}else{
			$res = $a;
		}
	}

	if($main::opt{'d'}){
		&misc::Prt("DB  :$qry; ($nre results)\n");
		&misc::Prt(' '.&main::Dumper($res)."\n");
	}
	return $res;
}

1;
