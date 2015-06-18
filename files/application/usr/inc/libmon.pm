=pod

=head1 LIBRARY
libmon.pm

Functions for monitoring

=head2 AUTHORS

Remo Rickli & NeDi Community

=cut

package mon;
use warnings;

use Time::HiRes;

=head2 FUNCTION InitMon()

Read monitoring targets and users

B<Options> -

B<Globals> -

B<Returns> -

=cut
sub InitMon{

	%main::srcna= ();
	%main::mon  = ();
	%main::usr  = ();

	my $nt = 0;
	$nt  = &db::ReadMon('dev');
	$nt += &db::ReadMon('node');

	&db::ReadUser("groups & 8 = 8 AND (phone != '' OR email != '')");				# Read users for Mail alerts (Pg requires = 8)

	return $nt;
}


=head2 FUNCTION GetUptime()

Gets uptime via SNMP

B<Options> IP address, SNMP version and community

B<Globals> -

B<Returns> array with (latency, uptime) or (0,0) upon timeout

=cut
sub GetUptime{

	my ($ip, $ver, $comm) = @_;

	my $r;

	my $uptimeO = '1.3.6.1.2.1.1.3.0';

	my ($session, $err) = &snmp::Connect($ip,$ver,$comm);
	my $start = Time::HiRes::time;
	if(defined $session){
		$r   = $session->get_request($uptimeO);
		$err = $session->error;
		$session->close;
	}

	if($err){
		&misc::Prt("ERR :$err\n");
		return -1,0;
	}else{
		my $lat = int(1000 * (Time::HiRes::time - $start) );
		&misc::Prt("SNMP:Latency=${lat}ms Uptime=$r->{$uptimeO}s\n");
		return $lat, $r->{$uptimeO};
	}
}


=head2 FUNCTION PingService()

Pings a tcp service.

B<Options> IP address, protocoll and name of service

B<Globals> -

B<Returns> latency or nothing upon timeout

=cut
sub PingService{

	my ($ip, $proto, $srv, $tout) = @_;

	$tout = ($tout)?$tout:$misc::timeout;
	my $p = Net::Ping->new($proto);
	$p->hires();
	&misc::Prt("TEST:");
	if ($proto and $proto ne 'icmp'){
		$srv = "microsoft-ds" if $srv eq "cifs";
		$p->tcp_service_check(1);
		$p->{port_num} = getservbyname($srv, $proto);
		&misc::Prt("$ip proto=$proto srv=$srv ");
	}else{
		&misc::Prt("$ip tcp echo ");
	}
	(my $ret, my $latency, my $rip) = $p->ping($ip, $tout);
	$p->close();

	if($ret){
		my $lat = int($latency * 1000);
		&misc::Prt("latency=${lat}ms\n");
		return $lat;
	}else{
		&misc::Prt("fail!\n");
		return -1;
	}
}

=head2 FUNCTION AlertFlush()

Sends Mails and SMS. If there are no queued mails, the SMTP connection won't be established. Look at commented lines to adjust SMS part...

B<Options> subject for mails, #mails queued

B<Globals> -

B<Returns> -
=cut

