<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $sshPrivateKeyConfig = array();

    if(!empty($clusterConfig['ssh-key'])) {
        $sshPrivateKeyConfig = array_merge($sshPrivateKeyConfig, $clusterConfig['ssh-key']);
    }

    if(!empty($nodeConfig['ssh-key'])) {
        $sshPrivateKeyConfig = array_merge($sshPrivateKeyConfig, $nodeConfig['ssh-key']);
    }

    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    $cloudConfig['write_files'][] = array(
        'path'          => '/home/core/.ssh/id_rsa',
        'owner'         => 'core:core',
        'permissions'   => '0600',
        'content'       => $sshPrivateKeyConfig["private"]
    );


    $cloudConfig['write_files'][] = array(
        'path'          => '/home/core/.ssh/id_rsa.pub',
        'owner'         => 'core:core',
        'permissions'   => '0600',
        'content'       => $sshPrivateKeyConfig["public"]
    );


    return $cloudConfig;
};