<?php
return function($clusterConfig, $nodeConfig, $cloudConfig, $enabledFeatures) {
    // determine which features are active for this node
    $enabledFeatures = array();

    if(!empty($clusterConfig['features'])) {
        $enabledFeatures = array_merge($enabledFeatures, $clusterConfig['features']);
    }

    if(!empty($nodeConfig['features'])) {
        $enabledFeatures = array_merge($enabledFeatures, $nodeConfig['features']);
    }
    
    $useSSL = in_array('etcd-ssl', $enabledFeatures);

    if(!array_key_exists('etcd', $cloudConfig['coreos'])) {
        throw new \Exception("etcd feature must be enabled before skydns");
    }

    $etcdEndpoint   = $useSSL ?
        "https://" . $cloudConfig['coreos']['etcd']['addr'] :
        "http://" . $cloudConfig['coreos']['etcd']['addr'];
    
    
    if(!array_key_exists('ip', $nodeConfig)) {
        throw new \Exception("Missing ip in nodeConfig");
    }

    // merge config  node <= cluster <= defaults
    $skyDnsConfig = array(
        'domain'        => 'skydns.local',                      // domain for which SkyDNS is authoritative, defaults to skydns.local.
        'nameservers'   => array('8.8.8.8:53', '8.8.4.4:53'),   // forward DNS requests to these nameservers (array of IP:port combination), when not authoritative for a domain.
        'ttl'           => 60,                                // default TTL in seconds to use on replies when none is set in etcd, defaults to 3600.
        'min_ttl'       => 30                                   // minimum TTL in seconds to use on NXDOMAIN, defaults to 30.
    );

    if(!empty($nodeConfig['ip'])) {
        $skyDnsConfig['dns_addr'] = $nodeConfig['ip'] . ":53";
    } else {
        $skyDnsConfig['dns_addr'] = "127.0.0.1:53";
    }
   
    if(!empty($clusterConfig['skydns'])) {
        $skyDnsConfig = array_merge($skyDnsConfig, $clusterConfig['skydns']);
    }

    if(!empty($nodeConfig['skydns'])) {
        $skyDnsConfig = array_merge($skyDnsConfig, $nodeConfig['skydns']);
    }
    
    // construct cloud-config.yml
    if(!array_key_exists('coreos', $cloudConfig)) {
        $cloudConfig['coreos'] = array();
    }

    if(!array_key_exists('units', $cloudConfig['coreos'])) {
        $cloudConfig['coreos']['units'] = array();
    }

    if (!array_key_exists('write_files', $cloudConfig)) {
        $cloudConfig['write_files'] = array();
    }

    $cloudConfig['write_files'][] = array(
        'path'          => '/etc/skydns-options.env',
        'permissions'   => '0644',
        'content'       => 
            "ETCD_MACHINES=" . $etcdEndpoint . "\n" .                       // list of etcd machines, "http://localhost:4001,http://etcd.example.com:4001"
            "SKYDNS_ADDR=" . $skyDnsConfig['dns_addr'] . "\n" .
            "SKYDNS_DOMAIN=" . $skyDnsConfig['domain'] . "\n" .
            ($useSSL ? "ETCD_TLSKEY=/etc/ssl/etcd/private/client.key\n" : "" ) .    // path of TLS client certificate - private key
            ($useSSL ? "ETCD_TLSPEM=/etc/ssl/etcd/certs/client.crt\n" : "" ) .      // path of TLS client certificate - public key
            ($useSSL ? "ETCD_CACERT=/etc/ssl/etcd/certs/ca.crt\n" : "" )            // path of TLS certificate authority public key
    );


    

    $curlOpts = $useSSL ?
        "--cert /etc/ssl/etcd/certs/client.crt --cacert /etc/ssl/etcd/certs/ca.crt --key /etc/ssl/etcd/private/client.key" :
        "";

    list($dnsIp, $port) = explode(":", $skyDnsConfig['dns_addr']);
    $dnsDomain = $skyDnsConfig['domain'];

    unset($skyDnsConfig['dns_addr']);
    unset($skyDnsConfig['domain']);

    $cloudConfig['write_files'][] = array(
        'path'          => '/etc/skydns-config.json',
        'permissions'   => '0644',
        'content'       => json_encode(
            $skyDnsConfig, 
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        )
    );
    
    $cloudConfig['write_files'][] = array(
        'path'          => '/opt/bin/skydns-set-record',
        'permissions'   => '0755',
        'content'       => file_get_contents(
             __DIR__ . '/../bin/skydns-set-record'
        )
    );

    $dockerOpts = "--dns=\"" . $dnsIp . "\" --dns-search=\"" . $dnsDomain . "\"";

    //HACK: add option from private-repository features to dockerOpts because we're overriding the DOCKER_OPTS environment variable
    if(in_array('private-repository', $enabledFeatures)) {
        $privateRepositoryConfig = array();

        if(!empty($clusterConfig['private-repository'])) {
            $privateRepositoryConfig = array_merge($privateRepositoryConfig, $clusterConfig['private-repository']);
        }

        if(!empty($nodeConfig['private-repository'])) {
            $privateRepositoryConfig = array_merge($privateRepositoryConfig, $nodeConfig['private-repository']);
        }

        $dockerOpts .= " --insecure-registry=\"" . $privateRepositoryConfig['insecure-addr'] ."\"";
    }

    $cloudConfig['write_files'][] = array(
        'path'          => '/etc/systemd/system/docker.service.d/50-docker-opts.conf',
        'content'       =>
            "[Service]\n" .
            "Environment='DOCKER_OPTS=" . $dockerOpts . "'"
    );

    $cloudConfig['coreos']['units'][] = array(
        'name'      => 'skydns.service',
        'command'   => 'start',
        'content'   =>
            "[Unit]\n" .
            "Description=SkyDNS Service\n" .
            "Wants=etcd.service\n" .
            "After=docker.service\n" .
            "\n" .
            "[Service]\n" .
            "Restart=always\n".
            "RestartSec=5\n".
            "Environment=\"ETCD_SSL_DIR=/etc/ssl/etcd\"\n".
            "ExecStartPre=/usr/bin/mkdir -p /run/skydns\n" .
            "ExecStartPre=/bin/cp /etc/skydns-options.env /run/skydns/options.env\n" .
            "ExecStartPre=/usr/bin/curl " . $curlOpts . " -L -XPUT " . $etcdEndpoint . "/v2/keys/skydns/config --data-urlencode value@/etc/skydns-config.json\n" .
            "ExecStartPre=-/usr/bin/docker kill skydns\n" .
            "ExecStartPre=-/usr/bin/docker rm skydns\n" .
            "ExecStart=/usr/bin/docker run --net=host --privileged=true --rm --env-file=/run/skydns/options.env -v /run/skydns:/run/skydns -v \${ETCD_SSL_DIR}:/etc/ssl/etcd:ro --name skydns skynetservices/skydns\n" .
            "ExecStop=/usr/bin/docker stop skydns\n" .
            "\n" .
            "[Install]\n" .
            "WantedBy=multi-user.target\n"
    );
    
    
    
    return $cloudConfig;

};