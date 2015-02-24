# puppetmaster 

A system for provisioning CoreOS cloud-config.yml files

This system is currently useful if you are running CoreOS on bare metal instances which can be identified by their mac address.

It uses the public ip address of the machines for communication.

You can setup your cluster nodes in a yaml based configuration file which must be mounted in the docker instance.

## Example cluster-config.yml
```yaml
cluster:
  discovery: https://discovery.etcd.io/xyz
  ssh-authorized-keys:
    - ssh-rsa ...

  nodes:
    - mac: c8:60:00:cc:xx:8d
      hostname: coreos-1
      ip: 11.22.33.44
      metadata: datacenter=colo1

    - mac: c8:60:00:bb:aa:91
      hostname: coreos-2
      ip: 1.2.3.4
      metadata: datacenter=colo2
```

## Example usage

### Server 

```bash
docker run -d -p 1234:80 -v /root/cluster-config.yml:/var/www/cluster-config.yml -e BASE_URL=http://puppetmaster.example.com:1234 hauptmedia/puppetmaster
```

### Node usage

Run on CoreOS hosts to update cloud-config.yml or on new (bare metal hosts) to install CoreOS with the provisioned cloud-config.yml

```bash
curl -sSL http://puppetmaster.example.com:1234/install.sh | sudo sh
```

## References in CoreOS documentation

https://coreos.com/docs/cluster-management/setup/cloudinit-cloud-config/
https://coreos.com/docs/cluster-management/setup/mounting-storage/
https://coreos.com/docs/launching-containers/building/customizing-docker/
https://coreos.com/docs/launching-containers/building/registry-authentication/

