<?php
return function($clusterConfig, $nodeConfig) {
    $sshPrivateKeyConfig = $nodeConfig['ssh-key'];

    return array(
        'write_files' => array(
            array(
                'path'          => '/home/core/.ssh/id_rsa',
                'owner'         => 'core:core',
                'permissions'   => '0600',
                'content'       => $sshPrivateKeyConfig["private"]
            ),
            array(
                'path'          => '/home/core/.ssh/id_rsa.pub',
                'owner'         => 'core:core',
                'permissions'   => '0600',
                'content'       => $sshPrivateKeyConfig["public"]
            )
        )
    );
};