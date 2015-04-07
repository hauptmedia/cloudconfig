# cloudconfig

A system for provisioning CoreOS cloud-config.yml files

This system is currently useful if you are running CoreOS on bare metal instances which can be identified by their mac address.

You can setup your cluster nodes in a yaml based configuration file which must be mounted via the *var* mount in the docker instance.

## Example cluster-config.yml
```yaml
cluster:
  features:
    - etcd
    - etcd-ssl
    - fleet
    - mount
    - timezone

  timezone: Europe/Berlin

  # generate a new token for each unique cluster from https://discovery.etcd.io/new
  etcd:
    discovery: https://discovery.etcd.io/xyz

  ssh-authorized-keys:
    - ssh-rsa ...

  update:
    reboot-strategy: off
    group: stable

  nodes:
    - mac: c8:60:00:cc:xx:8d
      hostname: coreos-1
      ip: 11.22.33.44

      fleet:
        metadata: dc=colo1,rack=rack1,disc=ssd,disc_amount=1,mem=32

      update:
        group: alpha


    - mac: c8:60:00:bb:aa:91
      hostname: coreos-2
      ip: 1.2.3.4
      mount:
        - dev: /dev/sdb
          mount-point: /mnt/sdb
          type: ext4
      fleet:
        metadata: dc=colo2,rack=rack2,disc=hdd,disc_amount=2,mem=32
```

## Example usage

### Provisioning server

You have to provide a volume for the */opt/cloudconfig/var* directory. In this directory a file named *cluster-config.yml* is expected.

You might want to copy the example *conf/cluster-config.yml* to your var/ directory for a quick start.


```bash
docker run -d -p 1234:80 \
-v $(pwd):/opt/cloudconfig/var \
-e BASE_URL=http://cloudconfig.example.com:1234 \
hauptmedia/cloudconfig
```

You can also run the provisioning service on your local machine and provide connectivity to it via a reverse ssh tunnel.

```bash
# override the BASE_URL so that the host can use the provided reverse ssh tunnel on 127.0.0.1:8080 to reach this service
# you can also run it in interactive mode and inspect the log files on stdout

docker run -i -t --rm 8080:80 \
-v $(pwd):/opt/cloudconfig/var \
-e BASE_URL=http://127.0.0.1:8080 \
hauptmedia/cloudconfig
```

```bash
ssh -R8080:127.0.0.1:8080 core@host

#or for boot2docker for example
ssh -R8080:192.168.59.103:8080 core@host
```

### Cluster Node usage

Run on CoreOS hosts to update cloud-config.yml or on new (bare metal hosts) to install CoreOS with the provisioned cloud-config.yml

```bash
curl -sSL http://cloudconfig.example.com:1234/install.sh | sudo sh
```

## Available features & config options

### bash-profile

Writes a `/home/core/.bash_profile` file which will automatically source the `/etc/fleetctl.env` file if available

and register the ssh-agent at `/tmp/ssh-agent.sock` if available.

### etcd

Run the etcd service

#### configuration options
* `node[etcd][name]` - The node name (defaults to `node[hostname]`)
* `node[etcd][addr]` - The advertised public hostname:port for client communication (defaults to `node[ip]:2379`)
* `node[etcd][peer-addr]` - The advertised public hostname:port for server communication (defaults to `node[ip]:2380`)
* `cluster[etcd][discovery]` `node[etcd][discovery]` - A URL to use for discovering the peer list (optional)

#### References
* https://coreos.com/docs/distributed-configuration/etcd-configuration/

### etcd-ssl

Secures the etcd service using SSL/TLS. You're required to create a certificate authority for etcd (once) and client, 
server and peer certs for each cluster node.

**The IP addresses used by etcd must be integrated into the certificate.**

You can use the scripts provided in the https://github.com/hauptmedia/ssl-cert repository to manage your etcd ssl certificates.

Please refer to the README.md file in the ssl-cert repository for further information.

### Creating the certificates

```bash
mkdir var/etcd-ca
create-ca -d var/etcd-ca
bin/create-etcd-cert -t server -c coreos-1.skydns.io -i 192.168.1.2 
bin/create-etcd-cert -t client -c coreos-1.skydns.io -i 192.168.1.2
bin/create-etcd-cert -t peer -c coreos-1.skydns.io -i 192.168.1.2
```

### Testing authentification

