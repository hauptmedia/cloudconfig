<?php
return function($clusterConfig, $nodeConfig) {
    if(!array_key_exists('hostname', $nodeConfig)) {
        throw new \Exception("Missing hostname in nodeConfig");
    }

    return array(
        'coreos' => array(
            'units' => array(
                array(
                    'name'      => 'iptables-restore.service',
                    'enable'    => true
                )
            )
        ),

        'write_files' => array(
            array(
                'path'          => '/var/lib/iptables/rules-save',
                'permissions'   => '0644',
                'owner'         => 'root:root',
                'content'       =>
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
                    "\n".
                    // Log and drop everything else
                    "-A Cloudconfig-Firewall-INPUT -j LOG\n" .
                    "-A Cloudconfig-Firewall-INPUT -j REJECT --reject-with icmp-host-prohibited\n" .
                    "COMMIT"
            )
        )
    );
};