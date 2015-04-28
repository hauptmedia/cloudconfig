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

    $requiredFiles = array(
        $etcdCADir . '/certs/ca.crt'                             => '/etc/ssl/etcd/certs/ca.crt',
        $etcdCADir . '/certs/' .      $etcdName . "-client.crt"              => '/etc/ssl/etcd/certs/client.crt',
        $etcdCADir . '/private/' .    $etcdName . "-client.key"              => '/etc/ssl/etcd/private/client.key'
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
