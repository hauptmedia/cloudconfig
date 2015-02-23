# puppetmaster 

A system for provisioning CoreOS cloud-config.yml files

## Example usage

### Server 

```bash
docker run -d -p 1234:80 -e BASE_URL=http://puppetmaster.example.com:1234 hauptmedia/puppetmaster
```

### Node usage

Run on CoreOS hosts to update cloud-config.yml or on new (bare metal hosts) to install CoreOS with the provisioned cloud-config.yml

```bash
sudo curl -sSL http://puppetmaster.example.com:1234/install.sh | sh
```
