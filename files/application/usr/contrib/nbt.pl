#!/usr/bin/perl
use Socket;

my $port = 137;
my $host = $ARGV[0];
my $tout = 0.5;
my $nbts = pack(C50,129,98,00,00,00,01,00,00,00,00,00,00,32,67,75,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,00,00,33,00,01);
my $wsnam = $data = $rin = "";
my $sockaddr = 'S n a4 x8';	# magic
my $family   = 2;			# AF_INET (system dependent !)
my $socktype = 1;			# SOCK_DGRA (system dependent !)

($name, $aliases, $proto) = getprotobyname('udp');
($name, $aliases, $type, $len, $peer_addr) = gethostbyname($host);

$me   = pack($sockaddr, $family, 0, $my_addr);
$peer = pack($sockaddr, $family, $port, $peer_addr);

print "Netbios Machine table\n";  
print "=========================\n";
socket(S, PF_INET, SOCK_DGRAM, $proto) || die "Unable to create socket: $!";
bind(S, $me) || die "Unable to bind socket: $!";
send(S, $nbts, 0, $peer) || warn "Couldn't send: $!";

# receive udp until timeout
vec($rin, fileno(S), 1) = 1;
while (select($rin, undef, undef,$tout)) {
	recv(S, $data, 1024, 0)        || die "recv: $!";
}
close(S);

if ($data =~ /AAAAAAAAAA/){  
	$num = unpack("C",substr($data,56,1));	# Get number of names
	$out = substr($data,57);			# get rid of WINS header

	for ($i = 0; $i < $num;$i++){
		$nam = substr($out,18*$i,15);
		$nam =~ s/ +//g;
		my $id = unpack("C",substr($out,18*$i+15,1));
		my $fl = unpack("C",substr($out,18*$i+16,1));
		if ($fl < 128){
			$fl = "UNIQUE";
		}else{
			$fl = "GROUP";
		}

		if ($id eq "3"){
			if ($fl eq "UNIQUE"){
				if ($wsnam eq ""){
					$wsnam = $nam;
				}else{$nam = "*$nam"}
			}
		}
		printf "%s	(%02X)	%s\n",$nam,$id,$fl;	
	}
	@mac = unpack("C6",substr($out,18*$i,6));
	print "=========================\n";
	printf "MAC Address %02X-%02X-%02X-%02X-%02X-%02X\n",@mac;
}else{
	print "No Netbios\n";
}

