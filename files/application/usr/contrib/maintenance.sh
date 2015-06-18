#!/bin/sh
toggling_count=50 # count of mac address entries in table iftrack; occurance higher than that count will be defined as toggling and deleted
#event_count=50 # count of entries in event table; occurance higher than that count will be defined as spam and deleteed
logfile_seperator="----------" # script internal variable for header and footer of log entries
#nedi_conf="nedi.conf"
#logfile="/var/log/nedi/nedi.log"
unset $logfile

function do_sql {
	sql=$( echo "$1" | cut -d "#" -f1 )
	write_output "$( echo $1 | cut -d '#' -f2)"
	
	case $2 in
	"silent")
	result_arr=(`mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" -s --ssl -e "$sql"`)
	;;
	
	*)
		if [ -z $logfile ];
		then
			mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" --ssl -e "$sql"
		else
			mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" --ssl -B -e "$sql" 2>&1 >> $logfile
		fi
	;;
	esac
}

function db_size {
	sql="SELECT ROUND(sum(data_length+index_length)/1024/1024,3) AS 'total (MB)' FROM information_schema.tables WHERE table_schema='$dbname';"
	echo `mysql --host="$dbhost" --user="$dbuser" --password="$dbpass" "$dbname" --ssl -e "$sql" -BN`;
}

