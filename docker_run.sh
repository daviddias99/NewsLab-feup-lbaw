#!/bin/bash
set -e

cd /var/www; php artisan config:cache ; rm public/storage -rf ; php artisan storage:link ; php artisan migrate:refresh --seed --force;
env >> /var/www/.env
php-fpm7.2 -D

echo "* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1" >> cronfile
crontab cronfile
rm cronfile
cron -f & 
nginx -g "daemon off;"

