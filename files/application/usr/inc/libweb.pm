=pod

=head1 LIBRARY
libweb.pm

LWP based Functions in order to fetch info from supported web only devices

=head2 AUTHORS

Remo Rickli & NeDi Community

=cut

package web;
use warnings;

use vars qw($lwpok);

eval 'use LWP::UserAgent;';
if ($@){
	&misc::Prt("WEB :LWP-UserAgent not available\n");
}else{
	$lwpok = 1;
	&misc::Prt("WEB :LWP-UserAgent loaded\n");
}


=head2 FUNCTION CiscoPhone()

Fetch info through webinterface of Cisco phones (tx Kyle Kniffin)

B<Options> device name

B<Globals> main::dev

B<Returns> 0 on success, 1 on failure

=cut
sub CiscoPhone{

	my ($na) = @_;

	my $ua = LWP::UserAgent->new;
	$ua->timeout($misc::timeout);

	my $response = $ua->get("http://$main::dev{$na}{ip}");
	if ($response->is_success) {
		my $devhtml = $response->content;
		$devhtml =~ s/[^\x20-\x7D]//gi;
		&misc::Prt("$devhtml\n\n") if  $main::opt{'D'};
		if( $devhtml =~ m/<b>#?([0-9]{2,10})<\/b>/i ){						# Find Extension on Cisco 7920,40,60,11,10,06
			$main::dev{$na}{co} = $1;
		}elsif( $devhtml =~ m/: ([0-9]{2,10})<\/name>/i ){					# Find Extension on Cisco 7937, tx Kstadler
			$main::dev{$_[0]}{co} = $1;
		}elsif( $devhtml =~ m/<td>([0-9]{2,10})<tr>/i ){					# Find Extension on Cisco 7912
			$main::dev{$na}{co} = $1;
		}

		if( $devhtml =~ m/<(b|strong)>(FCH\w+|INM\w+|PUC\w+)<\/(b|strong)>/i ){			# Find Serial  on Cisco 7920,40,60,11,10,06 8945, 9951,71
			$main::dev{$na}{sn} = $2;
		}elsif( $devhtml =~ m/:    (000\w+)<\/name>/i ){					# Find Serial  on Cisco 7937
			$main::dev{$na}{sn} = $1;
		}elsif( $devhtml =~ m/<td>(FCH\w+|INM\w+)<tr>/i ){					# Find Serial  on Cisco 7912
			$main::dev{$na}{sn} = $1;
		}

		if( $devhtml =~ m/<B>CP-CKEM<\/B><\/TD><\/TR><TR><TD><B> <\/B><\/TD><td width=20><\/TD><TD><B>(V\w+)<\/B><\/TD><\/TR><TR><TD><B> <\/B><\/TD><td width=20><\/TD><TD><B>(FCH\w+)<\/B>/i){
			$main::mod{$na}{1}{sl} = '1';
			$main::mod{$na}{1}{mo} = 'KEM';
			$main::mod{$na}{1}{de} = 'Key Expansion Module';
			$main::mod{$na}{1}{sn} = $2;
			$main::mod{$na}{1}{hw} = $1;
			$main::mod{$na}{1}{fw} = '';
			$main::mod{$na}{1}{sw} = '';
			$main::mod{$na}{1}{mc} = '90';
			&misc::Prt("LWP :Key Expansion Module SN=$2\n");
		}
		if( $devhtml =~ m/<b>CP-CAM-(\w)=(\w+)(V[0-9]+)<\/b>/i){
			$main::mod{$na}{3}{sl} = '1';
			$main::mod{$na}{3}{mo} = "CP-CAM-$1";
			$main::mod{$na}{3}{de} = 'Cisco Unified Video Camera';
			$main::mod{$na}{3}{sn} = $2;
			$main::mod{$na}{3}{hw} = $3;
			$main::mod{$na}{3}{fw} = '';
			$main::mod{$na}{3}{sw} = '';
			$main::mod{$na}{3}{mc} = '91';
			&misc::Prt("LWP :CP-CAM-$1 SN=$2 $3\n");
		}
		 if( exists $main::mod{$na} and !$main::opt{'D'} ){
			&db::WriteMod($na);
		}
		&misc::Prt("LWP :Contact=$main::dev{$na}{co} SN=$main::dev{$na}{sn}\n");
	} else{

		&misc::Prt("LWP :Error " . $response->status_line ."\n");
		return 1;
	}
}


=head2 FUNCTION CiscoAta()

Fetch info through webinterface of Cisco ATA boxes (tx Kyle Kniffin)

B<Options> device name

B<Globals> main::dev

B<Returns> 0 on success, 1 on failure

=cut
sub CiscoAta{

	my ($na) = @_;

	my $ua = LWP::UserAgent->new;
	$ua->timeout($misc::timeout);

	my $response = $ua->get("http://$main::dev{$na}{ip}/DeviceInfo");
	if ($response->is_success) {
		my $devhtml = $response->content;
		if ( $devhtml =~ m/<td>([0-9]{2,8})<\/td>/i ) {						# Find Extension on Cisco ATA 186
			$main::dev{$na}{co} = $1;
		}
		if ( $devhtml =~ m/<td>(FCH\w+|INM\w+)<\/td>/i ) {					# Find Serial  on Cisco ATA 186
			$main::dev{$na}{sn} = $1;
		}
		&misc::Prt("LWP :Contact=$main::dev{$na}{co} SN=$main::dev{$na}{sn}");
	} else {
		&misc::Prt("LWP :Error " . $response->status_line ."\n");
		return 1;
	}
 }

=head2 FUNCTION AastraPhone()

Fetch info through webinterface of Aastra phones using default
credentials (experimental)

B<Options> device name

B<Globals> main::dev

B<Returns> 0 on success, 1 on failure

=cut
sub AastraPhone{

	my ($na) = @_;

	my $ua = LWP::UserAgent->new;
	$ua->timeout($misc::timeout);
	$req = HTTP::Request->new(GET => "http://$main::dev{$na}{ip}/");
	$req->authorization_basic('admin', '22222');
	my $res = $ua->request($req);

	if ($res->is_success) {
		if ( $res->decoded_content =~ m/<td>Firmware Version<\/td><td>([0-9\.]+)<\/td>/i ) {	# Find FW
			$main::dev{$na}{bi} = $1;
		}
		if( $res->decoded_content =~ m/<td>Platform<\/td><td>([\w\s]+)<\/td>/i ){		# Find description
			$main::dev{$na}{de} = $1;
		}
		if( $res->decoded_content =~ m/<tr><td>1<\/td><td>([0-9]+)@/i ){			# Find 1st extension
			$main::dev{$na}{co} = $1;
		}
		&misc::Prt("LWP :Contact=$main::dev{$na}{co} FW=$main::dev{$na}{bi} $main::dev{$na}{de}\n");
	} else {
		&misc::Prt("LWP :Error " . $res->status_line ."\n");
		return 1;
	}
}

=head2 FUNCTION GetHTTP()

Send HTTP Get request and return answer

B<Options> ip,proto,uri

B<Globals>

B<Returns> result

=cut
sub GetHTTP{

	my ($dst, $proto, $uri) = @_;

	$uri = ($uri eq '/')?'':$uri;
	my $ua = LWP::UserAgent->new;
	$ua->timeout($misc::timeout);
	my $response = $ua->get("$proto://$dst/$uri");
	if ($response->is_success) {
		my $res = $response->content;
		&misc::Prt("LWP :".substr($res,0,70) );
		return $res;
	} else {
		&misc::Prt("LWP :Error " . $response->status_line ."\n");
		return $response->status_line;
	}
}
	
1;
