<?php
return function($clusterConfig, $nodeConfig) {
    $useSSL             = in_array('etcd2-ssl', $nodeConfig['features']);

    $flannelConfig = array(
        'etcd_prefix'       => '/coreos.com/network',
        'etcd_endpoints'    => $clusterConfig['etcd-peers'],
        
        'network'           => '10.0.0.0/8',
        'subnet_len'        => 24,
        'subnet_min'        => '10.0.0.0',
        'subnet_max'        => '10.255.255.255',
    
        'backend_type'      => 'vxlan'
    );

    if($useSSL) {
        $flannelConfig['etcd_cafile']         = "/etc/ssl/etcd/certs/ca.crt";
        $flannelConfig['etcd_keyfile']        = "/etc/ssl/etcd/private/client.key";
        $flannelConfig['etcd_certfile']       = "/etc/ssl/etcd/certs/client.crt";
    }

    if(!empty($clusterConfig['flannel'])) {
        $flannelConfig = array_merge($flannelConfig, $clusterConfig['flannel']);
    }

    if(!empty($nodeConfig['flannel'])) {
        $flannelConfig = array_merge($flannelConfig, $nodeConfig['flannel']);
    }

    //extract JSON Config from $flannelConfig
    $flannelJsonConfig = array(
        "Network"       => $flannelConfig['network'],
        "SubnetLen"     => $flannelConfig['subnet_len'],
        "SubnetMin"     => $flannelConfig['subnet_min'],
        "SubnetMax"     => $flannelConfig['subnet_max']
    );
    
    if($flannelConfig['backend_type'] == 'vxlan') {
        $flannelJsonConfig["Backend"] = array(
            "Type"  => "vxlan"
        );
    } elseif($flannelConfig['backend_type'] == 'udp') {
        $flannelJsonConfig["Backend"] = array(
            "Type"  => "udp"
        );
    }
    
    unset($flannelConfig['network']);
    unset($flannelConfig['subnet_len']);
    unset($flannelConfig['subnet_min']);
    unset($flannelConfig['subnet_max']);
    unset($flannelConfig['backend_type']);

    return array(
        'coreos' => array(
            'flannel' => $flannelConfig,

            'units' => array(
                array(
                    'name'      => 'flanneld-config.service',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=Set the flannel config in etcd\n" .
                        "\n" .
                        "[Service]\n" .
                        "Type=oneshot\n" .
                        "RemainAfterExit=yes\n".
                        "EnvironmentFile=/etc/etcdctl.env\n".
                        "ExecStart=/usr/bin/etcdctl " . $flannelConfig['etcd_prefix'] . "'". json_encode($flannelJsonConfig, JSON_UNESCAPED_SLASHES ) ."'\n"
                ),
                array(
                    'name'      => 'flanneld.service',
                    'command'   => 'start'
                )
            )
        )
    );

};