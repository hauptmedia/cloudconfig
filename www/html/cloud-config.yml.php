<?php
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

$yaml = new Parser();

try {
	$yamlContent = $yaml->parse(file_get_contents('../cluster-config.yml'));

	if(!array_key_exists('mac', $_GET)) {
		throw new \Exception("Missing mac");
	}

	$mac = $_GET['mac'];

	$cluster = $yamlContent['cluster'];

	$nodes = array_values(array_filter($cluster['nodes'], function($entry) use ($mac) {
		return $entry['mac'] == $mac;
	}));

	if(count($nodes) != 1) {
	        header('HTTP/1.1 404 Not Found');
        	exit;
	}

	$node = $nodes[0];

} catch (\Exception $e) {
	header('HTTP/1.1 500 Internal server error');
	exit;
}
?>
#cloud-config

hostname: <?=$node['hostname']?>

ssh_authorized_keys:
  - <?=$cluster['ssh-authorized-keys'][0]?>

coreos:
  units:
    - name: etcd.service
      command: start
    - name: fleet.service
      command: start
    - name: format-ephemeral.service
      command: start
      content: |
        [Unit]
        Description=Formats the ephemeral drive
        [Service]
        Type=oneshot
        RemainAfterExit=yes
        ExecStart=/usr/sbin/wipefs -f /dev/sdb
        ExecStart=/usr/sbin/mke2fs -q -t ext4 -b 4096 -i 4096 -I 128 /dev/sdb
    - name: var-lib-docker.mount
      command: start
      content: |
        [Unit]
        Description=Mount ephemeral to /var/lib/docker
        Requires=format-ephemeral.service
        After=format-ephemeral.service
        Before=docker.service
        [Mount]
        What=/dev/sdb
        Where=/var/lib/docker
        Type=ext4

  etcd:
      name: <?=$node['hostname']?> 
      discovery: <?=$cluster['discovery']?>

      addr: <?=$node['ip']?>:4001
      peer-addr: <?=$node['ip']?>:7001


  fleet:
      public-ip: <?=$node['ip']?> 
      metadata: <?=$node['metadata']?>
