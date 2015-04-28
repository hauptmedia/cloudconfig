<?php
return function($clusterConfig, $nodeConfig) {
    $useSSL             = in_array('etcd2-ssl', $nodeConfig['features']);
    $etcdEndpoint       = explode(",", $clusterConfig['etcd-peers'])[0];

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
    
    $curlOpts = $useSSL ? 
        "--cert /etc/ssl/etcd/certs/client.crt --cacert /etc/ssl/etcd/certs/ca.crt --key /etc/ssl/etcd/private/client.key" : 
        "";


    return array(
        'coreos' => array(
            'flannel' => $flannelConfig,

            'units' => array(
                array(
                    'name'      => 'flanneld.service',
                    'drop-ins' => array(
                        array(
                            'name'      => '50-network-config.conf',
                            'content'   =>
                                "[Service]\n" .
                                "ExecStartPre=/usr/bin/curl " . $curlOpts .
                                " -L -XPUT " . $etcdEndpoint . "/v2/keys" .
                                $flannelConfig['etcd_prefix'] .
                                "/config --data-urlencode value@/etc/flannel-network-config.json\n"

                        )),
                    'command'   => 'start'
                )
            )
        ),

        'write_files' => array(
            array(
                'path'          => '/etc/flannel-network-config.json',
                'permissions'   => '0644',
                'content'       => json_encode($flannelJsonConfig, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            )
        )
    );

};