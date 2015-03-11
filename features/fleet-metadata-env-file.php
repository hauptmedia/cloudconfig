<?php
/**
 * This feature writes the fleet metadata as an env file in /etc/fleet-metadata.env
 *
 * The env file can be used to pass the fleet metadata as environment variables in docker containers
 * with the --env-file=/etc/fleet-metadata.env docker command line option or in systemd service definitions
 * using the EnvironmentFile=/etc/fleet-metadata.env configuration option
 */
return function($clusterConfig, $nodeConfig, $cloudConfig) {
    $fleetConfig = array();
    
    if(!empty($clusterConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $clusterConfig['fleet']);
    }

    if(!empty($nodeConfig['fleet'])) {
        $fleetConfig = array_merge($fleetConfig, $nodeConfig['fleet']);
    }

    if(!empty($fleetConfig["metadata"])) {
        if (!array_key_exists('write_files', $cloudConfig)) {
            $cloudConfig['write_files'] = array();
        }
        
        $envFileContents = "";
        
        $metaData = $fleetConfig["metadata"];
        $metaDataEntries = explode(",", $metaData);
        
        foreach($metaDataEntries as $metaDataEntry) {
            list($key, $value) = explode("=", $metaDataEntry);
            
            $envFileContents .= strtoupper($key) . "=".$value."\n";
        }

        $cloudConfig['write_files'][] = array(
            'path'          => '/etc/fleet-metadata.env',
            'owner'         => 'root:root',
            'permissions'   => '0644',
            'content'       => $envFileContents
        );
    }
    
    return $cloudConfig;

};