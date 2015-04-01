<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeaturesr) {
    // determine which features are active for this node
    $enabledFeatures = array();

    if(!empty($clusterConfig['features'])) {
        $enabledFeatures = array_merge($enabledFeatures, $clusterConfig['features']);
    }

    if(!empty($nodeConfig['features'])) {
        $enabledFeatures = array_merge($enabledFeatures, $nodeConfig['features']);
    }


    // merge config  node <= cluster <= defaults
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
        'path'          => '/etc/fleetctl.env',
        'content'       => $fleetctlEnvFileContent
    );

    return $cloudConfig;

};