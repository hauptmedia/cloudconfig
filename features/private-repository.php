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

    if(
        array_key_exists('username', $privateRepositoryConfig) &&
        array_key_exists('password', $privateRepositoryConfig) &&
        array_key_exists('registry', $privateRepositoryConfig)
    ) {
        $dockercfg = array(
            $privateRepositoryConfig['registry'] => array(
                'auth'  => base64_encode($privateRepositoryConfig['username'] . ':' . $privateRepositoryConfig['password']),
                'email' => 'username@example.com'
            )
        );

        $writeFiles[] = array(
            'path'          => '/home/core/.dockercfg',
            'owner'         => 'core:core',
            'permissions'   => '0644',
            'content'       => json_encode($dockercfg)
        );

        $writeFiles[] = array(
            'path'          => '/root/.dockercfg',
            'owner'         => 'root:root',
            'permissions'   => '0644',
            'content'       => json_encode($dockercfg)
        );
    }

    return array(
        'write_files' => $writeFiles
    );
};