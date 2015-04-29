<?php
return function($clusterConfig, $nodeConfig) {
    $useSSL                     = in_array('etcd-client-ssl', $nodeConfig['features']);
    $privateRepositoryConfig    = $nodeConfig['private-repository'];
    $skyDnsConfig               = $nodeConfig['skydns'];

    if(!array_key_exists('ip', $nodeConfig)) {
        throw new \Exception("Missing ip in nodeConfig");
    }

    if(!array_key_exists('domain', $skyDnsConfig)) {
        $skyDnsConfig['domain'] = 'skydns.local';
    }

    if(!array_key_exists('nameservers', $skyDnsConfig)) {
        $skyDnsConfig['nameservers'] = array('8.8.8.8:53', '8.8.4.4:53');
    }

    if(!array_key_exists('ttl', $skyDnsConfig)) {
        $skyDnsConfig['ttl'] = 60;
    }

    if(!array_key_exists('min_ttl', $skyDnsConfig)) {
        $skyDnsConfig['min_ttl'] = 30;
    }

    if(!array_key_exists('dns_addr', $skyDnsConfig)) {
        $skyDnsConfig['dns_addr'] = array_key_exists('ip', $nodeConfig) ? $nodeConfig['ip'] . ":53" : "127.0.0.1:53";
    }

    $curlOpts = $useSSL ?
        "--cert /etc/ssl/etcd/certs/client.crt --cacert /etc/ssl/etcd/certs/ca.crt --key /etc/ssl/etcd/private/client.key" :
        "";

    list($dnsIp, $dnsPort) = explode(":", $skyDnsConfig['dns_addr']);
    $dnsDomain = $skyDnsConfig['domain'];

    unset($skyDnsConfig['dns_addr']);
    unset($skyDnsConfig['domain']);

    $dockerOpts = "--dns=\"" . $dnsIp . "\" --dns-search=\"" . $dnsDomain . "\"";

    //HACK: add option from private-repository features to dockerOpts because we're overriding the DOCKER_OPTS environment variable
    if(in_array('private-repository', $nodeConfig['features'])) {
        $dockerOpts .= " --insecure-registry=\"" . $privateRepositoryConfig['insecure-addr'] ."\"";
    }

    return array(
        'write_files' => array(
            array(
                'path'          => '/opt/bin/skydns-set-record',
                'permissions'   => '0755',
                'content'       => file_get_contents(
                    __DIR__ . '/../bin/skydns-set-record'
                )
            ),
            array(
                'path'          => '/opt/bin/skydns-rm-record',
                'permissions'   => '0755',
                'content'       => file_get_contents(
                    __DIR__ . '/../bin/skydns-rm-record'
                )
            ),
            array(
                'path'          => '/etc/skydns-options.env',
                'permissions'   => '0644',
                'content'       =>
                    "ETCD_MACHINES="    . $clusterConfig['etcd-peers'] . "\n" .
                    "SKYDNS_ADDR="      . $dnsIp . ":" . $dnsPort . "\n" .
                    "SKYDNS_DOMAIN="    . $dnsDomain . "\n" .
                    ($useSSL ? "ETCD_TLSKEY=/etc/ssl/etcd/private/client.key\n" : "" ) .    // path of TLS client certificate - private key
                    ($useSSL ? "ETCD_TLSPEM=/etc/ssl/etcd/certs/client.crt\n" : "" ) .      // path of TLS client certificate - public key
                    ($useSSL ? "ETCD_CACERT=/etc/ssl/etcd/certs/ca.crt\n" : "" )            // path of TLS certificate authority public key
            ),

            array(
                'path'          => '/etc/systemd/system/docker.service.d/50-docker-opts.conf',
                'content'       =>
                    "[Service]\n" .
                    "Environment='DOCKER_OPTS=" . $dockerOpts . "'"
            )
        ),

        'coreos' => array(
            'units' => array(
                array(
                    'name'      => 'skydns-config.service',
                    'command'   => 'start',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=Set the skydns config in etcd\n" .
                        "\n" .
                        "[Service]\n" .
                        "Type=oneshot\n" .
                        "RemainAfterExit=yes\n".
                        "EnvironmentFile=/etc/etcdctl.env\n".
                        "ExecStart=/usr/bin/etcdctl set /skydns/config '". json_encode($skyDnsConfig, JSON_UNESCAPED_SLASHES ) ."'\n" .
                        "\n" .
                        "[Install]\n".
                        "WantedBy=multi-user.target"
                ),

                array(
                    'name'      => 'skydns.service',
                    'command'   => 'start',
                    'content'   =>
                        "[Unit]\n" .
                        "Description=SkyDNS Service\n" .
                        "After=docker.service\n" .
                        "\n" .
                        "[Service]\n" .
                        "Restart=always\n".
                        "RestartSec=5\n".
                        "Environment=\"ETCD_SSL_DIR=/etc/ssl/etcd\"\n".
                        "ExecStartPre=/usr/bin/mkdir -p /run/skydns\n" .
                        "ExecStartPre=/bin/cp /etc/skydns-options.env /run/skydns/options.env\n" .
                        "ExecStartPre=-/usr/bin/docker kill skydns\n" .
                        "ExecStartPre=-/usr/bin/docker rm skydns\n" .
                        "ExecStart=/usr/bin/docker run --net=host --privileged=true --rm --env-file=/run/skydns/options.env -v /run/skydns:/run/skydns -v \${ETCD_SSL_DIR}:/etc/ssl/etcd:ro --name skydns skynetservices/skydns\n" .
                        "ExecStop=/usr/bin/docker stop skydns\n" .
                        "\n" .
                        "[Install]\n" .
                        "WantedBy=multi-user.target\n"
                )
            )

        )
    );
};