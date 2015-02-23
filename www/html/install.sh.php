<?php
header('Content-Type: text/plain');
?>
#!/bin/sh
BASE_URL="<?=getenv('BASE_URL')?>"
PRIMARY_NIC=$(ls /sys/class/net/|grep -v veth | grep -v docker | grep -v lo | xargs | cut -d" " -f1)

if [ -z ${PRIMARY_NIC} ]; then
	echo could not determine primary network interface
	exit 1
fi

MAC_ADDRESS=$(cat /sys/class/net/${PRIMARY_NIC}/address)
IP_ADDRESS=$(ip addr show ${PRIMARY_NIC}| awk '/inet /{print $2}' | cut -d"/" -f1 | xargs | cut -d" " -f1)

echo Primary network interface: ${PRIMARY_NIC}
echo Primary IP: ${IP_ADDRESS}
echo MAC: ${MAC_ADDRESS}

#curl -o coreos-install https://raw.githubusercontent.com/coreos/init/master/bin/coreos-install >/dev/null 2>&1
#chmod +x coreos-install

#./coreos-install -d /dev/sda -C alpha -c /tmp/cloud-config.yml


