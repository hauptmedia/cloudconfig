<?php
return function($clusterConfig, $nodeConfig) {
    if(array_key_exists('etcd2', $nodeConfig) && array_key_exists('name', $nodeConfig['etcd2'])) {
        $etcdName = $nodeConfig['etcd2']['name'];
    } elseif (array_key_exists('hostname', $nodeConfig)) {
        $etcdName = $nodeConfig['hostname'];
    }

    $etcdCADir = realpath( __DIR__ . '/../var/etcd-ca' );

    $requiredFiles = array(
        $etcdCADir . '/certs/ca.crt'                                         => '/etc/ssl/etcd/certs/ca.crt',
        $etcdCADir . '/certs/' .      $etcdName . "-client.crt"              => '/etc/ssl/etcd/certs/client.crt',
        $etcdCADir . '/private/' .    $etcdName . "-client.key"              => '/etc/ssl/etcd/private/client.key'
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

    return array(
        'write_files' => $writeFiles
    );
};
