#!/usr/bin/perl
#
# ccc.pl
# cisco contract checker v0.2
# get a Cisco Support Contract into nedi.cisco_contracts Table
#
# (c)2008 Andreas Wassatsch
# released under GPLv2
#
# This software is provided as-is, without any express or implied
# warranty. In no event will the author be held liable for any mental
# or physical damages arising from the use of this script.
#
# Howto:
# - check requirements: needs wget and Perl Modules DBD, DBI::mysql
# - adjust the credentials for Cisco CCO Access and your NeDi Database
#   (MySQL only, sorry)
# - run this script from cron on a weekly/monthly basis
#
# Changed vs v0.1:
# - we retrieve the full contract information now instead of
#   checking each serial number seperately for contract coverage
# - table cisco_contracts has been redesigned - do a drop table if you
#   upgrade from v0.1
# - eleminated previously required packages: w3m and Perl Module Date::Calc
# - since we fetch end_date, checking once a month should be enough

# Modules
#--------
use DBI;
use DBD::mysql;

# Credentials for Cisco CCO and Nedi Mysql DB
#--------------------------------------------
my $cco_user    = "your_cco_username";
my $cco_pass    = "your_cco_password";

my $mysql_db    = "nedi";
my $mysql_user  = "nedi";
my $mysql_pass  = "dbpa55";
my $mysql_host  = "localhost";

# list your Cisco contracts here
#----------------------------------------------------------
$contract[1] = "1234567";	# your Cisco contract number
# $contract[2] = "";		# optional - 2nd Contract
# $contract[3] = "";		# optional - 3rd Contract


# Open DB connection
#-------------------
$dsn = "DBI:mysql:database=$mysql_db;host=$mysql_host";
$dbh = DBI->connect($dsn, $mysql_user, $mysql_pass);

# create contract table if not exists
#------------------------------------
$table = ("CREATE TABLE IF NOT EXISTS `cisco_contracts` (
 `contract_number` varchar(16) NOT NULL,
 `service_level` varchar(40) NOT NULL,
 `contract_label` varchar(32) default NULL,
 `bill_to_name` varchar(32) default NULL,
 `address` varchar(40) default NULL,
 `city` varchar(32) default NULL,
 `state` varchar(16) default NULL,
 `zip_code` varchar(16) default NULL,
 `country` varchar(16) default NULL,
 `bill_to_contact` varchar(32) default NULL,
 `phone` varchar(16) default NULL,
 `email` varchar(40) default NULL,
 `site_id` varchar(15) default NULL,
 `site_name` varchar(15) default NULL,
 `site_address` varchar(40) default NULL,
 `address_line2` varchar(40) default NULL,
 `address_line3` varchar(40) default NULL,
 `site_city` varchar(40) default NULL,
 `site_state` varchar(16) default NULL,
 `site_zip` varchar(16) default NULL,
 `site_country` varchar(16) default NULL,
 `site_notes` varchar(40) default NULL,
 `site_label` varchar(40) default NULL,
 `site_contact` varchar(40) default NULL,
 `site_phone` varchar(16) default NULL,
 `site_email` varchar(40) default NULL,
 `product_number` varchar(32) NOT NULL,
 `serial_number` varchar(40) NOT NULL,
 `name_ip_address` varchar(32) default NULL,
 `description` varchar(64) default NULL,
 `product_type` varchar(32) default NULL,
 `begin_date` varchar(16) default NULL,
 `end_date` varchar(16) default NULL,
 `po_number` varchar(16) default NULL,
 `so_number` varchar(16) default NULL,
 PRIMARY KEY  (`serial_number`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");
$dbh->do($table);

# try to get contracts from Cisco Web Site
#-----------------------------------------
for ($i=1; $i<($#contract+1) ; $i++) {

	print "\nContract=$contract[$i]\n";

	$post = "AppName=ContractAgent&Form=ConManager&Function=DownloadContract&UserId=$cco_user&Auth=null&ConNum=$contract[$i]&ContractOrSite=Contract&Type=Summary&Download=Screen&Submit=Download";

	open(CISCO, "wget -q --keep-session-cookies --user=$cco_user --password=$cco_pass http://www.cisco.com/cgi-bin/front.x/scccibdispatch --post-data \'$post\' -O - |");
		@cisco = grep(!/^Contract Number/, <CISCO>);
	close(CISCO);

	if (grep(/\<html\>/, @cisco)) {
		print "sorry, couldn't download contract $contract[$i] - check your CCO access rights.\n"; 
	} else {

		foreach $line (@cisco) {
			(@val) = split(/\t/, $line);

			print "\t$val[26]\t$val[27]\t$val[1]\t$val[32]\n";

			$sql = "DELETE FROM cisco_contracts WHERE serial_number=\"$val[27]\"";
			$sth = $dbh->prepare($sql);
			$sth->execute();
	
			$sql = "INSERT INTO cisco_contracts VALUES (\'$val[0]\'";
			for($j=1; $j<($#val+1); $j++) {
				$sql .= ", \'$val[$j]\'";
			}
			$sql .= ")";
			
			if ($DEBUG) {
				print "$sql\n";
			}
	
			$sth = $dbh->prepare($sql);
			$sth->execute();
		}
	}
}

exit 0;
