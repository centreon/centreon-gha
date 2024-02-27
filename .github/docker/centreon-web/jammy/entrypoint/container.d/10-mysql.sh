#!/bin/sh

# Start database server.
service mysql start

# Wait for the database to be up and running.
while true ; do
  timeout 10 mysql -e 'SELECT id FROM nagios_server' centreon
  retval=$?
  if [ "$retval" = 0 ] ; then
    break ;
  else
    echo 'DB server is not yet responding.'
    sleep 1
  fi
done
