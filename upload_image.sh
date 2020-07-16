#!/bin/bash

# Stop execution if a step fails
set -e

DOCKER_USERNAME=lbaw2022 # Replace by your docker hub username
IMAGE_NAME=lbaw2022      # Replace with your group's image name

# Ensure that dependencies are available
composer install
php artisan clear-compiled
php artisan optimize

docker build -t $DOCKER_USERNAME/$IMAGE_NAME .

# if [ "$#" -eq 0 ]; then
docker push $DOCKER_USERNAME/$IMAGE_NAME
# fi
