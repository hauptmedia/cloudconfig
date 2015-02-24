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
        'name'      => 'fleet.service',
        'command'   => 'start'
    );

    $cloudConfig['coreos']['fleet'] = array(
        'public-ip' => $nodeConfig["ip"],
        'metadata'  => $nodeConfig["metadata"]
    );

    return $cloudConfig;

};