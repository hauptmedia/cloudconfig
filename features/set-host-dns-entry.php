<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {

    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }

    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'set-host-dns-entry.service',
        'content'   =>
            "[Unit]\n" .
            "Description=Set the dns entry for this host\n" .
            "Wants=etcd.service\n" .
            "[Service]\n" .
            "Type=simple\n" .
            "EnvironmentFile=/etc/host.env\n".
            "ExecStart=/opt/bin/skydns-set-record \${HOSTNAME} \${PUBLIC_IPV4}\n"
        );

    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'set-host-dns-entry.timer',
        'command'   => 'start',
        'content'   =>
            "[Unit]\n" .
            "Description=Set the dns entry for this host\n" .
            "[Timer]\n" .
            "OnBootSec=3min\n" .
            "Unit=set-host-dns-entry.service\n".
            "[Install]\n".
            "WantedBy=multi-user.target"
    );

    return $cloudConfig;

};