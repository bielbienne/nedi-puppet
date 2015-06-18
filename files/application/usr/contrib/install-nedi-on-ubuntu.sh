#!/bin/bash
# NeDi (Network Discovery Suite) Install Script
# install-nedi-on-ubuntu.sh (v 2.0) 02/09/2007
# Tristan Rhodes

clear
echo ""
echo "     This script will install Nedi on Ubuntu 6.06 or 6.10"
echo ""
echo "     ---------- WARNING ----------"
echo "     You should only run this script ONCE."
echo "     You should only run this script on a clean install of Ubuntu"
echo "     Your original configuration files will be saved with a .orig extension"
echo "     UNSUPPORTED -  Use at your own risk.   "
echo "     ---------- WARNING ----------"
echo ""
echo "     Created by Tristan Rhodes"
echo "     http://useopensource.blogspot.com"
echo ""
echo -n "     Hit Return to continue or Ctrl-C to exit : "
read foo

# Find location of the "nedi" folder
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " Finding the location of nedi source files..."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
# If this script was ran from the extracted archive, the "nedi" folder should be here:
cd ../../
nedi_location=`pwd`
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "What directory did you extract the \"nedi\" folder into? [$nedi_location]: "
	read nedi_location_input
	if [ -z "$nedi_location_input" ]; then
	   nedi_location_input=$nedi_location
	fi
	echo -n "You entered \"$nedi_location_input\".  Is this correct? (y/[n]): "
	read goodinput
	if [ "$goodinput" == "y" ]; then
		if [ ! -d "$nedi_location_input/nedi" ]; then
		   echo "Error: That location does not contain the \"nedi\" folder. Please try again."
		   goodinput="n"
		fi
	fi
done
# Move into the folder
cd $nedi_location_input

# Modify source.list and install needed packages
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " Installing needed software packages......."
echo " You will need to provide your Ubuntu password"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
# Save the original sources.list
sudo cp /etc/apt/sources.list /etc/apt/sources.list.orig
# Uncomment all repository entries
sudo sed -i -e 's/# deb/deb/g' /etc/apt/sources.list
# Update software catalog from new repositories
sudo apt-get update
# Install pre-requisites for NeDi
sudo apt-get install apache2 libapache2-mod-php5 mysql-server libnet-snmp-perl \
php5-mysql libnet-telnet-cisco-perl php5-snmp php5-gd libalgorithm-diff-perl rrdtool


# Setting up Apache2 with SSL
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " Setting up Apache2 with SSL.........."
echo " You will need to input some information for the SSL certificate."
echo " If you don't care what the SSL cert says, just hit enter to choose"
echo " the default settings."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
sleep 5
# Create a new SSL certificate
sudo apache2-ssl-certificate -days 365
# Enable the SSL module in apache2
sudo a2enmod ssl
# Create a new SSL conf file
sudo cp /etc/apache2/sites-available/default /etc/apache2/sites-available/ssl
sudo ln -s /etc/apache2/sites-available/ssl /etc/apache2/sites-enabled/ssl
# Add port numbers
sudo sed -i -e 's/VirtualHost \*/VirtualHost \*:80/g' /etc/apache2/sites-available/default
sudo sed -i -e 's/VirtualHost \*/VirtualHost \*:443/g' /etc/apache2/sites-available/ssl
sudo sed -i -e '/Listen 80/aListen 443' /etc/apache2/ports.conf
# Add SSL statements
sudo sed -i -e '/\<VirtualHost \*:443\>/aSSLEngine On\nSSLCertificateFile \/etc\/apache2\/ssl\/apache.pem' /etc/apache2/sites-available/ssl
# Setup APACHE to use rewrite module
sudo a2enmod rewrite
# Add rewrite statements
sudo sed -i -e '/\<VirtualHost \*:80\>/aRewriteEngine On\nRewriteCond \%\{HTTPS\} off\nRewriteRule \(\.\*\) https:\/\/\%{HTTP_HOST}\%{REQUEST_URI}' /etc/apache2/sites-available/default


# Install NeDi and related stuff
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " About to setup NeDi.........."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
echo ""
# Move nedi to /opt/nedi/
sudo mv nedi /opt/
# Fix permissions
sudo chgrp www-data /opt/nedi/html/log/
sudo chmod 775 /opt/nedi/html/log/
# Create link to Nedi website
sudo ln -s /opt/nedi/html/ /var/www/
# Create link to Nedi config file
sudo ln -s /opt/nedi/nedi.conf /etc/nedi.conf
# Restart apache
sudo /etc/init.d/apache2 restart


# Get input from user
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Please answer the following questions."
echo "If you would like to change your answers, edit /opt/nedi/nedi.conf."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
echo ""

# Get SNMP read-only string
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "Enter your SNMP read-only community string: "
	read snmp_read
	echo -n "You entered \"$snmp_read\".  Is this correct? (y/[n]): "
	read goodinput
