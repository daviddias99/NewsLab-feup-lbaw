#!/bin/bash

# docker run -d -it -p 8080:80 -v $PWD/:/var/www/html lbaw2022/lbaw2022:latest -e DB_USERNAME="lbaw2022" -e DB_PASSWORD="DP580136" 
docker run -it -p 8080:80 -e DB_DATABASE="lbaw2022" -e DB_USERNAME="lbaw2022" -e DB_PASSWORD="DP580136" lbaw2022/lbaw2022
docker-compose up