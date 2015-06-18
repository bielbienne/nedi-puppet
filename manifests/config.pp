class nedi::config{
  cron { 'backup':
    command => "/usr/local/nedi/nedi.pl -vproB5 > /tmp/nedi-00.bup 2>&1",
    user    => nedi,
    hour    => 0,
    minute  => 0
  }
  cron {'discovery':
    command => "/usr/local/nedi/nedi.pl -vp > /tmp/nedi-`date +\%H`.run 2>&1",
    user    => 'nedi',
    hour    => ['1-23'],
    minute  => '0',
  }
  cron {'db-cleanup':
    command  => "/usr/local/nedi/contrib/nedio_db_maintenance.sh /var/nedi/nedi.conf /tmp/nedi-dbcleanup",
    user     => 'nedi',
    hour     => '1',
    minute   => '0',
    monthday => '1',
  }
}
