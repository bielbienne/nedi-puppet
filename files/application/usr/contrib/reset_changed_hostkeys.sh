#!/bin/sh

mysql nedi --column-names=false -Be '
  SELECT INET_NTOA(d.ip)
    FROM messages m
    JOIN devices d
      ON m.source = d.name
   WHERE m.time > UNIX_TIMESTAMP() - 86400
     AND m.info LIKE "%hostkey changed%";
' | (
  while read line; do
    echo "removing ${line}" >&2
    echo "/^${line} ssh-/d"
  done
  echo "wq"
) | ed -s ~/.ssh/known_hosts

# licensed under GPL, Daniel Albers <daniel@lbe.rs>, 20120120