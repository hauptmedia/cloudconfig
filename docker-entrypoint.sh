#!/bin/sh

if [ -z ${BASE_URL} ]; then

	echo "No BASE_URL was specified! Aborting!" 1>&2
	exit 1
fi

echo ""
echo ""
echo "You may now setup your nodes with the following command"
echo ""
echo "  curl -sSL ${BASE_URL}/install.sh | sudo sh"
echo ""
echo ""

exec "$@"

