<?php
return function($clusterConfig, $nodeConfig, $cloudConfig) {
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

    $flannelConfig = array();


    if(!array_key_exists('etcd', $cloudConfig['coreos'])) {
        throw new \Exception("etcd feature must be enabled before flannel");
    }

    if($useSSL) {
        $flannelConfig['etcd_endpoints']      = "https://" . $cloudConfig['coreos']['etcd']['addr'];
        $flannelConfig['etcd_cafile']         = "/etc/ssl/etcd/certs/ca.crt";
        $flannelConfig['etcd_keyfile']        = "/etc/ssl/etcd/private/client.key";
        $flannelConfig['etcd_certfile']       = "/etc/ssl/etcd/certs/client.crt";
    } else {
       $flannelConfig['etcd_endpoints']        = "http://" . $cloudConfig['coreos']['etcd']['addr'];
    }

    if(!empty($clusterConfig['flannel'])) {
        $flannelConfig = array_merge($flannelConfig, $clusterConfig['flannel']);
    }

    if(!empty($nodeConfig['flannel'])) {
        $flannelConfig = array_merge($flannelConfig, $nodeConfig['flannel']);
    }

    // construct cloud-config.yml
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }

    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'flanneld.service',
        'drop-ins' => array(
            array(
            'name'      => '50-network-config.conf',
            'content'   => 
                "[Service]\n" .
                "ExecStartPre=/usr/bin/etcdctl set /coreos.com/network/config '{ \"Network\": \"10.1.0.0/16\" }'\n"
         )),
        'command'   => 'start'
    );

    $cloudConfig['coreos']['flannel'] = $flannelConfig;

    return $cloudConfig;

};


/*
 * {
    "Network": "10.1.0.0/16",
    "SubnetLen": 28,
    "SubnetMin": "10.1.10.0",
    "SubnetMax": "10.1.50.0"
}
 */