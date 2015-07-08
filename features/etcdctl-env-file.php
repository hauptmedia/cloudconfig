<?php
return function($clusterConfig, $nodeConfig) {
    $useSSL = in_array('etcd-ssl', $nodeConfig['features']) || in_array('etcd2-ssl', $nodeConfig['features']);

    return array(
        'write_files' => array(
            array(
                'owner' => 'root:root',
                'permissions' => '0644',
                'path' => '/etc/etcdctl.env',
                'content' =>
                    "ETCDCTL_PEERS=" . (array_key_exists('etcd-peers', $clusterConfig) ? $clusterConfig['etcd-peers'] : "") . "\n" .
                    ($useSSL ? "ETCDCTL_CA_FILE=/etc/ssl/etcd/certs/ca.crt\n" : "") .
                    ($useSSL ? "ETCDCTL_KEY_FILE=/etc/ssl/etcd/private/client.key\n" : "") .
                    ($useSSL ? "ETCDCTL_CERT_FILE=/etc/ssl/etcd/certs/client.crt\n" : "")
            )
        )
    );
};