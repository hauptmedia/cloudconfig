<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('hostname', $nodeConfig)) {
        throw new \Exception("Missing hostname in nodeConfig");
    }

    return array(
        'hostname' => $nodeConfig['hostname']
    );
};