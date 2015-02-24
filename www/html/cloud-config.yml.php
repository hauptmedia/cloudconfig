<?php
// generate a new token for each unique cluster from https://discovery.etcd.io/new
$discovery = "https://discovery.etcd.io/05b391cfc6ff5616461b747ff5562646";

$database = array(
	'c8:60:00:5e:c4:8d' => array(
		'hostname'	=> 'coreos-1',
		'discovery'	=> $discovery,
		'metadata'	=> 'datacenter=hetzner-rz16'
	),
	'c8:60:00:54:be:91' => array(
		'hostname'	=> 'coreos-2',
		'discovery'	=> $discovery,
		'metadata'	=> 'datacenter=hetzner-rz16'
	)
);

if(!array_key_exists('mac', $_GET) || !array_key_exists($_GET['mac'], $database)) {
        header('HTTP/1.1 404 Not Found');
	exit;
}

$entry = $database[$_GET['mac']];

?>
#cloud-config

hostname: <?=$entry['hostname']?>

ssh_authorized_keys:
  - ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEArKMGZBolEp3VmDoT430lgDcR6zee+dhvxE/Qe2PHhBA6yxJEqJGAwXE5OEUVbSa2UCjWKzaxmHLMuLZswyFtYe02MYxpXAwQSVggwWUfMD6bvl3afAuFtXbbt+qkrzh+YQcqOYh74/e5BA4cTNLs/5Q4X403j+IT4utbr0r3vKcKU14v5puoL+Vwy6GJSJWvUlzRZzAPZuDCLdv8qaeBVv9zZWNooS7Y7M7rGx1jiW3M2pJC11FuHgLRwKUT4zrKuVqREQXq325a3V0kh6msbKrkfKQAbTREGpllyqCe7JN6CPRDWHp0/DXjcsxhQYHT6A7tp04nvwDGscu2hKzAOQ== Julian Haupt <julian.haupt@hauptmedia.de> 

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
      name: <?=$entry['hostname']?> 
      discovery: <?=$entry['discovery']?>

      # multi-region and multi-cloud deployments need to use $public_ipv4
      addr: $public_ipv4:4001
      peer-addr: $public_ipv4:7001

  fleet:
      public-ip: $public_ipv4
      metadata: <?=$entry['metadata']?>
