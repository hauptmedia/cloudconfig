<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('ssh-authorized-keys', $nodeConfig)) {
        return;
    }

    return array(
        'ssh_authorized_keys' => $nodeConfig['ssh-authorized-keys']
    );
};