function write_output {
	if [ -z $logfile ];
	then
	        if  [[ "$1" != *${logfile_seperator}* ]]; then
			echo $2 "$1"
		fi
	else
#	        echo $2 `date +"%T"` "$1" >> $logfile
	        echo $2 "$1" >> $logfile
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
		echo -e "nedi maintenance"
		echo -e "17.07.2013, daniel.obst uni-leipzig.de"
		echo -e "--------------------------------------\n"
		echo -e "- deletes db entries older than \$retire variable in nedi.conf:"
		echo -e "  - events, configs, tracking information"
		echo -e "  - unnecessary tracking information of toggling interfaces"
		echo -e "- deletes devices last seen before \$retire variable"
		echo -e "  - devices and their dependencies in db"
		echo -e "  - rrd and config files"
		echo -e "- optimizes tables\n"
		echo -e "Usage: `basename $0` <config> [<logfile>]\n"
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
	dbuser=`grep '^dbuser' $nedi_conf | awk -F ' ' '{ print $NF }'`	
	dbhost=`grep '^dbhost' $nedi_conf | awk -F ' ' '{ print $NF }'`	
	dbpass=`grep '^dbpass' $nedi_conf | awk -F ' ' '{ print $NF }'`
	dbname=`grep '^dbname' $nedi_conf | awk -F ' ' '{ print $NF }'`	
	retire_days=`grep '^retire' $nedi_conf | awk -F ' ' '{ print $NF }'`
	nedipath=`grep '^nedipath' $nedi_conf | awk -F ' ' '{ print $NF }'`
	
	for (( i=0; i<${#dbpass}; i++ )); do dbpass_obfuscated=`echo "${dbpass_obfuscated}*"`; done
	write_output "database:\t$dbuser@$dbhost on $dbname, pass=$dbpass_obfuscated" "-e"
	write_output "max age:\t$retire_days days" "-e"
	write_output "nedipath:\t$nedipath" "-e"
	write_output "--"	
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

# sql jobs, terminating with a necessary comment
query+=("
DELETE FROM events WHERE time < $retire_before;
#delete old events")

query+=("
DELETE FROM iftrack WHERE ifupdate < $retire_before;
#delete old interface tracking information")

query+=("
DROP TABLE IF EXISTS temp_table;
CREATE TEMPORARY TABLE temp_table (
	SELECT mac FROM iftrack GROUP BY mac HAVING count(*) > $toggling_count
);
DELETE
FROM iftrack
WHERE mac IN (SELECT mac FROM temp_table)
;
DROP TABLE IF EXISTS temp_table;
#delete interface tracking information of toggling interfaces")

query+=("
DELETE FROM iptrack WHERE ipupdate < $retire_before;
#delete old ip tracking information")


query+=("
DROP TABLE IF EXISTS temp_table;
CREATE TABLE temp_table (
        SELECT device,devip FROM devices where lastdis < $retire_before 
)
;
#old devices: resolve depenencies")

query+=("
DELETE
FROM configs
WHERE device IN (SELECT device FROM temp_table)
;
#- delete configs")

query+=("
DELETE
FROM events
WHERE device IN (SELECT device FROM temp_table)
;
#- delete events")

#query+=("
#DELETE
#FROM iftrack
#WHERE device IN (SELECT device FROM temp_table)
#;
#- delete interface tracking information")

query+=("
DELETE
FROM incidents
WHERE device IN (SELECT device FROM temp_table)
;
#- delete old incidents")

query+=("
DELETE
FROM interfaces
WHERE device IN (SELECT device FROM temp_table)
;
#- delete interfaces")

#query+=("
#DELETE
#FROM iptrack
#WHERE device IN (SELECT device FROM temp_table)
#;
#- delete ip tracking information")

query+=("
DELETE
FROM links
WHERE device IN (SELECT device FROM temp_table)
;
#- delete links")

query+=("
DELETE
FROM modules
WHERE device IN (SELECT device FROM temp_table)
;
#- delete modules")

query+=("
DELETE
FROM monitoring
WHERE monip IN (SELECT devip FROM temp_table)
;
#- delete monitoring entries")

query+=("
DELETE
FROM networks
WHERE device IN (SELECT device FROM temp_table)
;
#- delete networks")

#query+=("
#DELETE
#FROM nodes
#WHERE device IN (SELECT device FROM temp_table)
#;
##- delete nodes")

query+=("
DELETE
FROM nodetrack
WHERE device IN (SELECT device FROM temp_table)
;
#- delete node tracking information")

query+=("
DELETE
FROM vlans
WHERE device IN (SELECT device FROM temp_table)
;
#- delete vlans")

#delete rrd data 
query+=("
SELECT device FROM devices where lastdis < $retire_before;
#- delete files")

#erasing devices
query+=("
DELETE
FROM devices
WHERE device IN (SELECT device FROM temp_table);

DROP TABLE IF EXISTS temp_table;
#--> delete old devices")

query+=("
FLUSH LOGS;
RESET MASTER;
#delete logs")

query+=("
ANALYZE TABLE chat,configs,devices,events,iftrack,incidents,interfaces,iptrack,links,locations,modules,monitoring,networks,nodes,nodetrack,stock,stolen,system,users,vlans,wlan;
#analyze tables")

query+=("
OPTIMIZE TABLE chat,configs,devices,events,iftrack,incidents,interfaces,iptrack,links,locations,modules,monitoring,networks,nodes,nodetrack,stock,stolen,system,users,vlans,wlan;
#optimize tables")



for i in "${!query[@]}"
do
	todo=$( echo "${query[$i]}" | cut -s -d "#" -f2)
#	echo -n "$(($i+1))/${#query[@]} "
	write_output "$(($i+1))/${#query[@]}\t" "-n -e"
	case $todo in
	"- delete files")
		do_sql "${query[$i]}" "silent"
                for device in ${result_arr[*]}
       	        do
			#write_output "device: $device"
			rrdpath=$nedipath/rrd/$device/
			confpath=$nedipath/conf/$device/
			if [[ -d "$rrdpath" && ! -L "$rrdpath" ]]; then
				write_output "$rrdpath"
				write_output "`rm -r  $rrdpath 2>&1`"
			fi
			if [[ -d "$confpath" && ! -L "$confpath" ]]; then
				write_output "$confpath"
				write_output "`rm -r  $confpath 2>&1`"
			fi
                done
	;;

	*)
		do_sql "${query[$i]}"
	;;
	esac
done


db_size_after=$(db_size)
saved=`printf '%.2f\n' $(echo "scale=10; $db_size_before - $db_size_after"|bc)`
saved_percent=`printf '%.2f\n' $(echo "scale=10; $saved / $db_size_before * 100"|bc)` 
write_output "database $dbname is now $db_size_after MB ($saved MB, $saved_percent % saved)"
exit_script 0
