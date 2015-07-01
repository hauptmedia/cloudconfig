<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('etcd', $nodeConfig)) {
        throw new \Exception('missing etcd in nodeConfig');
    }

    $etcdConfig    = $nodeConfig['etcd'];

    if(!array_key_exists('name', $etcdConfig) && array_key_exists('hostname', $nodeConfig)) {
        $etcdConfig['name'] = $nodeConfig['hostname'];
    }

    if(array_key_exists('ip', $nodeConfig)) {
        if(!array_key_exists('addr', $etcdConfig)) {
            $etcdConfig['addr'] = $nodeConfig['ip'] . ':2379';
        }

        if(!array_key_exists('peer-addr', $etcdConfig)) {
            $etcdConfig['peer-addr'] = $nodeConfig['ip'] . ':2380';
        }
    }

    return array(
        'coreos' => array(
            'etcd' => $etcdConfig,
            'units' => array(
                array(
                    'name'      => 'etcd.service',
                    'command'   => 'start'
                )
            )
        )
    );
};