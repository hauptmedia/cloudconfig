<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $useSSL = in_array('etcd2-ssl', $enabledFeatures);

    // merge config  node <= cluster <= defaults
    $etcd2Config = array();

    if(!empty($nodeConfig['hostname'])) {
        $etcd2Config['name'] = $nodeConfig['hostname'];
    }

    if(!empty($nodeConfig['ip'])) {
        $etcd2Config['advertise-client-urls']       = ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2379';
        $etcd2Config['listen-client-urls']          = ($useSSL ? 'https://' : 'http://') . '127.0.0.1:2379,' . ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2379';
        $etcd2Config['initial-advertise-peer-urls'] = ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2380';
        $etcd2Config['listen-peer-urls']            = ($useSSL ? 'https://' : 'http://') . '127.0.0.1:2380,' . ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2380';
    }

    if(!empty($clusterConfig['etcd2'])) {
        $etcd2Config = array_merge($etcd2Config, $clusterConfig['etcd2']);
    }

    if(!empty($nodeConfig['etcd2'])) {
        $etcd2Config = array_merge($etcd2Config, $nodeConfig['etcd2']);
    }

    // construct cloud-config.yml
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }
    
    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'etcd2.service',
        'command'   => 'start'
        
    );
    
    if(empty($etcd2Config['name'])) {
        throw new \Exception("Missing etcd2 name");
    }

    if(empty($etcd2Config['advertise-client-urls'])) {
        throw new \Exception("Missing etcd2 advertise-client-urls");
    }

    if(empty($etcd2Config['initial-advertise-peer-urls'])) {
        throw new \Exception("Missing etcd2 initial-advertise-peer-urls");
    }
    
    $cloudConfig['coreos']['etcd2'] = $etcd2Config;


    return $cloudConfig;

};