<?php
return function($clusterConfig, $nodeConfig) {
    $useSSL         = in_array('etcd2-ssl', $nodeConfig['features']);
    $etcd2Config    = $nodeConfig['etcd2'];

    //try to auto generate etcd2 configuration if no configuration was specified
    if(!array_key_exists('name', $etcd2Config) && array_key_exists('hostname', $nodeConfig)) {
        $etcd2Config['name'] = $nodeConfig['hostname'];
    }

    if(array_key_exists('ip', $nodeConfig)) {
        if(!array_key_exists('advertise-client-urls', $etcd2Config)) {
            $etcd2Config['advertise-client-urls']       = ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2379';
        }

        if(!array_key_exists('listen-client-urls', $etcd2Config)) {
            $etcd2Config['listen-client-urls']          = ($useSSL ? 'https://' : 'http://') . '127.0.0.1:2379,' . ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2379';
        }

        if(!array_key_exists('initial-advertise-peer-urls', $etcd2Config)) {
            $etcd2Config['initial-advertise-peer-urls'] = ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2380';
        }

        if(!array_key_exists('listen-peer-urls', $etcd2Config)) {
            $etcd2Config['listen-peer-urls']            = ($useSSL ? 'https://' : 'http://') . '127.0.0.1:2380,' . ($useSSL ? 'https://' : 'http://') . $nodeConfig['ip'] . ':2380';
        }
    }

    return array(
        'coreos' => array(
            'etcd2' => $etcd2Config,

            'units' => array(
                array(
                    'name'      => 'etcd2.service',
                    'command'   => 'start'
                )
            )
        ),

        'write_files' => array(
            array(
                'owner'         => 'root:root',
                'permissions'   => '0644',
                'path'          => '/etc/etcdctl.env',
                'content'       =>
                    "ETCDCTL_PEERS=" . ( array_key_exists('etcd-peers', $clusterConfig) ? $clusterConfig['etcd-peers'] : "" ). "\n" .
                    ($useSSL ? "ETCDCTL_CA_FILE=/etc/ssl/etcd/certs/ca.crt\n" : "") .
                    ($useSSL ? "ETCDCTL_KEY_FILE=/etc/ssl/etcd/private/client.key\n" : "") .
                    ($useSSL ? "ETCDCTL_CERT_FILE=/etc/ssl/etcd/certs/client.crt\n" : "")
            )
        )
    );
};