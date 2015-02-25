<?php
header('Content-Type: text/plain');
?>
#!/bin/sh
COREOS_CHANNEL="alpha"
COREOS_INSTALL_DEV=/dev/sda

BASE_URL="<?=getenv('BASE_URL')?>"

if [ "$(id -u)" -ne 0 ]; then
   echo "This script must be run as root" 1>&2
   exit 1
fi

PRIMARY_NIC=$(ls /sys/class/net/|grep -v veth | grep -v docker | grep -v lo | xargs | cut -d" " -f1)

if [ -z ${PRIMARY_NIC} ]; then
	echo Could not determine primary network interface 1>&2
	exit 1
fi

MAC_ADDRESS=$(cat /sys/class/net/${PRIMARY_NIC}/address)
IP_ADDRESS=$(ip addr show ${PRIMARY_NIC}| awk '/inet /{print $2}' | cut -d"/" -f1 | xargs | cut -d" " -f1)
TMP_FILE=/tmp/$$-cloud-config.yml

if [ -f /etc/os-release ]; then
	. /etc/os-release
	IS_COREOS="true"
fi

QUERY_URL="${BASE_URL}/cloud-config.yml?mac=${MAC_ADDRESS}&ip=${IP_ADDRESS}&version=$VERSION"

echo
echo Configuration details
echo
echo CoreOS version: ${VERSION_ID}
echo Primary network interface: ${PRIMARY_NIC}
echo Primary IP: ${IP_ADDRESS}
echo MAC: ${MAC_ADDRESS}
echo

res=`curl --output /dev/null --silent --write-out "%{http_code}" "${QUERY_URL}"`

if [ "${res}" != "200" ]; then
	echo ${QUERY_URL} returned HTTP status code ${res}. Aborting! 1>&2
	exit 1
fi


curl --output ${TMP_FILE} --silent "${QUERY_URL}"

if [ ! -f ${TMP_FILE} ]; then
	echo Could not download cloud-config.yml file to ${TMP_FILE}. Aborting! 1>&2
	exit 1
fi

if [ ! -z $IS_COREOS ]; then
	coreos-cloudinit -validate --from-file=${TMP_FILE}
	if [ $? -ne 0 ];then
		echo
		echo "Could not validate cloud-config.yml. Aborting!" 1>&2
		exit 1
	fi
fi

echo New cloud-config.yml
echo
cat ${TMP_FILE}

echo ""
echo "" 
echo "Starting automatic provioning in 5 seconds (CTRL-C to abort)"

sleep 5

if [ -z $IS_COREOS ]; then
	echo "Installing CoreOS on ${COREOS_INSTALL_DEV}"
	echo

	curl -o coreos-install https://raw.githubusercontent.com/coreos/init/master/bin/coreos-install >/dev/null 2>&1
	chmod +x coreos-install
	./coreos-install -d ${COREOS_INSTALL_DEV} -C ${COREOS_CHANNEL} -c ${TMP_FILE} 

else
	echo "Updating cloud-config.yml in /var/lib/coreos-install/user_data"
	echo
	mv /var/lib/coreos-install/user_data /var/lib/coreos-install/user_data.backup
	mv ${TMP_FILE} /var/lib/coreos-install/user_data
fi
	
echo ""
echo ""
echo "Rebooting in 5 seconds (CTRL-C to abort)"
sleep 5
reboot
