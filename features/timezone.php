<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $timezone = "";

    if(!empty($clusterConfig['timezone'])) {
        $timezone = $clusterConfig['timezone'];
    }

    if(!empty($nodeConfig['timezone'])) {
        $timezone = $nodeConfig['timezone'];
    }


    if($timezone == "") {
        return;
    }

    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }
    
    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'timezone.service',
        'command'   => 'start',
        'content'   => 
            "[Unit]\n" .
            "Description=Set the timezone\n" .
            "[Service]\n" .
            "Type=oneshot\n" .
            "RemainAfterExit=yes\n" .
            "ExecStart=/usr/bin/timedatectl set-timezone " . $timezone . "\n"
        );

    return $cloudConfig;

};