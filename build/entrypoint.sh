#!/bin/bash

export TERM=xterm

/etc/init.d/php7.4-fpm start &
/usr/local/bin/docker-entrypoint.sh mysqld &
/etc/init.d/nginx start &

screen -dmS 'blacklist-sync' watch -n 1800 php /var/www/bin/console app:blacklist:sync

watch -n 1 php /var/www/bin/console app:project:setup
