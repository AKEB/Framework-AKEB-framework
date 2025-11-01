#!/bin/bash

cd /app/

if [ "$DEVELOPMENT" = "True" ] ; then
	echo "Development mode"
	if [ -d "/app_framework" ]; then
		echo "Using local framework repository."
		rm -rf /app/composer.lock
		composer config repositories.framework path /app_framework
		cd /app && composer install
	else
		echo "Local /app_framework directory not found. Relying on composer.json for remote repository."
		rm -rf /app/composer.lock
		composer config --unset repositories.framework
		cd /app && composer install --prefer-dist --no-interaction --no-dev --no-scripts
	fi
else
	echo "Production mode"
fi

mkdir -p js/framework css/framework lang/framework
rm -rf js/framework css/framework lang/framework

cp -R vendor/akeb/framework/src/js js/framework/
cp -R vendor/akeb/framework/src/css css/framework/
cp -R vendor/akeb/framework/src/lang lang/framework/

cd /app/vendor/akeb/framework/src/ && SERVER_ROOT=/app php migrate.php

cd /app/vendor/akeb/framework/src/crons/ && chmod +x *.sh && SERVER_ROOT=/app ./run_all.sh

cd /app/crons/ && chmod +x *.sh && ./run_all.sh

echo "Server started successfully.";
echo "For run server in browser type: http://127.0.0.1:${NGINX_PORT}"

if [ "$DEVELOPMENT" = "True" ] ; then
	echo "For run phpmyadmin in browser type: http://127.0.0.1:${PHPMYADMIN_PORT}"
fi
