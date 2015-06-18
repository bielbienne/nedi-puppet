#!/usr/bin/perl
#============================================================================
# Program: nedi.pl
# Programmer: Remo Rickli
#
# UDP (discard port) sweep on a network to refresh ARP and CAM tables.
# You may have to increase the neighbour table on your unix system with:
# echo 2048 > /proc/sys/net/ipv4/neigh/default/gc_thresh3

if (!$ARGV[0]){
	die "Specify Network (e.g. 192.168 or 192.168.1) to sweep!\n";
}else{
	my @ip   = split(/\./,$ARGV[0]);
	if($ip[2]){
		print "$ip[0].$ip[1].$ip[2] ";
		for($i=1;$i<255;$i++){
			&Send("$ip[0].$ip[1].$ip[2].$i");
			print ":";
		}
		print "\n";
	}else{
		for($s=1;$s<255;$s++){
			print "$ip[0].$ip[1].$s ";
			for($i=1;$i<255;$i++){
				&Send("$ip[0].$ip[1].$s.$i");
				print ".";
			}
			print "\n";
		}
	}
}

sub Send(){
	use IO::Socket::INET;

	my $socket = new IO::Socket::INET->new(PeerPort=>'9', Proto=>'udp', PeerAddr=>"$_[0]")|| die "Unable to create socket: $!";
	$socket->autoflush();
	$socket->send(' ');
	$socket->close();

}

# Alternative Socket Programming
sub SockSend(){
	use Socket;
	
	# create a socket
	socket(Server, PF_INET, SOCK_DGRAM, getprotobyname('udp'))|| die "Unable to create socket: $!";
	
	# build the address of the remote machine
	$internet_addr = inet_aton($_[0])
	    or die "Couldn't convert $remote_host into an Internet address: $!\n";
	$paddr = sockaddr_in('9', $internet_addr);
	
	# connect
	connect(Server, $paddr)
	    or die "Couldn't connect to $remote_host:$remote_port: $!\n";
	
#	select((select(Server), $| = 1)[0]);  # enable command buffering
	select(Server); $| = 1; select(STDOUT);
	print Server "\n";
#	$answer = <Server>;
	close(Server);
}
