<?php
return function($clusterConfig, $nodeConfig, $cloudConfig) {
    // merge config  node <= cluster <= defaults
    $etcdConfig = array(
        'addr'  => '127.0.0.1:2379'
    );

    if(!empty($nodeConfig['hostname'])) {
        $etcdConfig['name'] = $nodeConfig['hostname'];
    }

    if(!empty($nodeConfig['ip'])) {
        $etcdConfig['peer-addr'] = $nodeConfig['ip'] . ':2380';
    }

    if(!empty($cloudConfig['etcd'])) {
        $etcdConfig = array_merge($etcdConfig, $cloudConfig['etcd']);
    }

    if(!empty($nodeConfig['etcd'])) {
        $etcdConfig = array_merge($etcdConfig, $nodeConfig['etcd']);
    }

    // construct cloud-config.yml
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }
    
    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'etcd.service',
        'command'   => 'start'
        
    );
    
    if(empty($etcdConfig['name'])) {
        throw new \Exception("Missing etcd name");
    }

    if(empty($etcdConfig['addr'])) {
        throw new \Exception("Missing etcd addr");
    }

    if(empty($etcdConfig['peer-addr'])) {
        throw new \Exception("Missing etcd peer-addr");
    }
    
    $cloudConfig['coreos']['etcd'] = $etcdConfig;


    return $cloudConfig;

};