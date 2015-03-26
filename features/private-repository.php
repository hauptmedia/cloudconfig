<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {


    $privateRepositoryConfig = array();

    if(!empty($clusterConfig['private-repository'])) {
        $privateRepositoryConfig = array_merge($privateRepositoryConfig, $clusterConfig['private-repository']);
    }

    if(!empty($nodeConfig['private-repository'])) {
        $privateRepositoryConfig = array_merge($privateRepositoryConfig, $nodeConfig['private-repository']);
    }

    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    if(array_key_exists('insecure-addr', $privateRepositoryConfig)) {
        $cloudConfig['write_files'][] = array(
            'path'          => '/etc/systemd/system/docker.service.d/50-insecure-registry.conf',
            'content'       =>
                "[Service]\n" .
                "Environment='DOCKER_OPTS=--insecure-registry=\"" . $privateRepositoryConfig['insecure-addr'] . "\"'\n"

        );
    }

    return $cloudConfig;

};