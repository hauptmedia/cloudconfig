<?php
return function($clusterConfig, $nodeConfig) {
    $iptablesRules =
        "*filter\n" .
        ":INPUT DROP [0:0]\n" .
        ":FORWARD DROP [0:0]\n" .
        ":OUTPUT ACCEPT [0:0]\n" .
        ":Cloudconfig-Firewall-INPUT - [0:0]\n" .
        "\n" .
        "-A INPUT -j Cloudconfig-Firewall-INPUT\n" .
        "-A FORWARD -j Cloudconfig-Firewall-INPUT\n" .
        "\n" .
        "-A Cloudconfig-Firewall-INPUT -i lo -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -i docker0 -j ACCEPT\n" .
        "\n".
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type echo-request -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type echo-reply -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type destination-unreachable -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type time-exceeded -j ACCEPT\n" .
        "\n" .
        "-A Cloudconfig-Firewall-INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT\n" .
        "\n";


    if(array_key_exists('iptables', $nodeConfig) && array_key_exists('allow', $nodeConfig['iptables'])) {
        foreach($nodeConfig['iptables']['allow'] as $entry) {
            if(!array_key_exists('protocol', $entry) || !in_array($entry['protocol'], array('tcp', 'udp'))) {
                throw new \RuntimeException("Wrong protocol for iptables allow entry");
            }

            if(!array_key_exists('port', $entry) || !is_numeric($entry['port'])) {
                throw new \RuntimeException("Wrong port for iptables allow entry");

            }

            $iptablesRules .= sprintf("-A Cloudconfig-Firewall-INPUT -m conntrack --ctstate NEW -p %s --dport %u -j ACCEPT\n", $entry['protocol'], $entry['port']);
        }

        $iptablesRules .= "\n";
    }

    if(array_key_exists('ips', $clusterConfig)) {
        //allow inter node communication between all nodes using their public ips
        foreach ($clusterConfig['ips'] as $ip) {
            $iptablesRules .= "-A Cloudconfig-Firewall-INPUT -s " . $ip . " -j ACCEPT\n";
        }
    }

    //log and drop any other input
    $iptablesRules .= "-A Cloudconfig-Firewall-INPUT -j DROP\n";

    $iptablesRules .= "COMMIT\n";


    return array(
        'coreos' => array(
            'units' => array(
                array(
                    'name'      => 'iptables-restore.service',
		    'enable'    => true,
		    'command'	=> 'start'
                )
            )
        ),

        'write_files' => array(
            array(
                'path'          => '/var/lib/iptables/rules-save',
                'permissions'   => '0644',
                'owner'         => 'root:root',
                'content'       => $iptablesRules
            )
        )
    );
};
