<?php
return function($clusterConfig, $nodeConfig) {
    return array(
        'coreos' => array(
            'units' => array(
                array(
                    'name'      => 'set-host-dns-entry.service',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=Set the dns entry for this host\n" .
                        "\n" .
                        "[Service]\n" .
                        "Type=oneshot\n" .
                        "RemainAfterExit=yes\n".
                        "EnvironmentFile=/etc/host.env\n".
                        "ExecStart=/opt/bin/skydns-set-record \${HOSTNAME} \${PUBLIC_IPV4}\n".
                        "\n" .
                        "[Install]\n".
                        "WantedBy=multi-user.target"
                )
            )
        )
    );
};