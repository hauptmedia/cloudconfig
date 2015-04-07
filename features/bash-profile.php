<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $bashProfileContent  =
        "[[ -f ~/.bashrc ]] && . ~/.bashrc\n" .
        "\n" .
        "if [ -f /etc/fleetctl.env ]; then\n" .
        "  . /etc/fleetctl.env\n" .
        "  export FLEETCTL_ENDPOINT FLEETCTL_CERT_FILE FLEETCTL_KEY_FILE FLEETCTL_CA_FILE\n" .
        "fi\n" .
        "\n" .
        "if [ -z \"\$SSH_AUTH_SOCK\" ] && [ -e /tmp/ssh-agent.sock ]; then\n" .
        "  export SSH_AUTH_SOCK=/tmp/ssh-agent.sock\n" .
        "fi\n";

    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    $cloudConfig['write_files'][] = array(
        'owner'         => 'core:core',
        'permissions'   => '0644',
        'path'          => '/home/core/.bash_profile',
        'content'       => $bashProfileContent
    );
            
    return $cloudConfig;
};