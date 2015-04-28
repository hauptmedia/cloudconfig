<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }

    $cloudConfig['coreos']['units'][] = array(
        'name'      => "ssh-agent.service",
        'command'   => 'start',
        'content'   =>
            "[Unit]\n" .
            "Description=SSH agent service\n" .
            "IgnoreOnIsolate=true\n" .
            "Before=docker.service\n" .
            "\n" .
            "[Service]\n" .
            "User=core\n" .
            "Group=core\n" .
            "Environment=SSH_AUTH_SOCK=/tmp/ssh-agent.sock\n" .
            "Type=forking\n" .
            "Restart=always\n" .
            "ExecStartPre=-/bin/rm -rf \$SSH_AUTH_SOCK\n" .
            "ExecStart=/usr/bin/ssh-agent -a \$SSH_AUTH_SOCK\n" .
            "ExecStartPost=/usr/bin/ssh-add\n" .
            "\n" .
            "[Install]\n" .
            "WantedBy=multi-user.target\n"
    );

    return $cloudConfig;
};