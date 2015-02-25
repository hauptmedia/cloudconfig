# puppetmaster 

A system for provisioning CoreOS cloud-config.yml files

This system is currently useful if you are running CoreOS on bare metal instances which can be identified by their mac address.

It uses the public ip address of the machines for communication.

You can setup your cluster nodes in a yaml based configuration file which must be mounted in the docker instance.

## Example cluster-config.yml
```yaml
cluster:
  features:
    - etcd
    - fleet
      
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

## Securing etcd with TLS

Etcd need specially crafted certificates to function properly. An *openssl.cnf* with all the needed settings is included in this repository.

You can use the provided scripts in *bin* directory to manage your ssl certificates.

### Create an certificate authority for etcd

Run the create-etcd-ca command with the common name as parameter. 

You need to provide a volume for the */opt/cloudconfig/var* directory. The certificates will be saved in *var/etcd-ca*.

```bash
docker run -i -t --rm -v $(pwd)/var:/opt/cloudconfig/var hauptmedia/cloudconfig create-etcd-ca ca.etcd.commonname.com
```

### Certificate requirements in detail

#### Client certificate requirements
* IP address of the client has to be included as *subjectAltName* on the certificate. In order to get *subjectAltName* you need to enable relevant *X509.3* extension
* Certificate has to have *Extended key usage* extension enabled and allow *TLS Web Client Authentication*.

#### Peer certificate requirements
* Similarly to client certificate, the IP address has to be included in *SAN*. See above for details.
* Certificate has to have *Extended key usage* extension enabled and allow *TLS Web Server Authentication*.


## References on third party websites and the CoreOS documentation

* https://coreos.com/docs/cluster-management/setup/cloudinit-cloud-config/
* https://coreos.com/docs/cluster-management/setup/mounting-storage/
* http://blog.skrobul.com/securing_etcd_with_tls/
* https://coreos.com/docs/distributed-configuration/etcd-security/
* http://www.g-loaded.eu/2005/11/10/be-your-own-ca/
* https://coreos.com/docs/launching-containers/building/customizing-docker/
* https://coreos.com/docs/launching-containers/building/registry-authentication/

