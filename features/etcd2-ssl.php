<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    // merge config  node <= cluster <= defaults
    $etcd2SslConfig = array();

    if(!empty($clusterConfig['etcd2-ssl'])) {
        $etcd2SslConfig = array_merge($etcd2SslConfig, $clusterConfig['etcd2-ssl']);
    }

    if(!empty($nodeConfig['etcd2-ssl'])) {
        $etcd2SslConfig = array_merge($etcd2SslConfig, $nodeConfig['etcd2-ssl']);
    }

    // construct cloud-config.yml
    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    if (!empty($nodeConfig['etcd2']['name'])) {
        $etcdName = $nodeConfig['etcd2']['name'];
        
    } elseif (!empty($nodeConfig['hostname'])) {
        $etcdName = $nodeConfig['hostname'];
    }

    $etcdCADir = realpath( __DIR__ . '/../var/etcd-ca' );

    $serviceDefinition = 
        "[Service]\n" .
        "Environment=ETCD_PEER_CLIENT_CERT_AUTH=true\n" .
        "Environment=ETCD_PEER_CA_FILE=/etc/ssl/etcd/certs/ca.crt\n" .
        "Environment=ETCD_PEER_CERT_FILE=/etc/ssl/etcd/certs/peer.crt\n" .
        "Environment=ETCD_PEER_KEY_FILE=/etc/ssl/etcd/private/peer.key\n" .
        "Environment=ETCD_CLIENT_CERT_AUTH=true\n" .
        "Environment=ETCD_CA_FILE=/etc/ssl/etcd/certs/ca.crt\n" .
        "Environment=ETCD_CERT_FILE=/etc/ssl/etcd/certs/server.crt\n" .
        "Environment=ETCD_KEY_FILE=/etc/ssl/etcd/private/server.key\n";

    $requiredFiles = array(
        $etcdCADir . '/certs/ca.crt'                             => '/etc/ssl/etcd/certs/ca.crt',
        $etcdCADir . '/certs/' .      $etcdName . "-peer.crt"              => '/etc/ssl/etcd/certs/peer.crt',
        $etcdCADir . '/private/' .    $etcdName . "-peer.key"              => '/etc/ssl/etcd/private/peer.key',
        $etcdCADir . '/certs/' .      $etcdName . "-server.crt"              => '/etc/ssl/etcd/certs/server.crt',
        $etcdCADir . '/private/' .    $etcdName . "-server.key"              => '/etc/ssl/etcd/private/server.key',
        $etcdCADir . '/certs/' .      $etcdName . "-client.crt"              => '/etc/ssl/etcd/certs/client.crt',
        $etcdCADir . '/private/' .    $etcdName . "-client.key"              => '/etc/ssl/etcd/private/client.key'
    );
        

    $cloudConfig['write_files'][] = array(
        'path'          => '/run/systemd/system/etcd2.service.d/30-certificates.conf',
        'permissions'   => '0644',
        'content'       => $serviceDefinition
    );

    foreach ($requiredFiles as $sourceFile => $destinationFile) {
        if (!file_exists($sourceFile)) {
            throw new \Exception("Missing file " . $sourceFile);
        }

        $cloudConfig['write_files'][] = array(
            'path'          => $destinationFile,
            'permissions'   => '0644',
            'content'       => @file_get_contents($sourceFile)
        );
    }

    return $cloudConfig;
};