done
echo ""

# Save orginal nedi configuration file
cp /opt/nedi/nedi.conf /opt/nedi/nedi.conf.orig
# Replace snmp string in nedi.conf
sed -i -e "s/comm\tpublic/comm\t$snmp_read/g" /opt/nedi/nedi.conf

# Delete the default telnet examples
sed -i -e '/usr\tnedi\tpa55\tena6le/d' /opt/nedi/nedi.conf
sed -i -e '/usr\tN-lab\t2pa55\t2ena6le/d' /opt/nedi/nedi.conf
sed -i -e '/usr\tnedi\tpa55\tenpa55/d' /opt/nedi/nedi.conf
sed -i -e '/usr\tN-lab\tw0rd\tenw0rd/d' /opt/nedi/nedi.conf

# Get telnet username
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "Enter your telnet (vty) username. If you don't use usernames, just hit Enter: "
	read telnet_user
	# If user hits enter, put in default username
	if [ -z "$telnet_user" ]; then
		echo "Using default settings for telnet username..."
		echo ""
		telnet_user="N-default"
	fi
	echo -n "You entered \"$telnet_user\".  Is this correct? (y/[n]): "
	read goodinput
	echo ""
done

# Get telnet password
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "Enter your telnet password (also known as the vty password): "
	read telnet_pass
	echo -n "You entered \"$telnet_pass\".  Is this correct? (y/[n]): "
	read goodinput
	echo ""
done

# Get enable password
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "Enter the enable password: "
	read telnet_enable
	echo -n "You entered \"$telnet_enable\".  Is this correct? (y/[n]): "
	read goodinput
	echo ""
done

# Add a telnet statement based on user input
sudo sed -i -e "/user\tpass\tenablepass/ausr\t$telnet_user\t$telnet_pass\t$telnet_enable" /opt/nedi/nedi.conf
# Enable the RRD functionality
sed -i -e 's/;rrdpath/rrdpath/' /opt/nedi/nedi.conf
sed -i -e 's/;rrdstep/rrdstep/' /opt/nedi/nedi.conf
# Setup Nedi log files
sudo mkdir /var/log/nedi
myusername=$(whoami)
sudo chown $myusername:$myusername /var/log/nedi
touch /var/log/nedi/nedi.log
touch /var/log/nedi/lastrun.log


# Configure database related stuff
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " About to setup the NeDi Database.........."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
echo ""

# Get the database password
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "Enter the MySQL database root password you wish to create: "
	read mysql_passwd
	echo -n "You entered \"$mysql_passwd\".  Is this correct? (y/[n]): "
	read goodinput
	echo ""
done

# Create a root password for MySQL
sudo mysqladmin -u root password "$mysql_passwd"
# Initialize the NeDi database
echo "You will need to input this same database password again (username is \"root\")..."
cd /opt/nedi/
./nedi.pl -i
echo ""
echo ""

# make startnedi.sh
echo '#!/bin/sh
# start nedi from crontab. Creates logfiles
CMD="./nedi.pl $1"
LOGPATH="/var/log/nedi"
LOGFILE="$LOGPATH/nedi.log"
LASTRUN="$LOGPATH/lastrun.log"
cd /opt/nedi
now=$(date +%Y%m%d:%H%M)
echo "#$now start # $CMD" > $LASTRUN
echo "#$now start" >> $LOGFILE
$($CMD >> $LASTRUN)
tail -8 $LASTRUN >> $LOGFILE
now=$(date +%Y%m%d:%H%M)
echo "#$now stop" >> $LOGFILE
echo "#$now stop" >> $LASTRUN' > /opt/nedi/startnedi.sh
# Make startnedi.sh executable
chmod +x /opt/nedi/startnedi.sh


# crontab
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " Creating a crontab entry for NeDi to run a discovery every four hours......"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
echo ""
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo "How often do you want NeDi to run network discoveries?"
	echo ""
	echo "(1) Every 1 hours"
	echo "(2) Every 2 hours"
	echo "(4) Every 4 hours"
	echo ""
	echo -n "Select 1, 2, or 4: "
	read cron_freq
	if [ "$cron_freq" = "1" ]; then
		goodinput=y
	elif [ "$cron_freq" = "2" ]; then
		goodinput=y
	elif [ "$cron_freq" = "4" ]; then
		goodinput=y
	fi
	echo ""
done

# Create nedi.cron file
if [ "$cron_freq" = "1" ]; then
	echo "15 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * * /opt/nedi/startnedi.sh -co  # Discovery" > /opt/nedi/nedi.cron
	sed -i -e 's/rrdstep\t\t14400/rrdstep\t\t3600/' /opt/nedi/nedi.conf
elif [ "$cron_freq" = "2" ]; then
	echo "15 2,4,6,8,10,12,14,16,18,20,22 * * * /opt/nedi/startnedi.sh -co  # Discovery" > /opt/nedi/nedi.cron
	sed -i -e 's/rrdstep\t\t14400/rrdstep\t\t7200/' /opt/nedi/nedi.conf
