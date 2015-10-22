<?php
/**
 * Configure networkd to use static ip addresses
 * https://coreos.com/os/docs/latest/network-config-with-networkd.html
 */
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('static-network', $nodeConfig)) {
        return;
    }

    $staticNetworkConfiguration = $nodeConfig['static-network'];

    if(!is_array($staticNetworkConfiguration)) {
        return;
    }

    $units = array();

    $units[] = array(
        'name' => 'systemd-networkd.service',
        'command' => 'stop'
    );

    foreach($staticNetworkConfiguration as $entry) {
        $i = 0;

        $unit = array(
            'name'      => sprintf('%02d-%s.network', $i, $entry['iface']),
            'runtime'   => true,
            'content'   =>
                "[Match]\n" .
                "Name=" . $entry['iface'] . "\n" .
                "\n" .
                "[Network]\n" .
                "DNS=" . $entry['dns'] . "\n" .
                "Address=" . $entry['address'] . "\n".
                "Gateway=" . $entry['gateway'] . "\n"
        );

        $units[] = $unit;

        $unit2 = array(
            'name'      => "down-interface-" . $entry['iface' ] . ".service",
            'command'   => "start",
            'content'   =>
                "[Service]\n" .
                "Type=oneshot\n" .
                "ExecStart=/usr/bin/ip link set " . $entry['iface'] .  " down\n" .
                "ExecStart=/usr/bin/ip addr flush dev " . $entry['iface'] . "\n"
        );

        $units[] = $unit2;

        $i++;
    }

    $units[] =  array(
        "name" => "systemd-networkd.service",
        "command" => "restart"
    );

    return array(
        'coreos' => array(
            'units' => $units
        )
    );
};