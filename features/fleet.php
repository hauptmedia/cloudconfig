<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    $useSSL = in_array('etcd2-ssl', $enabledFeatures);

    if(!array_key_exists('etcd2', $cloudConfig['coreos'])) {
        throw new \Exception("etcd2 feature must be enabled before fleet");
    }

    $etcd2Endpoint   = $cloudConfig['coreos']['etcd2']['advertise-client-urls'];

    $fleetConfig = array(
        'etcd_servers' => $etcd2Endpoint
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

    if(!empty($nodeConfig['ip'])) {
        $fleetConfig['public-ip'] = $nodeConfig['ip'];
    }

    if(!empty($clusterConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $clusterConfig['fleet']);
    }

    if(!empty($nodeConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $nodeConfig['fleet']);
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
