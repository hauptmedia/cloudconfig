<?php
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

error_reporting(E_ALL);
ini_set('display_errors', 1);
$yaml = new Parser();

try {
	$yamlContent = $yaml->parse(file_get_contents('../cluster-config.yml'));

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

    foreach($clusterConfig["features"] as $feature) {
        if(!file_exists("../features/" . $feature . ".php")) {
            throw new \Exception("Unkwnown feature: " . $feature);
        }
        
        $featureFn = require("../features/" . $feature . ".php");
        $cloudConfig = call_user_func($featureFn, $clusterConfig, $nodeConfig, $cloudConfig);
    }

    $dumper = new Dumper();
    $cloudConfigFileContent = "#cloud-config\n";
    $cloudConfigFileContent .= $dumper->dump($cloudConfig, 4);

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
print($cloudConfigFileContent);

?>



