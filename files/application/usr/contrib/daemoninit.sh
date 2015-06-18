#!/bin/bash
# Part of Tristan Rhodes' install script to just create the init.d scripts

clear
##########################################################################
cat >> /var/nedi/nedi-syslog << "EOF"
#! /bin/sh
#
# nedi-syslog   Init script to start/stop syslog daemon for NeDi
#               http://nedi.sourceforge.net
#
# Author:       Tristan Rhodes <tristanrhodes@weber.edu>.
#
# Version:      @(#)nedi-syslog  1.00-01  03-Apr-2006  tristanrhodes@weber.edu
#

SCRIPTNAME="/etc/init.d/nedi-syslog"

DESC="Syslog server for NeDi"
NEDI_DIR="/var/nedi/"
COMMAND="syslog.pl"
PARAMETERS="-D"

#
#       Function that starts the daemon/service.
#
d_start() {
      cd $NEDI_DIR
      $(./$COMMAND $PARAMETERS >> /dev/null)
}

#
#       Function that stops the daemon/service.
#
d_stop() {
      pkill $COMMAND
}

case "$1" in
start)
     echo -n "Starting $DESC: $COMMAND"
     d_start
     echo "."
;;
stop)
     echo -n "Stopping $DESC: $COMMAND"
     d_stop
     echo "."
;;
restart|force-reload)
     #
     #       If the "reload" option is implemented, move the "force-reload"
     #       option to the "reload" entry above. If not, "force-reload" is
     #       just the same as "restart".
     #
     echo -n "Restarting $DESC: $NAME"
     d_stop
     sleep 1
     d_start
     echo "."
;;
*)
     echo "Usage: $SCRIPTNAME {start|stop|restart|force-reload}" >&2
     exit 1
;;
esac

exit 0
EOF
##########################################################################
# Move file
sudo mv /var/nedi/nedi-syslog /etc/init.d/nedi-syslog
# Change permissions
sudo chmod 755 /etc/init.d/nedi-syslog
# Create symlinks to autostart
sudo update-rc.d nedi-syslog defaults
# Start process
sudo /etc/init.d/nedi-syslog start


# Create nedi-monitor init script to start moni.pl
##########################################################################
cat >> /var/nedi/nedi-monitor << "EOF"
#! /bin/sh
#
# nedi-monitor   Init script to start/stop the monitoring daemon for NeDi
#               http://nedi.sourceforge.net
#
# Author:       Tristan Rhodes <tristanrhodes@weber.edu>.
#
# Version:      @(#)nedi-monitor  1.00-01  06-Apr-2006  tristanrhodes@weber.edu
#

SCRIPTNAME="/etc/init.d/nedi-monitor"

DESC="Monitoring service for NeDi"
NEDI_DIR="/var/nedi/"
COMMAND="moni.pl"
PARAMETERS="-D"

#
#       Function that starts the daemon/service.
#
d_start() {
      cd $NEDI_DIR
      $(./$COMMAND $PARAMETERS >> /dev/null)
}

#
#       Function that stops the daemon/service.
#
d_stop() {
      pkill $COMMAND
}

case "$1" in
start)
     echo -n "Starting $DESC: $COMMAND"
     d_start
     echo "."
;;
stop)
     echo -n "Stopping $DESC: $COMMAND"
     d_stop
     echo "."
;;
restart|force-reload)
     #
     #       If the "reload" option is implemented, move the "force-reload"
     #       option to the "reload" entry above. If not, "force-reload" is
     #       just the same as "restart".
     #
     echo -n "Restarting $DESC: $NAME"
     d_stop
     sleep 1
     d_start
     echo "."
;;
*)
     echo "Usage: $SCRIPTNAME {start|stop|restart|force-reload}" >&2
     exit 1
;;
esac

exit 0
EOF
##########################################################################
# Move file
sudo mv /var/nedi/nedi-monitor /etc/init.d/nedi-monitor
# Change permissions
sudo chmod 755 /etc/init.d/nedi-monitor
# Create symlinks to autostart
sudo update-rc.d nedi-monitor defaults
# Start process
sudo /etc/init.d/nedi-monitor start
