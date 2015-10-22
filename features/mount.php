<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('mount', $nodeConfig)) {
        return;
    }

    $mountConfig = $nodeConfig['mount'];
    $units = array();

    foreach($mountConfig as $mountConfigEntry) {
        if(!array_key_exists('mount-point', $mountConfigEntry)) {
            throw new \Exception("Missing mount-point in config");
        }

        if(!array_key_exists('dev', $mountConfigEntry)) {
            throw new \Exception("Missing dev in config");
        }

        if(!array_key_exists('type', $mountConfigEntry) || $mountConfigEntry['type'] != 'ext4') {
            throw new \Exception("Only type=ext4 supported in mount feature");
        }

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


        if(array_key_exists('format', $mountConfigEntry) && $mountConfigEntry['format']) {
            $units[] = array(
                'name'      => "format-" . substr(str_replace("/", "-", $mountConfigEntry["mount-point"]) . ".service", 1),
                'command'   => 'start',
                'content'   =>
                    "[Unit]\n" .
                    "Description=Formats a drive\n" .
                    "Before=local-fs-pre.target local-fs.target\n" .
                    "Wants=local-fs-pre.target\n" .
                    "ConditionFirstBoot=yes\n" .
                    "[Service]\n" .
                    "Type=oneshot\n" .
                    "RemainAfterExit=yes\n" .
                    "ExecStart=/usr/sbin/wipefs -f " . $mountConfigEntry['dev'] . "\n" .
                    "ExecStart=/usr/sbin/mke2fs -q -t ext4 -b 4096 -i 4096 -I 128 " . $mountConfigEntry['dev'] . "\n"
            );
        }
    }

    return array(
        'coreos' => array(
            'units' => $units
        )
    );
};