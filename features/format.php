<?php
return function($clusterConfig, $nodeConfig) {
    $formatConfig   = $nodeConfig['format'];
    $units          = array();


    foreach($formatConfig as $formatConfigEntry) {
        if(!array_key_exists('mount-point', $formatConfigEntry)) {
            throw new \Exception("Missing mount-point in config");
        }

        $systemdName        = "format-" . substr(str_replace("/", "-", $formatConfigEntry["mount-point"]) . ".service", 1);
        $systemdMountName   = substr(str_replace("/", "-", $formatConfigEntry["mount-point"]) . ".mount", 1);


        if(!array_key_exists('type', $formatConfigEntry) || $formatConfigEntry['type'] != 'ext4') {
            throw new \Exception("Only type=ext4 supported");
        }

        $units[] = array(
            'name'      => $systemdName,
            'command'   => 'start',
            'content'   =>
                "[Unit]\n" .
                "Description=Formats a drive\n" .
                "Before=" . $systemdMountName . "\n" .
                "ConditionFirstBoot=yes\n" .
                "[Service]\n" .
                "Type=oneshot\n" .
                "RemainAfterExit=yes\n" .
                "ExecStart=/usr/sbin/wipefs -f " . $formatConfigEntry['dev'] . "\n" .
                "ExecStart=/usr/sbin/mke2fs -q -t ext4 -b 4096 -i 4096 -I 128 " . $formatConfigEntry['dev'] . "\n"
        );
    }

    return array(
        'coreos' => array(
            'units' => $units
        )
    );
};