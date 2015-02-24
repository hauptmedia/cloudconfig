<?php
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