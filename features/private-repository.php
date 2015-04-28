<?php
//TODO: Add support for private repositories with authentification
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $privateRepositoryConfig = array();

    if(!empty($clusterConfig['private-repository'])) {
        $privateRepositoryConfig = array_merge($privateRepositoryConfig, $clusterConfig['private-repository']);
    }

    if(!empty($nodeConfig['private-repository'])) {
        $privateRepositoryConfig = array_merge($privateRepositoryConfig, $nodeConfig['private-repository']);
    }

    //Skip writing the DOCKER_OPTS file, if skydns is already defined, as it will override DOCKER_OPTS, too
    //there is currently a hack inside the skydns which will add the insecure-registry flat inside the skydns feature
    if(!in_array('skydns', $enabledFeatures) &&
        array_key_exists('insecure-addr', $privateRepositoryConfig)) {

        if (!array_key_exists('write_files', $cloudConfig)) {
            $cloudConfig['write_files'] = array();
        }

        $cloudConfig['write_files'][] = array(
            'path'          => '/etc/systemd/system/docker.service.d/50-docker-opts.conf',
            'content'       =>
                "[Service]\n" .
                "Environment='DOCKER_OPTS=--insecure-registry=\"" . $privateRepositoryConfig['insecure-addr'] . "\"'\n"

        );
    }

    return $cloudConfig;

};