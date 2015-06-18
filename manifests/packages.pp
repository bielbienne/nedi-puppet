class nedi::packages {
  Package { ensure => 'installed' }
  package { 'php': }
  package { 'php-mysql': }
  package { 'mysql-server': }
  package { 'php-snmp': }
  package { 'php-php-gettext': }
  package { 'php-mbstring': }
  package { 'php-gd': }
  package { 'php-mcrypt': }
  package { 'perl-Net-Telnet-Cisco': }
  package { 'perl-IO-Tty': }
  package { 'perl-Algorithm-Diff': }
  package { 'perl-Net-Telnet': }
  package { 'perl-Net-SNMP': }
  package { 'net-snmp': }
  package { 'net-snmp-perl': }
  package { 'rrdtool': }
  package { 'perl-Net-SSH': }
  package { 'perl-Time-HiRes': }
  package { 'mod_proxy_html': }
  package { 'rrdtool-perl': }
}