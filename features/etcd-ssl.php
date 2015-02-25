<?php
return function($clusterConfig, $nodeConfig, $cloudConfig) {

    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    $etcdName =
        !empty($nodeConfig['etcd']['name']) ?
            $nodeConfig['etcd']['name'] :
            $nodeConfig['hostname'];


    $cloudConfig['write_files'][] = array(
        'path' => '/run/systemd/system/etcd.service.d/30-certificates.conf',
        'permissions' => '0644',
        'content' => '
[Service]
# Client Env Vars
Environment=ETCD_CA_FILE=/etc/ssl/etcd/certs/etcd-ca.crt
Environment=ETCD_CERT_FILE=/etc/ssl/etcd/certs/client.crt
Environment=ETCD_KEY_FILE=/etc/ssl/etcd/private/client.key
# Peer Env Vars
Environment=ETCD_PEER_CA_FILE=/etc/ssl/etcd/certs/etcd-ca.crt
Environment=ETCD_PEER_CERT_FILE=/etc/ssl/etcd/certs/server.crt
Environment=ETCD_PEER_KEY_FILE=/etc/ssl/etcd/private/server.key'
    );


    $etcdCADir = realpath( __DIR__ . '/../var/etcd-ca' );
    
    $requiredFiles = array(
        $etcdCADir . '/certs/etcd-ca.crt'                             => '/etc/ssl/etcd/certs/etcd-ca.crt',
        
        $etcdCADir . '/certs/' .      $etcdName . ".crt"              => '/etc/ssl/etcd/certs/server.crt',
        $etcdCADir . '/private/' .    $etcdName . ".key"              => '/etc/ssl/etcd/private/server.key',
        
        $etcdCADir . '/certs/' .      $etcdName . "-client.crt"       => '/etc/ssl/etcd/certs/client.crt',
        $etcdCADir . '/private/' .    $etcdName . "-client.key"       => '/etc/ssl/etcd/private/client.key',
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