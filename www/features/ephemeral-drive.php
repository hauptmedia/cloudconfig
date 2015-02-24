<?php
/*
 * ETCD_CA_FILE - location of CA certificate used to sign client certificates.
 * ETCD_CERT_FILE - location of ceritificate used for communication with clients
 * ETCD_KEY_FILE - location of private key used for communication with clients
 * ETCD_CA_FILE - location of CA certificate used for signing peer certificates. In my case it was the same CA.
 * ETCD_PEER_CERT_FILE - location of ceritificate used for communication with other etcd nodes
 * ETCD_PEER_KEY_FILE - location of private key used for communication with other etcd nodes
 */
return function($clusterConfig, $nodeConfig, $cloudConfig) {

    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }
    
    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'format-ephemeral.service',
        'command'   => 'start',
        'content'   => '
[Unit]
Description=Formats the ephemeral drive
[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/usr/sbin/wipefs -f /dev/sdb
ExecStart=/usr/sbin/mke2fs -q -t ext4 -b 4096 -i 4096 -I 128 /dev/sdb'
        );

    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'var-lib-docker.mount',
        'command'   => 'start',
        'content'   => '
[Unit]
Description=Mount ephemeral to /var/lib/docker
Requires=format-ephemeral.service
After=format-ephemeral.service
Before=docker.service
[Mount]
What=/dev/sdb
Where=/var/lib/docker
Type=ext4'
    );

            
    return $cloudConfig;

};