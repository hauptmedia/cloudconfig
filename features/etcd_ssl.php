<?php
/*
 * ETCD_CA_FILE - location of CA certificate used to sign client certificates.
 * ETCD_CERT_FILE - location of ceritificate used for communication with clients
 * ETCD_KEY_FILE - location of private key used for communication with clients
 * ETCD_CA_FILE - location of CA certificate used for signing peer certificates. In my case it was the same CA.
 * ETCD_PEER_CERT_FILE - location of ceritificate used for communication with other etcd nodes
 * ETCD_PEER_KEY_FILE - location of private key used for communication with other etcd nodes
 */
return function($clusterConfig, $nodeConfig, $cloudConfig) {

    return $cloudConfig;

};
/*
write_files:
- path: /run/systemd/system/etcd.service.d/30-certificates.conf
    permissions: 0644
    content: |
      [Service]
      # Client Env Vars
      Environment=ETCD_CA_FILE=/etc/ssl/etcd/certs/ca.pem
      Environment=ETCD_CERT_FILE=/etc/ssl/etcd/certs/etcd-client.pem
      Environment=ETCD_KEY_FILE=/etc/ssl/etcd/private/etcd-client.pem
      # Peer Env Vars
      Environment=ETCD_PEER_CA_FILE=/etc/ssl/etcd/certs/ca.pem
      Environment=ETCD_PEER_CERT_FILE=/etc/ssl/etcd/certs/etcd-peer.pem
      Environment=ETCD_PEER_KEY_FILE=/etc/ssl/etcd/private/etcd-peer.pem
- path: /etc/ssl/etcd/certs/ca.pem
    permissions: 0644
    content: |
      -----BEGIN CERTIFICATE-----
      MIIDXjCCAsegAwIBAgIJAJXzVr07dOwSMA0GCSqGSIb3DQEBBQUAMIHGMQswCQYD
      VQQGEwJVUzELMAkGA1UECAwCVFgxFDASBgNVBAcMC1NhbiBBbnRvbmlvMRIwEAYD
      ........... TRUNCATED ............
      Fswf5tfAmQviftvXd/wA8/DcsRWe/75xVF6UA3IpntHux0vVU1RUPvg+At/1urUJ
      d5A=
          -----END CERTIFICATE-----
  - path: /etc/ssl/etcd/certs/etcd-client.pem
    permissions: 0644
    content: |
      Please generate new certificate with cluster/ca/new_node_cert.sh
- path: /etc/ssl/etcd/private/etcd-client.pem
    permissions: 0644
    content: |
      Please generate new key with cluster/ca/new_node_cert.sh
- path: /etc/ssl/etcd/certs/etcd-peer.pem
    permissions: 0644
    content: |
      Please generate new certificate with cluster/ca/new_node_cert.sh
- path: /etc/ssl/etcd/private/etcd-peer.pem
    permissions: 0644
    content: |
      Please generate new key with cluster/ca/new_node_cert.sh
*/