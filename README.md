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

  etcd-ssl:
    mode: both

  # generate a new token for each unique cluster from https://discovery.etcd.io/new
  etcd:
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

### Provisioning server

You have to provide a volume for the */opt/cloudconfig/var* directory. In this directory a file named *cluster-config.yml* is expected.

You might want to copy the example *conf/cluster-config.yml* to your var/ directory for a quick start.


```bash
docker run -d -p 1234:80 -v $(pwd)/var:/opt/cloudconfig/var -e BASE_URL=http://cloudconfig.example.com:1234 hauptmedia/cloudconfig
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
* *node[etcd][name]* - The node name (defaults to *node[hostname]*)
* *node[etcd][addr]* - The advertised public hostname:port for client communication (defaults to *node[ip]:2379*)
* *node[etcd][peer-addr]* - The advertised public hostname:port for server communication (defaults to *node[ip]:2380*)
* *cluster[etcd][discovery]* or *node[etcd][discovery]* - A URL to use for discovering the peer list (optional)

#### References
* https://coreos.com/docs/distributed-configuration/etcd-configuration/

### etcd-ssl

Secures the etcd service using SSL/TLS. You're required to create a certificate authority for etcd (once) and client, 
server and/or peer certs for each cluster node.

**The IP addresses used by etcd must be integrated into the certificate.**

Create a certificate authority (once)

```bash
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-ca
````

For mode: peer-only only a peer certificate is required

```bash
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-peer-cert 1.etcd.example.com 5.6.7.8 192.168.2.2
````

If you run mode: both you need also a server and client certificate

```bash
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-server-cert 1.etcd.example.com 5.6.7.8 192.168.2.2
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-client-cert 1.etcd.example.com
````

#### configuration options
* *cluster[etcd-ssl][mode]* or *node[etcd-ssl][mode]* - both | peer-only - If set to both secure both peer and client connector. If set to peer-only only secure the peer connector

#### References
* https://coreos.com/docs/distributed-configuration/customize-etcd-unit/
* https://coreos.com/docs/distributed-configuration/etcd-security/

### fleet

Run the fleet service

### ephemeral-drive

Mounts an additional ephemeral drive to a specified mount point

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
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-ca
```

### Creating a server certificate for etcd


Run the *create-server-cert* script with a *common name* and up to *three additional* ip addresses and provide a volume for the */opt/cloudconfig/var* directory.

The certificates will be saved in *var/etcd-ca*.

**Please note: the CommonName must match the name used for the etcd instance**

```bash
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-server-cert 1.etcd.example.com 5.6.7.8 192.168.2.2
````

### Creating a client certificate for etcd

Run the *create-client-cert* script with a *common name* and provide a volume for the */opt/cloudconfig/var* directory.

The certificates will be saved in *var/etcd-ca*.

```bash
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-client-cert etcd-client.example.com
```


### Testing authentification
 
Check if all machines are returning the same leader using:

```bash
curl --cert /etc/ssl/etcd/certs/client.crt \
     --cacert /etc/ssl/etcd/certs/ca.crt  \
     --key /etc/ssl/etcd/private/client.key \
       https://127.0.0.1:2379/v2/stats/leader
```


### Using client auth with curl

**Please note: in order to activate client authentification in etcd you need to run etcd with the *-ca-file* option**

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

* https://coreos.com/docs/cluster-management/setup/cloudinit-cloud-config/
* https://coreos.com/docs/launching-containers/building/customizing-docker/
* https://coreos.com/docs/launching-containers/building/registry-authentication/

