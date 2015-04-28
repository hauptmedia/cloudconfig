<?php
//TODO: Add support for private repositories with authentification
return function($clusterConfig, $nodeConfig) {
    $privateRepositoryConfig = $nodeConfig['private-repository'];

    $writeFiles = array();

    //Skip writing the DOCKER_OPTS file, if skydns is already defined, as it will override DOCKER_OPTS, too
    //there is currently a hack inside the skydns which will add the insecure-registry flat inside the skydns feature
    if(!in_array('skydns', $nodeConfig['features']) &&
        array_key_exists('insecure-addr', $privateRepositoryConfig)) {


        $writeFiles[] = array(
            'path'          => '/etc/systemd/system/docker.service.d/50-docker-opts.conf',
            'content'       =>
                "[Service]\n" .
                "Environment='DOCKER_OPTS=--insecure-registry=\"" . $privateRepositoryConfig['insecure-addr'] . "\"'\n"

        );
    }

    return array(
        'write_files' => $writeFiles

    );
};