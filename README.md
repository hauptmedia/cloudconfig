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

### etcd

Run the etcd service

#### configuration options
* `node[etcd][name]` - The node name (defaults to `node[hostname]`)
* `node[etcd][addr]` - The advertised public hostname:port for client communication (defaults to `node[ip]:2379`)
* `node[etcd][peer-addr]` - The advertised public hostname:port for server communication (defaults to `node[ip]:2380`)
* `cluster[etcd][discovery]` `node[etcd][discovery]` - A URL to use for discovering the peer list (optional)

#### References
* https://coreos.com/docs/distributed-configuration/etcd-configuration/

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

### etcd-ssl

Secures the etcd service using SSL/TLS. You're required to create a certificate authority for etcd (once) and client, 
server and peer certs for each cluster node.

**The IP addresses used by etcd must be integrated into the certificate.**

#### generating the certificates
Create a certificate authority (once)

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-ca
````

For each node generate a server, peer and client certificate

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-peer-cert 1.etcd.example.com 5.6.7.8 192.168.2.2

docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-server-cert 1.etcd.example.com 5.6.7.8 192.168.2.2

docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-client-cert 1.etcd.example.com
````

#### References
* https://coreos.com/docs/distributed-configuration/customize-etcd-unit/
* https://coreos.com/docs/distributed-configuration/etcd-security/

### fleet

Run the fleet service. Automaticly configures itself for etcd-ssl if etcd-ssl is enabled.

#### Use fleetctl with SSL/TLS configuration shipped with this image

You should create your own client ssl certificate as described in the "Creating a client certificate for etcd" section.

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

### fleet-metadata-env-file

This feature writes the fleet metadata as an env file in `/etc/fleet-metadata.env`

The env file can be used to pass the fleet metadata as environment variables in docker containers
with the `--env-file=/etc/fleet-metadata.env` docker command line option or in systemd service definitions
using the `EnvironmentFile=/etc/fleet-metadata.env` configuration option

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

### mount

Mounts a given device to the specified mount point

* `cluster[mount][dev]` `cluster[mount][dev]` Device which should be mounted
* `cluster[mount][mount-point]` `cluster[mount][mount-point]` Mount point where the device should be mounted
* `cluster[mount][type]` `cluster[mount][type]` Filesystem type of the mountpoint

#### References
* https://coreos.com/docs/cluster-management/setup/mounting-storage/


## Securing etcd with SSL/TLS

In a production environment it might be a good idea to secure etcd with it's integrated SSL/TLS secruity. However etcd
needs specially crafted certificates to function properly. An *openssl.cnf* with all the needed settings is included in
this repository.

You can use the provided scripts in *bin* directory to manage your ssl certificates.

### Creating a certificate authority for etcd

Run the *create-etcd-ca* script and provide a volume for the */opt/cloudconfig/var* directory.

The certificates will be saved in *var/etcd-ca*.

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-ca
```

### Creating a server or peer certificate for etcd

Run the *create-server-cert* or *create-peer-cert* script with a *common name* and up to *three additional* ip addresses and provide a volume for the */opt/cloudconfig/var* directory.

The certificates will be saved in *var/etcd-ca*.

**Please note: the CommonName must match the name used for the etcd instance**

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-server-cert 1.etcd.example.com 5.6.7.8 192.168.2.2
````

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-peer-cert 1.etcd.example.com 5.6.7.8 192.168.2.2
````

### Creating a client certificate for etcd

Run the *create-client-cert* script with a *common name* and provide a volume for the */opt/cloudconfig/var* directory.

The certificates will be saved in *var/etcd-ca*.

```bash
docker run -i -t --rm \
-v $(pwd):/opt/cloudconfig/var \
hauptmedia/cloudconfig \
create-etcd-client-cert etcd-client.example.com
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

### Certificate requirements in detail

#### Client certificate requirements
* IP address of the client has to be included as *subjectAltName* on the certificate. In order to get *subjectAltName* you need to enable relevant *X509.3* extension
* Certificate has to have *Extended key usage* extension enabled and allow *TLS Web Client Authentication*.

#### Peer certificate requirements
* Similarly to client certificate, the IP address has to be included in *SAN*. See above for details.
* Certificate has to have *Extended key usage* extension enabled and allow *TLS Web Server Authentication*.


### References
* https://coreos.com/docs/distributed-configuration/etcd-security/
* http://blog.skrobul.com/securing_etcd_with_tls/
* https://github.com/kelseyhightower/etcd-production-setup
* http://www.g-loaded.eu/2005/11/10/be-your-own-ca/

## References on third party websites and the CoreOS documentation

* https://coreos.com/docs/launching-containers/building/customizing-docker/
* https://coreos.com/docs/launching-containers/building/registry-authentication/

