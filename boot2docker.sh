#!/bin/sh
if [ -z $BASE_URL ]; then
    BASE_URL="http://"$(/sbin/ifconfig eth1 | grep "inet addr" | awk -F: '{print $2}' | awk '{print $1}')
fi

if [ ! -d ./var ]; then
    mkdir ./var
fi

dos2unix docker-entrypoint.sh

docker build -t="hauptmedia/cloudconfig" .

docker run -i -t --rm \
-p 80:80 \
-v $(pwd)/var:/opt/cloudconfig/var \
-v $(pwd)/www:/opt/cloudconfig/www \
-v $(pwd)/features:/opt/cloudconfig/features \
-e BASE_URL=$BASE_URL \
hauptmedia/cloudconfig \
$@
