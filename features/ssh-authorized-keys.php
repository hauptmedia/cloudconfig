<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('ssh-authorized-keys', $nodeConfig)) {
        throw new \Exception("Missing ssh-authorized-keys in nodeConfig");
    }

    return array(
        'ssh_authorized_keys' => $nodeConfig['ssh-authorized-keys']
    );
};