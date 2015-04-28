<?php
return function($clusterConfig, $nodeConfig) {
    $bashProfileContent  =
        "[[ -f ~/.bashrc ]] && . ~/.bashrc\n" .
        "\n" .
        "if [ -f /etc/etcdctl.env ]; then\n".
        "  . /etc/etcdctl.env\n" .
        "fi\n" .
        "if [ -z \"\$SSH_AUTH_SOCK\" ] && [ -e /tmp/ssh-agent.sock ]; then\n" .
        "  export SSH_AUTH_SOCK=/tmp/ssh-agent.sock\n" .
        "fi\n";

    return array(
        'write_files' => array(
            array(
                'owner'         => 'core:core',
                'permissions'   => '0644',
                'path'          => '/home/core/.bash_profile',
                'content'       => $bashProfileContent
            )
        )
    );
};