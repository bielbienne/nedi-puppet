class nedi(
  $auth_method = 'local',
  $snmp_communities = ['paranod md5 ver3pa55  aes ver3pa55'],
  $credentials = ['usr  nedi  pa55  enpa55'],
  $hostname = 'nedi.example.com',
  $certlocation = '/etc/pki/tls/certs/localhost.crt',
  $privkeylocation = '/etc/pki/tls/private/localhost.key',
){
  include nedi::packages
  include nedi::install
  include nedi::config
  Class['nedi::packages'] -> Class['nedi::install'] -> Class['nedi::config']
}
