#!/usr/local/bin/bash
#
# nedio_db_maintenance.sh, 
#
toggling_count=50 # count of mac address entries in table iftrack; occurance higher than that count will be defined as toggling and deleted
logfile_seperator="----------" # script internal variable for header and footer of log entries
#nedi_conf="nedi.conf"
#logfile="/var/log/nedi/nedi.log"
unset $logfile

function do_sql {
	sql=$( echo "$1" | cut -d "#" -f1)
	#hint=$( echo $1 | cut -d "#" -f2)
	write_output "$( echo $1 | cut -d '#' -f2)"
	
	if [ -z $logfile ];
	then
		mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" --ssl -e "$sql"
	else
		mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" --ssl -B -e "$sql" & >> $logfile
	fi
}

function db_size {
	sql="SELECT ROUND(sum(data_length+index_length)/1024/1024,3) AS 'total (MB)' FROM information_schema.tables WHERE table_schema='$dbname';"
	echo `mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" --ssl -e "$sql" -BN`;
}

function write_output {
	if [ -z $logfile ];
	then
	        if  [[ "$1" != *${logfile_seperator}* ]]; then echo "$1"; fi
	else
	        echo `date +"%T"` "$1" >> $logfile
	fi
}

function exit_script {
	write_output "$logfile_seperator FINISHED `readlink -f $0` at `date +'%a, %d.%m.%Y %T'` $logfile_seperator"
	exit $1
}

case $# in
	1)
		nedi_conf=$1
	;;

	2)
		nedi_conf=$1
		logfile=$2
	;;

	*)
		echo -e "--------------------------------------"
		echo -e "nedi database maintenance"
		echo -e "03.04.2013, daniel.obst uni-leipzig.de"
		echo -e "--------------------------------------\n"
		echo -e "* deletes entries older than \$retire-variable in nedi.conf"
		echo -e "* deletes unnecessary tracking information of toggling interfaces (because they messup db)"
		echo -e "* optimizes and repairs tables\n"
		echo -e "Usage: ./nedi_db_maintenance.sh <config> [<logfile>]\n"
		echo -e "<config>\tnedi config file, e. g. nedi.conf"
		echo -e "<logfile>\twrite output to logfile instead of standard out (for use as cronjob)"
		exit 0
	;;
esac

if [ -n "$logfile" ] && ! [ -w "$logfile" ] && [ -f "$logfile" ];
then
	echo "logfile $logfile not accessable"
	exit 3;
fi

write_output "$logfile_seperator STARTING `readlink -f $0` at `date +'%a, %d.%m.%Y %T'` $logfile_seperator"
if [ -r $nedi_conf ];
then
	write_output "reading ${nedi_conf}"	
	# get variables from nedi config
	dbuser="root" #`grep '^dbuser' $nedi_conf | awk -F ' ' '{ print $NF }'`	
	dbhost=`grep '^dbhost' $nedi_conf | awk -F ' ' '{ print $NF }'`	
	dbpass=`grep '^dbpass' $nedi_conf | awk -F ' ' '{ print $NF }'`
	dbname=`grep '^dbname' $nedi_conf | awk -F ' ' '{ print $NF }'`	
	retire_days=`grep '^retire' $nedi_conf | awk -F ' ' '{ print $NF }'` #get retire value from nedi.conf
	
	for (( i=0; i<${#dbpass}; i++ )); do dbpass_obfuscated=`echo "${dbpass_obfuscated}*"`; done
	write_output "$dbuser@$dbhost on $dbname, pass=$dbpass_obfuscated"
	write_output "max age: $retire_days days"


else
	write_output "$nedi_conf not readable";
	exit_script 3;
fi

db_size_before=$(db_size);

# do some calculations
retire_secs=$(( $retire_days * 24 * 60 * 60 ))
now=`date +%s`
retire_before=$(( $now - $retire_secs ))
query=() # inizialize array
echo "retire days: $retire_days"
echo "now: $now"
echo "retire before: $retire_before"

# sql jobs, terminating with a necessary comment
query+=("
DELETE FROM events WHERE time < $retire_before;
#deleting old events")

query+=("
DELETE FROM iftrack WHERE ifupdate < $retire_before;
#deleting old interface tracking information")

query+=("
CREATE TEMPORARY TABLE temp_table (
	SELECT mac FROM iftrack GROUP BY mac HAVING count(*) > $toggling_count
);
DELETE
FROM iftrack
WHERE mac IN (SELECT * FROM temp_table)
;
DROP TABLE temp_table;
#deleting interface tracking information of toggling interfaces")

query+=("
DELETE FROM iptrack WHERE ipupdate < $retire_before;
#deleting old ip tracking information")

query+=("
DELETE FROM devices where lastdis < $retire_before;
#deleting old devices")

query+=("
FLUSH LOGS;
RESET MASTER;
#deleting logs")

query+=("
OPTIMIZE TABLE chat,configs,devices,events,iftrack,incidents,interfaces,iptrack,links,locations,modules,monitoring,networks,nodes,nodetrack,stock,stolen,system,users,vlans,wlan;
#optimizing tables")

query+=("
ANALYZE TABLE chat,configs,devices,events,iftrack,incidents,interfaces,iptrack,links,locations,modules,monitoring,networks,nodes,nodetrack,stock,stolen,system,users,vlans,wlan;
#analyzing tables")

for i in "${!query[@]}"
do
	do_sql "${query[$i]} ($(($i+1))/${#query[@]})"
done

db_size_after=$(db_size)
saved=`printf '%.2f\n' $(echo "scale=10; $db_size_before - $db_size_after"|bc)`
saved_percent=`printf '%.2f\n' $(echo "scale=10; $saved / $db_size_before * 100"|bc)` 
write_output "database $dbname is now $db_size_after MB ($saved MB, $saved_percent % saved)"
exit_script 0
