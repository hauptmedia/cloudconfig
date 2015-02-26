<?php
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

error_reporting(E_ALL);
ini_set('display_errors', 1);
$yaml = new Parser();

try {
    $fileContents = @file_get_contents('../var/cluster-config.yml');
    
    if(!$fileContents) {
        throw new \Exception("Could not find var/cluster-config.yml");
    }
    
	$yamlContent = $yaml->parse($fileContents);

	if(!array_key_exists('mac', $_GET)) {
		throw new \Exception("Missing mac");
	}

	$mac = $_GET['mac'];

	$clusterConfig = $yamlContent['cluster'];

	$nodes = array_values(array_filter($clusterConfig['nodes'], function($entry) use ($mac) {
		return $entry['mac'] == $mac;
	}));

	if(count($nodes) != 1) {
	        header('HTTP/1.1 404 Not Found');
        	exit;
	}

	$nodeConfig = $nodes[0];


    $cloudConfig = array();
    $cloudConfig["hostname"]            = $nodeConfig["hostname"];
    $cloudConfig["ssh_authorized_keys"] = $clusterConfig["ssh-authorized-keys"];
    
    $defaultFeatures                    = array('update');
    $features                           = $clusterConfig["features"];
    $features                           = array_merge($features, $defaultFeatures);
    
    foreach($features as $feature) {
        if(!file_exists("../features/" . $feature . ".php")) {
            throw new \Exception("Unkwnown feature: " . $feature);
        }
        
        $featureFn = require("../features/" . $feature . ".php");
        $cloudConfig = call_user_func($featureFn, $clusterConfig, $nodeConfig, $cloudConfig);
    }

    $dumper = new Dumper();
    $cloudConfigFileContent = "#cloud-config\n";
    $cloudConfigFileContent .= $dumper->dump($cloudConfig, 6);

    //Validate generated file with coreos-cloudinit
    $tmpFileName = tempnam("/tmp", "cloud-config.yml");
    file_put_contents($tmpFileName, $cloudConfigFileContent);
    exec("/usr/local/bin/coreos-cloudinit -validate --from-file=".escapeshellarg($tmpFileName), $output, $ret);
    unlink($tmpFileName);
    
    if ($ret != 0) {
        throw new \Exception("coreos-cloudinit validation failed:\n\n" . implode("\n", $output));
        
    }
    
} catch (\Exception $e) {
	header('HTTP/1.1 500 Internal server error');
    header("Content-Type: text/plain");
    print $e->getMessage();
	exit;
}

header("Content-Type: text/plain");

if(array_key_exists('format', $_GET) && $_GET['format'] == 'sh') {
    function array_flatten($array) {
        $return = array();

        foreach ($array as $key => $value) {
            if (is_array($value)){
                
                foreach (array_flatten($value) as $subKey => $subValue) {
                 $return[$key . "_" . $subKey] = $subValue;
                    
                }
            }
            else {
                $return[$key] = $value;
            }
        }
        return $return;

    }

    foreach(array_flatten($cloudConfig) as $key => $value) {
        printf("%s=\"%s\"\n", str_replace("-", "_", strtoupper($key)), addslashes($value));
    }
    
} else {
    print($cloudConfigFileContent);    
}


?>