sub AlertFlush{

	my ($sub,$mq) = @_;

	use Net::SMTP;

	my $err = 0;
	my $nm  = 0;
	my $ns  = 0;
	
	if($mq){
		my $smtp = Net::SMTP->new($misc::smtpserver, Timeout => $misc::timeout) || ($err = 1);
		if($err){
			&misc::Prt("ERR :Connecting to SMTP server $misc::smtpserver\n");
		}else{
			foreach my $u ( keys %main::usr ){
				if(@{$main::usr{$u}{mail}}){
					&misc::Prt("MAIL:$u/$main::usr{$u}{ml}\n");
					$smtp->mail($misc::mailfrom) || &ErrSMTP($smtp,"From");
					$smtp->to($main::usr{$u}{ml}) || &ErrSMTP($smtp,"To");
					$smtp->data();
					$smtp->datasend("To: $main::usr{$u}{ml}\n");
					$smtp->datasend("From: $misc::mailfrom\n");
					$smtp->datasend("Subject: ".((@{$main::usr{$u}{mail}} > 1)?@{$main::usr{$u}{mail}}." ${sub}s":$sub)."\n");
					$smtp->datasend("Date: ".localtime($main::now)."\n");
					#$smtp->datasend("MIME-Version: 1.0\n"); 			# Some need it, Exchange doesn't?
					$smtp->datasend("\n");
					$smtp->datasend("Hello $u\n");
					$smtp->datasend("\n");
					my $ln = 0;
					foreach my $l (@{$main::usr{$u}{mail}}){
						$ln++;
						$smtp->datasend("$ln) $l\n");
					}
					$smtp->datasend("\n");

					if($misc::mailfoot){
						foreach my $l (split /\\n/,$misc::mailfoot){
							$smtp->datasend("$l\n");
						}
					}
					$smtp->dataend() || &ErrSMTP($smtp,"End");

					@{$main::usr{$u}{mail}} = ();
					$nm++;
				}
			}
			$smtp->quit;
		}
	}

	foreach my $u ( keys %main::usr ){

		if($main::usr{$u}{sms}){
			if (!-e "/var/spool/sms/checked/$u"){						# Skip if previous SMS hasn't been sent, to avoid smsd crash! TODO use timestamp instead?
				&misc::Prt("SMS :$u/$main::usr{$u}{ph}\n");

				#1. Spooling to smsd:
				if( exists $misc::sms{'spool'} ){
					$ns++ if open(SMS, ">$misc::sms{'spool'}/$u");			# User is filename to avoid flooding
					print SMS "To:$main::usr{$u}{ph}\n\n$main::usr{$u}{sms}\n";
					close(SMS);
				}

				#2. Calling gammu server:
				if( exists $misc::sms{'gammu'} ){
					$ns++ if !system "gammu-smsd-inject TEXT $main::usr{$u}{ph} -text \"$main::usr{$u}{sms}\" >/dev/null";
				}

				#3.SMTP based SMS gateway:
				if( exists $misc::sms{'smtp'} ){
					$smtp->mail($misc::mailfrom) || &ErrSMTP($smtp,"From");
					$smtp->to($misc::sms{'smtp'}) || &ErrSMTP($smtp,"To");
					$smtp->data();
					$smtp->datasend("To:Mobile#\n");
					$smtp->datasend("From: $misc::mailfrom\n");
					$smtp->datasend("Subject: $sub\n");
					#$smtp->datasend("MIME-Version: 1.0\n"); 				# Some need it, Exchange doesn't?
					$smtp->datasend("\n");
					$smtp->datasend("$main::usr{$u}{sms}\n");
					$smtp->dataend() || &ErrSMTP($smtp,"End");
				}

				$main::usr{$u}{sms} = '';
			}else{
				&misc::Prt("ERR :SMS skipped since previous message for $u is still being sent!\n");
			}
		}
	}

	&misc::Prt("ALRT:$nm mails and $ns SMS sent with $mq events\n");
	
	return $nm;
}

=head2 FUNCTION ErrSMTP()

Handle SMTP errors

B<Options> SMTP code, Step of delivery

B<Globals> -

B<Returns> -
=cut

sub ErrSMTP{

	my ($smtp,$step) = @_;

	my $m = &misc::Strip(($smtp->message)[-1]);							# Avoid uninit with Strip()
	my $c = $smtp->code;
	chomp $m;
	&misc::Prt("ERR :$c, $m\n");
}


=head2 FUNCTION Elevate()

Returns elevation according to the notify string in nedi.conf, but if
min-elevation is higher this is returned instead.

Bits

1 = Create event

2 = Send mail

4 = Send sms

B<Options> mode,min-elevation

B<Globals> -

B<Returns> elevation
=cut

