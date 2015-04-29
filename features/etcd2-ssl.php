<?php
return function($clusterConfig, $nodeConfig) {
    if (!array_key_exists('hostname', $nodeConfig)) {
        throw new \Exception("Missing hostname");
    }

    $etcdCADir = realpath( __DIR__ . '/../var/etcd-ca' );

    $requiredFiles = array(
        $etcdCADir . '/certs/ca.crt'                             => '/etc/ssl/etcd/certs/ca.crt',
        $etcdCADir . '/certs/' .      $nodeConfig['hostname'] . "-peer.crt"              => '/etc/ssl/etcd/certs/peer.crt',
        $etcdCADir . '/private/' .    $nodeConfig['hostname'] . "-peer.key"              => '/etc/ssl/etcd/private/peer.key',
        $etcdCADir . '/certs/' .      $nodeConfig['hostname'] . "-server.crt"              => '/etc/ssl/etcd/certs/server.crt',
        $etcdCADir . '/private/' .    $nodeConfig['hostname'] . "-server.key"              => '/etc/ssl/etcd/private/server.key'
    );


    $writeFiles = array();

    foreach ($requiredFiles as $sourceFile => $destinationFile) {
        if (!file_exists($sourceFile)) {
            throw new \Exception("Missing file " . $sourceFile);
        }

        $writeFiles[] = array(
            'path'          => $destinationFile,
            'permissions'   => '0644',
            'content'       => @file_get_contents($sourceFile)
        );
    }

    $writeFiles[] = array(
        'path'          => '/run/systemd/system/etcd2.service.d/30-certificates.conf',
        'permissions'   => '0644',
        'content'       =>
            "[Service]\n" .
            "Environment=ETCD_PEER_CLIENT_CERT_AUTH=true\n" .
            "Environment=ETCD_PEER_CA_FILE=/etc/ssl/etcd/certs/ca.crt\n" .
            "Environment=ETCD_PEER_CERT_FILE=/etc/ssl/etcd/certs/peer.crt\n" .
            "Environment=ETCD_PEER_KEY_FILE=/etc/ssl/etcd/private/peer.key\n" .
            "Environment=ETCD_CLIENT_CERT_AUTH=true\n" .
            "Environment=ETCD_CA_FILE=/etc/ssl/etcd/certs/ca.crt\n" .
            "Environment=ETCD_CERT_FILE=/etc/ssl/etcd/certs/server.crt\n" .
            "Environment=ETCD_KEY_FILE=/etc/ssl/etcd/private/server.key\n"
    );

    return array(
        'write_files' => $writeFiles
    );
};
