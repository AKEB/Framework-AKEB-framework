#!/bin/bash

cd /app/ && composer update 

cd /app/vendor/akeb/framework/src/ && SERVER_ROOT=/app php migrate.php

cd /app/vendor/akeb/framework/src/crons/ && SERVER_ROOT=/app ./run_all.sh

# cd /app/crons/ && ./run_all.sh

