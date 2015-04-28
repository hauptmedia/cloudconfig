<?php
return function($clusterConfig, $nodeConfig) {
    $useSSL             = in_array('etcd2-client-ssl', $nodeConfig['features']);
    $fleetConfig        = array_key_exists('fleet', $nodeConfig) ? $nodeConfig['fleet'] : array();

    if(!array_key_exists('etcd_servers', $fleetConfig)) {
        $fleetConfig['etcd_servers'] = $clusterConfig['etcd-peers'];
    }


    if(!array_key_exists('public-ip', $fleetConfig) && array_key_exists('ip', $nodeConfig)) {
        $fleetConfig['public-ip'] = $nodeConfig['ip'];
    }

    if($useSSL) {
        $fleetConfig['etcd_cafile']         = "/etc/ssl/etcd/certs/ca.crt";
        $fleetConfig['etcd_keyfile']        = "/etc/ssl/etcd/private/client.key";
        $fleetConfig['etcd_certfile']       = "/etc/ssl/etcd/certs/client.crt";
    }

    $writeFiles = array();

    if(!empty($fleetConfig["metadata"])) {
        $fleetmetaEnvFileContent = "";

        $metaDataEntries = explode(",", $fleetConfig["metadata"]);

        foreach ($metaDataEntries as $metaDataEntry) {
            list($key, $value) = explode("=", $metaDataEntry);

            $fleetmetaEnvFileContent .= strtoupper($key) . "=" . $value . "\n";
        }

        $writeFiles[] = array(
            'owner'         => 'root:root',
            'permissions'   => '0644',
            'path'          => '/etc/fleet-metadata.env',
            'content'       => $fleetmetaEnvFileContent
        );
    }

    return array(
        'coreos' => array(
            'fleet' => $fleetConfig,
            'units' => array(
                array(
                    'name'      => 'fleet.service',
                    'command'   => 'start'
                )
            )

        ),
        'write_files' => $writeFiles
    );

};
