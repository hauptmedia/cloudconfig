#!/bin/sh
if [ -z $IP ]; then
    IP=$(/sbin/ifconfig eth1 | grep "inet addr" | awk -F: '{print $2}' | awk '{print $1}')
fi

if [ ! -d ./var ]; then
    mkdir ./var
fi

dos2unix docker-entrypoint.sh

docker build -t="hauptmedia/cloudconfig" .

docker run -i -t --rm \
-p 80:80 \
-v $(pwd)/var:/opt/cloudconfig/var \
-e BASE_URL=http://$IP \
hauptmedia/cloudconfig \
$@