```bash
curl --cert /etc/ssl/etcd/certs/client.crt \
     --cacert /etc/ssl/etcd/certs/ca.crt  \
     --key /etc/ssl/etcd/private/client.key \
     -v https://127.0.0.1:2379/v2/leader

curl --cert /etc/ssl/etcd/certs/client.crt \
     --cacert /etc/ssl/etcd/certs/ca.crt  \
     --key /etc/ssl/etcd/private/client.key \
     -v https://127.0.0.1:2379/v2/peers
       
curl --cert /etc/ssl/etcd/certs/client.crt \
     --cacert /etc/ssl/etcd/certs/ca.crt  \
     --key /etc/ssl/etcd/private/client.key -v \
     -XPUT -v -L -d value=bar https://127.0.0.1:2379/v2/keys/foo
 
curl --cert /etc/ssl/etcd/certs/client.crt \
     --cacert /etc/ssl/etcd/certs/ca.crt  \
     --key /etc/ssl/etcd/private/client.key -v \
     -XDELETE -v -L https://127.0.0.1:2379/v2/keys/foo
```

#### References
* https://coreos.com/docs/distributed-configuration/customize-etcd-unit/
* https://coreos.com/docs/distributed-configuration/etcd-security/

### flannel

Starts flanneld service. Will be automatically configured for etcd ssl access if etcd-ssl was enabled.
It will also automatically write the specified network settings in etcd.

#### configuration options
* `cluster[flannel][network]` `node[flannel][network]`
* `cluster[flannel][subnet_len]` `node[flannel][subnet_len]`
* `cluster[flannel][subnet_min]` `node[flannel][subnet_min]`
* `cluster[flannel][subnet_max]` `node[flannel][subnet_max]`
* `cluster[flannel][backend_type]` `node[flannel][backend_type]` - vxlan | udp - defaults to vxlan

#### References
* https://coreos.com/docs/cluster-management/setup/flannel-config/
* https://coreos.com/docs/cluster-management/setup/cloudinit-cloud-config/


### fleet

Runs the fleet service. It automatically configures itself for etcd-ssl if etcd-ssl is enabled.

This feature writes a `/etc/fleet-metadata.env` file which contains the fleet metadata as environment variables.

The fleet metadata keys will be transformed to uppercase. E.g. fleet metadata "dc=dc1,rack=12" will 
be available as `DC=dc1` `RACK=12`

The env file can be used to pass the fleet metadata as environment variables in docker containers
with the `--env-file=/etc/fleet-metadata.env` docker command line option or in systemd service definitions
using the `EnvironmentFile=/etc/fleet-metadata.env` configuration option.

This feature also writes a `/etc/fleetctl.env` file which can be used to provide a configuration to `fleetctl`.

#### configuration options

All default fleet configuration options are available plus:

* `cluster[fleet][verbosity]` `node[fleet][verbosity]` Enable debug logging by setting this to an integer value greater than zero. Only a single debug level exists, so all values greater than zero are considered equivalent. Default: 0
* `cluster[fleet][etcd_servers]` `node[fleet][etcd_servers]` Provide a custom set of etcd endpoints. Default: ["http://127.0.0.1:4001"]
* `cluster[fleet][etcd_request_timeout]` `node[fleet][etcd_request_timeout]` Amount of time in seconds to allow a single etcd request before considering it failed. Default: 1.0 
* `cluster[fleet][etcd_cafile,etcd_keyfile,etcd_certfile]` `node[fleet][etcd_cafile,etcd_keyfile,etcd_certfile]` Provide TLS configuration when SSL certificate authentication is enabled in etcd endpoints
* `cluster[fleet][public_ip]` `node[fleet][public_ip]` IP address that should be published with the local Machine's state and any socket information. If not set, fleetd will attempt to detect the IP it should publish based on the machine's IP routing information.
* `cluster[fleet][metadata]` `node[fleet][metadata]` Comma-delimited key/value pairs that are published with the local to the fleet registry. This data can be used directly by a client of fleet to make scheduling decisions. An example set of metadata could look like: `metadata="region=us-west,az=us-west-1"` 
* `cluster[fleet][agent_ttl]` `node[fleet][agent_ttl]` An Agent will be considered dead if it exceeds this amount of time to communicate with the Registry. The agent will attempt a heartbeat at half of this value. Default: "30s" 
* `cluster[fleet][engine_reconcile_interval]` `node[fleet][engine_reconcile_interval]`  Interval at which the engine should reconcile the cluster schedule in etcd. Default: 2
* `cluster[fleet][strict_host_key_checking]` `node[fleet][strict_host_key_checking]` Boolean which specifies if fleetctl will check the ssh host keys or not

#### Use fleetctl with SSL/TLS configuration shipped with this image

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
fleetctl \
--cert-file=/opt/cloudconfig/var/etcd-ca/certs/fleetctl-client.crt \
--key-file=/opt/cloudconfig/var/etcd-ca/private/fleetctl-client.key \
--ca-file=/opt/cloudconfig/var/etcd-ca/certs/etcd-ca.crt \
--endpoint=https://public-ip-of-etcd:2379 \
list-machines
````

#### Use fleetctl with SSL/TLS configuration on a coreos-node

