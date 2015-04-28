<?php
return function($clusterConfig, $nodeConfig) {
    $mountConfig = $nodeConfig['mount'];
    $units = array();

    foreach($mountConfig as $mountConfigEntry) {
        $systemdName = substr(
            str_replace("/", "-", $mountConfigEntry["mount-point"]) . ".mount",
            1
        );

        $units[] = array(
            'name'      => $systemdName,
            'command'   => 'start',
            'content'   =>
                "[Unit]\n" .
                "Before=docker.service\n" .
                "[Mount]\n" .
                "What=" . $mountConfigEntry['dev'] . "\n" .
                "Where=" . $mountConfigEntry['mount-point'] . "\n" .
                "Type=" . $mountConfigEntry['type'] . "\n"
        );
    }

    return array(
        'coreos' => array(
            'units' => $units
        )
    );
};