<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    // determine which features are active for this node
    $enabledFeatures = array();

    if(!empty($clusterConfig['features'])) {
        $enabledFeatures = array_merge($enabledFeatures, $clusterConfig['features']);
    }

    if(!empty($nodeConfig['features'])) {
        $enabledFeatures = array_merge($enabledFeatures, $nodeConfig['features']);
    }


    // merge config  node <= cluster <= defaults
    $useSSL = in_array('etcd-ssl', $enabledFeatures);

    $fleetConfig = array();
    
    if($useSSL) {
        $fleetConfig['etcd_servers']        = "https://127.0.0.1:2379";
        $fleetConfig['etcd_cafile']         = "/etc/ssl/etcd/certs/ca.crt";
        $fleetConfig['etcd_keyfile']        = "/etc/ssl/etcd/private/client.key";
        $fleetConfig['etcd_certfile']       = "/etc/ssl/etcd/certs/client.crt";
    } else {
        $fleetConfig['etcd_servers']        = "http://127.0.0.1:2379";
    }

    if(!empty($nodeConfig['ip'])) {
        $fleetConfig['public-ip'] = $nodeConfig['ip'];
    }

    if(!empty($clusterConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $clusterConfig['fleet']);
    }

    if(!empty($nodeConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $nodeConfig['fleet']);
    }

    // construct cloud-config.yml
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

    $cloudConfig['coreos']['fleet'] = $fleetConfig;

    return $cloudConfig;

};