class nedi::install {
  # Tar to tar.gz
  # Nedi.conf template
  file {
    'userhome':
      ensure  => 'directory',
      path    => '/var/nedi',
      mode    => '0755',
      owner   => 'nedi',
      group   => 'apache',
      require => User['nedi'];
    'application-folder':
      ensure  => 'directory',
      path    => '/usr/local/nedi',
      source  => 'puppet:///modules/nedi/application/usr/',
      recurse => 'inf',
      mode    => '0775',
      owner   => 'nedi',
      group   => 'apache',
      require => File['userhome'];
    'var.tar':
      ensure  => 'file',
      path    => '/tmp/var.tar',
      source  => 'puppet:///modules/nedi/application/var.tar',
      mode    => '0700',
      owner   => 'root',
      group   => 'root',
      require => File['userhome'];
    '/usr/local/nedi/conf':
      ensure  => 'link',
      target  => '/var/nedi/conf',
      mode    => '0775',
      owner   => 'nedi',
      group   => 'apache',
      require => Exec['var-content'];
    '/usr/local/nedi/sysobj':
      ensure  => 'link',
      target  => '/var/nedi/sysobj',
      mode    => '0775',
      owner   => 'nedi',
      group   => 'apache',
      require => Exec['var-content'];
    '/usr/local/nedi/rrd':
      ensure  => 'link',
      target  => '/var/nedi/rrd',
      mode    => '0775',
      owner   => 'nedi',
      group   => 'apache',
      require => Exec['var-content'];
    '/usr/local/nedi/nedi.conf':
      ensure  => 'link',
      target  => '/usr/local/etc/nedi.conf',
      owner   => 'nedi',
      group   => 'apache',
      require => File['nedi.conf'];
    'nedi.conf':
      ensure  => 'file',
      path    => '/usr/local/etc/nedi.conf',
      content => template('nedi/nedi.conf.erb'),
      owner   => 'nedi',
      group   => 'apache',
      mode    => '0700';
    'nedi-httpd.conf':
      ensure  => 'file',
      path    => '/etc/httpd/conf.d/nedi.conf',
      content => template('nedi/httpd.conf.erb'),
      owner   => 'root',
      group   => 'root',
      mode    => '0644';
  }
  user {'nedi':
    ensure     => 'present',
    home       => '/var/nedi',
    managehome => true,
    system     => true,
    shell      => '/bin/bash',
    groups     => ['apache'],
  }
  exec {'var-content':
    command    => 'tar -xf /tmp/var.tar -C /var/nedi/',
    unless     => 'cat /var/nedi/.lockfile 2> /dev/null',
    require    => File['var.tar'],
  }
}