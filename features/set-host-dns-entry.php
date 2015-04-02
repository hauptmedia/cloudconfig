<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {

    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }
    ///etc/host.env
    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'set-host-dns-entry.service',
        'command'   => 'start',
        'content'   => 
            "[Unit]\n" .
            "Description=Set the dns entry for this host\n" .
            "Wants=etcd.service\n" .
            "[Service]\n" .
            "Type=oneshot\n" .
            "RemainAfterExit=yes\n" .
            "EnvironmentFile=/etc/host.env\n".
            "ExecStart=/opt/bin/skydns-set-record \${HOSTNAME} \${PUBLIC_IPV4}\n"
        );
            
    return $cloudConfig;

};