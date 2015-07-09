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
        "\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type echo-request -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type echo-reply -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type destination-unreachable -j ACCEPT\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m icmp --icmp-type time-exceeded -j ACCEPT\n" .
        "\n" .
        "-A Cloudconfig-Firewall-INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT\n" .
        "\n".
        // accept ssh, http, https
        "-A Cloudconfig-Firewall-INPUT -m conntrack --ctstate NEW -m multiport -p tcp --dports 22,80,443 -j ACCEPT\n" .
        "\n";


    if(array_key_exists('ips', $clusterConfig)) {
        //allow inter node communication between all nodes using their public ips
        foreach ($clusterConfig['ips'] as $ip) {
            $iptablesRules .= "-A Cloudconfig-Firewall-INPUT -s " . $ip . " -j ACCEPT\n";
        }
    }

    //log and drop any other input
    $iptablesRules .=
        "-A Cloudconfig-Firewall-INPUT -p tcp -m limit --limit 5/min -j LOG --log-prefix \"Denied TCP: \" --log-level 7\n" .
        "-A Cloudconfig-Firewall-INPUT -p udp -m limit --limit 5/min -j LOG --log-prefix \"Denied UDP: \" --log-level 7\n" .
        "-A Cloudconfig-Firewall-INPUT -p icmp -m limit --limit 5/min -j LOG --log-prefix \"Denied ICMP: \" --log-level 7\n" .
        "-A Cloudconfig-Firewall-INPUT -j DROP\n";

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
