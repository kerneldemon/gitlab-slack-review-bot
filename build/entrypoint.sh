#!/bin/bash

export TERM=xterm

/etc/init.d/php7.4-fpm start &
/usr/local/bin/docker-entrypoint.sh mysqld &
/etc/init.d/nginx start &

screen -dmS 'doctrine-database-create' bash -c 'until php /var/www/bin/console --no-interaction doctrine:database:create; do echo Unsuccessful; done'
screen -dmS 'doctrine-migrations-migrate' bash -c 'until php /var/www/bin/console --no-interaction doctrine:migrations:migrate; do echo Unsuccessful; done'
screen -dmS 'blacklist-sync' watch -n 1800 php /var/www/bin/console app:blacklist:sync

watch -n 1 php /var/www/bin/console app:project:setup
