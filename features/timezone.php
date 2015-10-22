<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('timezone', $nodeConfig)) {
        return;
    }

    $timezone = $nodeConfig['timezone'];

    if($timezone == "") {
        return;
    }

    return array(
        'coreos' => array(
            'units' => array(
                array(
                    'name'      => 'timezone.service',
                    'command'   => 'start',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=Set the timezone\n" .
                        "[Service]\n" .
                        "Type=oneshot\n" .
                        "RemainAfterExit=yes\n" .
                        "ExecStart=/usr/bin/timedatectl set-timezone " . $timezone . "\n"
                )
            )
        )
    );
};