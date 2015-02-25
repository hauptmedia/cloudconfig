<?php
return function($clusterConfig, $nodeConfig, $cloudConfig) {

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

    $etcdName =
        !empty($nodeConfig['etcd']['name']) ?
            $nodeConfig['etcd']['name'] :
            $nodeConfig['hostname'];
    
    $etcdAddr =
        !empty($nodeConfig['etcd']['addr']) ?
        $nodeConfig['etcd']['addr'] :
        '127.0.0.1:2379';

    $etcdPeerAddr =
        !empty($nodeConfig['etcd']['peer-addr']) ?
            $nodeConfig['etcd']['peer-addr'] :
            $nodeConfig['ip'] . ':2380';

    $discovery =
        !empty($nodeConfig['etcd']['discovery']) ?
            $nodeConfig['etcd']['discovery'] :
            $clusterConfig['discovery'];
    
    
    $cloudConfig['coreos']['etcd'] = array(
        'name'      => $etcdName,
        'discovery' => $discovery,
        'addr'      => $etcdAddr,
        'peer-addr' => $etcdPeerAddr
    );


return $cloudConfig;

};