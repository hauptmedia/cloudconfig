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

    $cloudConfig['coreos']['etcd'] = array(
        'name'      => $nodeConfig['hostname'],
        'discovery' => $clusterConfig['discovery'],
        'addr'      => $nodeConfig['ip'] . ':4001',
        'peer-addr' => $nodeConfig['ip'] . ':7001'
    );


return $cloudConfig;

};