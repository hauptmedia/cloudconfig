<?php
return function($clusterConfig, $nodeConfig) {
    return array(
        'coreos' => array(
            'units' => array(
                array(
                    'name'      => 'format-ephemeral.service',
                    'command'   => 'start',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=Formats the ephemeral drive\n" .
                        "[Service]\n" .
                        "Type=oneshot\n" .
                        "RemainAfterExit=yes\n" .
                        "ExecStart=/usr/sbin/wipefs -f /dev/sdb\n" .
                        "ExecStart=/usr/sbin/mke2fs -q -t ext4 -b 4096 -i 4096 -I 128 /dev/sdb\n"
                ),
                array(
                    'name'      => 'var-lib-docker.mount',
                    'command'   => 'start',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=Mount ephemeral to /var/lib/docker\n" .
                        "Requires=format-ephemeral.service\n" .
                        "After=format-ephemeral.service\n" .
                        "Before=docker.service\n" .
                        "[Mount]\n" .
                        "What=/dev/sdb\n" .
                        "Where=/var/lib/docker\n" .
                        "Type=ext4\n"
                )
            )
        )
    );
};