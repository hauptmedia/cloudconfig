#!/bin/sh
IP=$(/sbin/ifconfig eth1 | grep "inet addr" | awk -F: '{print $2}' | awk '{print $1}')

dos2unix docker-entrypoint.sh

docker build -t="hauptmedia/puppetmaster" .

docker run -i -t --rm \
-p 80:80 \
-v $(pwd)/www:/var/www \
-e BASE_URL=http://$IP \
hauptmedia/puppetmaster
