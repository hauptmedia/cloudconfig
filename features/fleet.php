<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $useSSL = in_array('etcd-ssl', $enabledFeatures);

    if(!array_key_exists('etcd', $cloudConfig['coreos'])) {
        throw new \Exception("etcd feature must be enabled before fleet");
    }

    $etcdEndpoint   = $useSSL ?
        "https://" . $cloudConfig['coreos']['etcd']['addr'] :
        "http://" . $cloudConfig['coreos']['etcd']['addr'];

    $fleetConfig = array(
        'etcd_servers' => $etcdEndpoint
    );

    if(!empty($clusterConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $clusterConfig['fleet']);
    }

    if(!empty($nodeConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $nodeConfig['fleet']);
    }

    if($useSSL) {
        $fleetConfig['etcd_cafile']         = "/etc/ssl/etcd/certs/ca.crt";
        $fleetConfig['etcd_keyfile']        = "/etc/ssl/etcd/private/client.key";
        $fleetConfig['etcd_certfile']       = "/etc/ssl/etcd/certs/client.crt";
    }

    $fleetctlEnvFileContent = "FLEETCTL_ENDPOINT=" . $fleetConfig['etcd_servers'] . "\n";

    if($useSSL) {
        $fleetctlEnvFileContent .=
            "FLEETCTL_CERT_FILE="   . $fleetConfig['etcd_certfile'] . "\n" .
            "FLEETCTL_KEY_FILE="    . $fleetConfig['etcd_keyfile']  . "\n" .
            "FLEETCTL_CA_FILE="     . $fleetConfig['etcd_cafile']   . "\n";
    }

    if(!empty($nodeConfig['ip'])) {
        $fleetConfig['public-ip'] = $nodeConfig['ip'];
    }

    if(!empty($clusterConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $clusterConfig['fleet']);
    }

    if(!empty($nodeConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $nodeConfig['fleet']);
    }

    if(array_key_exists('strict_host_key_checking', $fleetConfig)) {
        $fleetctlEnvFileContent .= "FLEETCTL_STRICT_HOST_KEY_CHECKING=" . ( $fleetConfig['strict_host_key_checking'] ? 'true' : 'false' ) . "\n";
        unset($fleetConfig['strict_host_key_checking']);
    }

    // construct cloud-config.yml
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }

    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'fleet.service',
        'command'   => 'start'
    );

    $cloudConfig['coreos']['fleet'] = $fleetConfig;


    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    $cloudConfig['write_files'][] = array(
        'owner'         => 'root:root',
        'permissions'   => '0644',
        'path'          => '/etc/fleetctl.env',
        'content'       => $fleetctlEnvFileContent
    );

    $fleetmetaEnvFileContent = "";

    if(!empty($fleetConfig["metadata"])) {
        $metaDataEntries = explode(",", $fleetConfig["metadata"]);

        foreach ($metaDataEntries as $metaDataEntry) {
            list($key, $value) = explode("=", $metaDataEntry);

            $fleetmetaEnvFileContent .= strtoupper($key) . "=" . $value . "\n";
        }

        $cloudConfig['write_files'][] = array(
            'owner'         => 'root:root',
            'permissions'   => '0644',
            'path'          => '/etc/fleet-metadata.env',
            'content'       => $fleetmetaEnvFileContent
        );
    }

    return $cloudConfig;

};