```bash
fleetctl \
--cert-file=/etc/ssl/etcd/certs/client.crt \
--key-file=/etc/ssl/etcd/private/client.key \
--ca-file=/etc/ssl/etcd/certs/ca.crt \
--endpoint=https://127.0.0.1:2379 \
list-machines
````

#### References
* https://github.com/coreos/fleet/blob/master/Documentation/deployment-and-configuration.md#configuration
* https://coreos.com/docs/launching-containers/launching/launching-containers-fleet/


### host-env-file

This feature writes the some information about the evironment in `/etc/host.env`.

This feature also install the `/opt/bin/getip` script for easy retrieval of the system's main ip address.


### mount

Mounts a given device to the specified mount point

* `cluster[mount][dev]` `cluster[mount][dev]` Device which should be mounted
* `cluster[mount][mount-point]` `cluster[mount][mount-point]` Mount point where the device should be mounted
* `cluster[mount][type]` `cluster[mount][type]` Filesystem type of the mountpoint

#### References
* https://coreos.com/docs/cluster-management/setup/mounting-storage/


### private-repository

Add support for private docker repositories 

* `cluster[private-repository][insecure-addr]` `node[private-repository][insecure-addr]` - 
If the private registry supports only HTTP or HTTPS with an unknown CA certificate specfiy it's address here. CIDR notations are also allowed.

#### References

https://coreos.com/docs/launching-containers/building/registry-authentication/

### set-host-dns-entry

This feature utilizes the `/opt/bin/skydns-set-record` script provided by the `skydns` feature and registers the
hostname of the node in skydns. This will only work if the hostname was specified as a FQDN and skydns is configured
to be authoritative for the domain name.

### skydns

Starts the skydns service. Will be automatically configured for etcd ssl access if etcd-ssl was enabled.
It will also automatically write the specified dns config in etcd.

#### configuration options
* `cluster[skydns][dns_addr]` `node[skydns][dns_addr]` IP:port on which SkyDNS should listen, defaults to node[ip]:53.
* `cluster[skydns][domain]` domain for which SkyDNS is authoritative, defaults to skydns.local
* `cluster[skydns][nameservers]` forward DNS requests to these nameservers (array of IP:port combination), when not authoritative for a domain, defaults to [8.8.8.8:53, 8.8.4.4:53]
* `cluster[skydns][ttl]` default TTL in seconds to use on replies when none is set in etcd, defaults to 3600.
* `cluster[skydns][min_ttl]` minimum TTL in seconds to use on NXDOMAIN, defaults to 30.

#### Setting a hostname with curl

```bash
curl -XPUT \
    --cert /etc/ssl/etcd/certs/client.crt \
    --cacert /etc/ssl/etcd/certs/ca.crt  \
    --key /etc/ssl/etcd/private/client.key \
    https://127.0.0.1:2379/v2/keys/skydns/local/skydns/test \
    -d value='{"host":"10.10.13.37"}'
```

#### using the skydns-set-record script

The skydns feature installs a convenience script which can be used to set hostname records at `/home/core/bin/skydns-set-record`

```bash
/opt/bin/skydns-set-record test.skydns.local 10.10.10.10

# with ttl (after which the record becomes unavailable)
/opt/bin/skydns-set-record test.skydns.local 10.10.10.10 60
```

#### References
* https://github.com/skynetservices/skydns 

### ssh-agent

Runs an ssh-agent for the `core` user. The ssh-agent socket will be available at `/tmp/ssh-agent.sock`.
 
It automatically registers the private key of the `core` user at the agent (assuming that it has no passphrase set).

This feature can be used to enable fleetctl ssh authentication on the coreos node. 

### ssh-key

This features writes a private key file for the core user. This is useful in combination with the ssh-agent feature to
provide authentication credentials for fleetctl.

#### configuration options

* `cluster[ssh-key][private] `node[ssh-key][private]` - Content of the private key file (will be written to `/home/core/.ssh/id_rsa`)
* `cluster[ssh-key][public] `node[ssh-key][public]` - Content of the public key file (will be written to `/home/core/.ssh/id_rsa.pub`)


### timezone
* `cluster[timezone] `node[timezone]` - Set the timezone to the specified string on a cluster wide or node level

### update

Configures the update strategy on a cluster or node level. This feature is always enabled.

#### configuration options
* `cluster[update][reboot-strategy]` `node[update][reboot-strategy]` - reboot | etcd-lock | best-effort | off (defaults to off)
* `cluster[update][group]` `node[update][group]` - master | alpha | beta | stable (defaults to stable)
* `cluster[update][server]` `node[update][server]` - location of the CoreUpdate server

#### References
* https://coreos.com/docs/cluster-management/setup/update-strategies/
* https://coreos.com/docs/cluster-management/setup/switching-channels/
* https://coreos.com/docs/cluster-management/setup/cloudinit-cloud-config/