elif [ "$cron_freq" = "4" ]; then
	echo "15 4,8,12,16,20 * * * /opt/nedi/startnedi.sh -co  # Discovery" > /opt/nedi/nedi.cron
fi
echo "15 0 * * * /opt/nedi/startnedi.sh -cob # Discovery and gather device configs" >> /opt/nedi/nedi.cron

# Apply nedi.cron file to user crontab
crontab /opt/nedi/nedi.cron
echo "Finished setting up nedi crontab........"
echo ""
echo ""


# Startup scripts
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " Setting up startup scripts......"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
# Create nedi-syslog init script to start syslog.pl
##########################################################################
cat >> /opt/nedi/nedi-syslog << "EOF"
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
NEDI_DIR="/opt/nedi/"
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
sudo mv /opt/nedi/nedi-syslog /etc/init.d/nedi-syslog
# Change permissions
sudo chmod 755 /etc/init.d/nedi-syslog
# Create symlinks to autostart
sudo update-rc.d nedi-syslog defaults
# Start process
sudo /etc/init.d/nedi-syslog start


# Create nedi-monitor init script to start moni.pl
##########################################################################
cat >> /opt/nedi/nedi-monitor << "EOF"
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
NEDI_DIR="/opt/nedi/"
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
sudo mv /opt/nedi/nedi-monitor /etc/init.d/nedi-monitor
# Change permissions
sudo chmod 755 /etc/init.d/nedi-monitor
# Create symlinks to autostart
sudo update-rc.d nedi-monitor defaults
# Start process
sudo /etc/init.d/nedi-monitor start


# Setting up weekly backups
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " Setting up a weekly backup of the database......"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
# Create backup file (nedi-backup.sh)
##########################################################################
cat >> /opt/nedi/nedi-backup.sh << "EOF"
#!/bin/bash
# This script will backup a MySQL database. It uses mysqldump
# and then gzip to compress the files. Modify the variables below
# to suit your needs.

# Database info
DB_HOST="localhost"
DB_USER="root"
DB_PASS="YourPasswordGoesHere"
DB_NAME="nedi"

# Where should I put the backups?
BACKUP_DIR=/root/${DB_NAME}_db_backups

# Filename to give backup. In this case
# e.g."nedi_db_backup-YYYY-MM-DD.sql.gz"
BACKUP_NAME=${DB_NAME}_db_backup-`date +%F`.sql.gz

# If the backup folder doesn't exist, create it.
if [ ! -d $BACKUP_DIR ]; then
      mkdir -p $BACKUP_DIR
fi

# Make a backup of the database and compress it
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME -h $DB_HOST -c | \
gzip -c -9 > $BACKUP_DIR/$BACKUP_NAME

# Change permissions so that only root can read/write the backup file
chmod 600 $BACKUP_DIR/$BACKUP_NAME

# Uncomment the bottom line if you want to email the database backup somewhere
# Note: This command requires that mutt is installed
# echo | mutt -a $BACKUP_DIR/$BACKUP_NAME -s "NeDi backup: $BACKUP_NAME" MyEmail@MyDomain.com
EOF
##########################################################################
# Insert real password
sed -i -e "s/YourPasswordGoesHere/$mysql_passwd/" /opt/nedi/nedi-backup.sh
# Move file
sudo mv /opt/nedi/nedi-backup.sh /etc/cron.weekly/nedi-backup.sh
# Change permissions to hide root password
sudo chmod 700 /etc/cron.weekly/nedi-backup.sh

# Get the email address to send backups to
goodinput=n
until [ "$goodinput" == "y" ]; do
	echo -n "Enter the email address that you want to receive backups: "
	read email_backup
	echo -n "You entered \"$email_backup\".  Is this correct? (y/[n]): "
	read goodinput
	echo ""
done

# Replace the text with the real email address
sudo sed -i -e "s/MyEmail@MyDomain.com/$email_backup/" /etc/cron.weekly/nedi-backup.sh

# Start a discovery process
echo ""
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo " NeDi is about to discover your network."
echo ""  
echo " This portion of the install process may take some time,"
echo " depending on the size of your network.  Please be"
echo " patient and don't cancel out of it."
echo " Estimate [(15 seconds) x (Number of Devices)] for the"
echo " discovery process to complete."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo ""
echo ""
sleep 5
cd /opt/nedi/
./nedi.pl -cob


echo "NeDi Installation completed!!!!"
echo ""
echo ""
ipaddress=$(/sbin/ifconfig eth0 | grep "inet addr:" | cut -c21-36 | cut -d' ' -f1)
echo "Login via https://$ipaddress/html/"
echo "Username: admin"
echo "Password: admin"
echo ""
echo "Be sure to change the default password immediately!"
echo ""
echo "Enjoy!!!"
echo ""