sub Elevate{

	my ($mode,$min,$tgt) = @_;

	my $nfy = ($tgt and exists $main::mon{$tgt} and $main::mon{$tgt}{no} )?$main::mon{$tgt}{no}:$misc::notify;

	my $elevate = 0;
	if($mode =~ /^[0-9]+$/){
			$elevate = $mode;
	}elsif($mode =~ /^[A-Z]$/){									# Only uppercase mode can elevate above 1
		if($nfy =~ /$mode/){
			$elevate = 3;
		}elsif($nfy =~ /$mode/i){
			$elevate = 1;
		} 
	}elsif($mode =~ /^[a-z]$/){									# Lowercase mode can still elevate to 1
		if($nfy =~ /$mode/i){
			$elevate = 1;
		}
	}

	return ($elevate > $min)?$elevate:$min;
}

=head2 FUNCTION Event()

Print a message, insert event and queue alert if desired. If the mode argument is a letter, 
the event is elevated according to the notify string in nedi.conf. If mode is a number the
event is elevated accordingly. The monitoring settings for the target determine
final elevation and processing.

B<Options> mode,level,class,notify,target,device,message,sms

B<Globals> -

B<Returns> # of queued mails
=cut

sub Event{

	my ($mode,$level,$class,$tgt,$dev,$msg,$sms) = @_;
	
	my $elevate = &Elevate($mode,0,$tgt);

	&misc::Prt("EVNT:CL=$class EL=$elevate TGT=$tgt MSG=$msg\n");

	if($mode =~ /^\d+$/ and $class ne "moni" and exists $main::mon{$tgt}){			# Using alert settings for moni events and never elevate unmonitored sources
		if($main::mon{$tgt}{ed} and $msg =~ /$main::mon{$tgt}{ed}/){
			$elevate = 0;
			&misc::Prt("EINF:$msg contains /$main::mon{$tgt}{ed}/, discarding\n");
		}
		if( $main::mon{$tgt}{el} ){
			if( $level >= $main::mon{$tgt}{el} ){
				$elevate = 3;
				&misc::Prt("ELVL:Forward level limit $main::mon{$tgt}{el} <= $level, forwarding\n");
			}elsif($main::mon{$tgt}{el}%2 and $level > ($main::mon{$tgt}{el}-2) ){			# Deduct 2 and use > instead >=
				$elevate = 0;
				&misc::Prt("ELVL:Discard level limit ".($main::mon{$tgt}{el}-1)." >= event level $level, discarding\n");
			}
		}
		if($main::mon{$tgt}{ef} and $msg =~ /$main::mon{$tgt}{ef}/){
			$elevate = 3;
			&misc::Prt("EINF:$msg contains /$main::mon{$tgt}{ef}/, forwarding\n");
		}
	}

	if($elevate){
		my $info = ((length $msg > 250)?substr($msg,0,250)."...":$msg);
		$info =~ s/[\r\n]/, /g;
		&db::Insert('events','level,time,source,info,class,device',"$level,$main::now,'$tgt',".$db::dbh->quote($info).",'$class','$dev'");
	}

	if($elevate > 1){
		my $nm = 0;
		my $ns = 0;
		foreach my $u ( keys %main::usr ){

			my $viewdev = ($main::usr{$u}{vd})?&db::Select('devices','','device',"device='$dev' AND $main::usr{$u}{vd}"):$dev;
			if(defined $viewdev and $viewdev eq $dev){					# Send mail only to those who can see the associated device

				if($main::usr{$u}{ml} and $msg and $elevate & 2){			# Usr has email, there's a msg and elevation bit 2 is set -> queue mail
					push (@{$main::usr{$u}{mail}}, "$tgt\t$msg");
					&misc::Prt("MLQ :$u $tgt $msg\n");
					$nm++;
				}

				if($main::usr{$u}{ph} and $sms and $elevate & 4){			# Usr has phone, there's a short message and elevation bit 4 is set -> queue sms
					$main::usr{$u}{sms} .= "$tgt:$sms ";
					&misc::Prt("SMSQ:$u $tgt:$sms\n");
					$ns++;
				}
			}
		}
		&misc::Prt("EFWD:$nm Mail and $ns SMS queued\n");
		return $nm;
	}

	return 0;
}

1;
