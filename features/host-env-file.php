<?php
/**
 * This feature writes the fleet metadata as an env file in /etc/host.env
 *
 * The env file can be used to pass the fleet metadata as environment variables in docker containers
 * with the --env-file=/etc/host.env docker command line option or in systemd service definitions
 * using the EnvironmentFile=/etc/host.env configuration option
 */
return function($clusterConfig, $nodeConfig, $cloudConfig) {
    $fleetConfig = array();
    
    if(!empty($clusterConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $clusterConfig['fleet']);
    }

    if(!empty($nodeConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $nodeConfig['fleet']);
    }

    //TODO: if no ip was specified via cloud config it must be set via a system service upon startup
    $public_ipv4 = "\$public_ipv4";

    if (array_key_exists('ip', $nodeConfig)) {
        $public_ipv4 = $nodeConfig['ip'];
    }

    if(!empty($fleetConfig["metadata"])) {
        if (!array_key_exists('write_files', $cloudConfig)) {
            $cloudConfig['write_files'] = array();
        }
        
        $envFileContents = "PUBLIC_IPV4=" . $public_ipv4 . "\n";
        
        $metaData = $fleetConfig["metadata"];
        $metaDataEntries = explode(",", $metaData);
        
        foreach($metaDataEntries as $metaDataEntry) {
            list($key, $value) = explode("=", $metaDataEntry);
            
            $envFileContents .= "FLEET_" . strtoupper($key) . "=".$value."\n";
        }


        $cloudConfig['write_files'][] = array(
            'path'          => '/etc/host.env',
            'owner'         => 'root:root',
            'permissions'   => '0644',
            'content'       => $envFileContents
        );

        $cloudConfig['write_files'][] = array(
            'path'          => '/opt/bin/getip',
            'permissions'   => '0755',
            'content'       => file_get_contents(
                __DIR__ . '/../bin/getip'
            )
        );
    }
    
    return $cloudConfig;

};