<?php
return function($clusterConfig, $nodeConfig) {
    //TODO: if no ip was specified via cloud config it must be set via a system service upon startup

    $public_ipv4    = array_key_exists('ip', $nodeConfig)       ? $nodeConfig['ip']         : "\$public_ipv4";
    $hostname       = array_key_exists('hostname', $nodeConfig) ? $nodeConfig['hostname']   : "";

    $envFileContents  = "PUBLIC_IPV4=" . $public_ipv4 . "\n";
    $envFileContents .= "HOSTNAME=" . $hostname . "\n";

    return array(
        'write_files' => array(
            array(
                'path'          => '/etc/host.env',
                'owner'         => 'root:root',
                'permissions'   => '0644',
                'content'       => $envFileContents
            ),
            array(
                'path'          => '/opt/bin/getip',
                'permissions'   => '0755',
                'content'       => file_get_contents(
                    __DIR__ . '/../bin/getip'
                )
            )
        )
    );
};