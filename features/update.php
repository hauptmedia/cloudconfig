<?php
return function($clusterConfig, $nodeConfig, $cloudConfig) {
    // merge config  node <= cluster <= defaults
    $updateConfig = array(
        'group'           => 'stable',
        'reboot-strategy' => 'off'
    );

    if(!empty($clusterConfig['update'])) {
        $updateConfig = array_merge($updateConfig, $clusterConfig['update']);
    }

    if(!empty($nodeConfig['update'])) {
        $updateConfig = array_merge($updateConfig, $nodeConfig['update']);
    }

    // construct cloud-config.yml
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }
        
    $cloudConfig['coreos']['update'] = $updateConfig;

    return $cloudConfig;
};