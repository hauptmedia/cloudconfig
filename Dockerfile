FROM debian:jessie

ENV	DEBIAN_FRONTEND noninteractive

#Core OS image from where we extract the coreos-cloudinit tool (used to verify cloudinit files)
ENV	COREOS_CHANNEL_ID=alpha
ENV	COREOS_VERSION_ID=current

ENV	COREOS_IMAGE_NAME="coreos_production_pxe_image.cpio.gz"
ENV	COREOS_BASE_URL="http://${COREOS_CHANNEL_ID}.release.core-os.net/amd64-usr"

ENV	COREOS_IMAGE_URL="${COREOS_BASE_URL}/${COREOS_VERSION_ID}/${COREOS_IMAGE_NAME}"

# install required packges
RUN	apt-get update -qq && \
	apt-get install -y cpio squashfs-tools curl apache2 php5 php5-curl bzip2 && \
	apt-get clean autoclean && \
	apt-get autoremove --yes && \
	rm -rf /var/lib/{apt,dpkg,cache,log}/

# configure apache
RUN	rm -rf /var/www && \
	a2enmod php5 && \
	a2enmod rewrite

# extract coreos-cloudinit from CoreOS Image file
WORKDIR	/tmp
RUN	curl -L --silent ${COREOS_IMAGE_URL} | zcat | cpio -iv && \
	unsquashfs usr.squashfs && \
	cp /tmp/squashfs-root/bin/coreos-cloudinit /usr/local/bin && \
	rm -rf /tmp/*

EXPOSE 80

VOLUME ["/var/log/apache2"]
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
