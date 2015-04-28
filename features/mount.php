<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $mountConfig = array();

    if(!empty($clusterConfig['mount'])) {
        $mountConfig = array_merge($mountConfig, $clusterConfig['mount']);
    }

    if(!empty($nodeConfig['mount'])) {
        $mountConfig = array_merge($mountConfig, $nodeConfig['mount']);
    }
    
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }
    
    foreach($mountConfig as $mountConfigEntry) {
        $systemdName = substr(
            str_replace("/", "-", $mountConfigEntry["mount-point"]) . ".mount",
            1
        );

        $cloudConfig['coreos']['units'][] = array(
            'name'      => $systemdName,
            'command'   => 'start',
            'content'   =>
                "[Unit]\n" .
                "Before=docker.service\n" .
                "[Mount]\n" .
                "What=" . $mountConfigEntry['dev'] . "\n" .
                "Where=" . $mountConfigEntry['mount-point'] . "\n" .
                "Type=" . $mountConfigEntry['type'] . "\n"
        );
    }
    
    return $cloudConfig;
};