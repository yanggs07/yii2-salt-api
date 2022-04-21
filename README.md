# Usages
Before using this salt-api PHP SDK, enable salt-api for salt master is required.

## Salt-api Installation And Configuration
see [salt-api](https://www.unixhot.com/docs/saltstack/ref/netapi/all/salt.netapi.rest_cherrypy.html#a-rest-api-for-salt)
### Install salt-api for linux
```shell
# yum -y install salt-api
or
# apt -y install salt-api
or
whatever your linux system can do this
```
### Generate self-signed cert with correct api host domain
```shell
salt-call --local tls.create_self_signed_cert CN=salt-master.server days=3650
local:
    Created Private Key: "/etc/pki/tls/certs/salt-master.server.key." Created Certificate: "/etc/pki/tls/certs/salt-master.server.crt."
```
Later we would use `https://salt-master.server:8000` for remote api call and use custom CA cert file.
Copy the CA public key file `/etc/pki/tls/certs/salt-master.server.crt` to your PHP project path.

### Configure salt-api and salt-master
Ensure the master conf include `master.d`:
```shell
[root@salt-master ~]# vim /etc/salt/master
default_include: master.d/*.conf
```

Create new api conf:
```shell
[root@salt-master ~]# vim /etc/salt/master.d/api.conf
rest_cherrypy:
  host: <ip_salt-api_would_listen>
  port: 8000
  ssl_crt: /etc/pki/tls/certs/salt-master.server.crt
  ssl_key: /etc/pki/tls/certs/salt-master.server.key
```

Create salt-api user with YOUR password!
```shell
[root@salt-master ~]# useradd -M -s /sbin/nologin saltapi
[root@salt-master ~]# echo 'saltapi' | passwd --stdin saltapi
```

Create auth conf:
```shell
[root@salt-master ~]# vim /etc/salt/master.d/auth.conf
external_auth:
  pam:
    saltapi:
      - .*
      - '@wheel'
      - '@runner'
      - '@jobs'
```

Restart services:
```shell
[root@salt-master ~]# systemctl restart salt-master
[root@salt-master ~]# systemctl start salt-api
[root@salt-master ~]# systemctl enable salt-api
```

# SDK DEMO Code
`tes.php`:
````php
<?php
require '../vendor/autoload.php';


$client = new \yanggs07\saltapi\ApiClient([
    'host' => 'salt-master.server',
    'port' => 8000,
    'cacert' => '/path/to/your/salt-api_self-signed/ca.crt', // or use
    // if you do not have the ca crt file, use this instead
    // 'skipCertCheck' => true 
    'username' => 'saltapi',
    'password' => 'saltapi',
]);

$session = $client->pamLogin();
$ret = $client->localAsyncRun($session, "*", 'cmd.run', 'w');
$jid = $ret['jid'];
for ($i = 0;$i < 30; $i++) {
    $ret = $client->job($session, $jid);
    var_dump($ret);
    sleep(1);
}
?>
````

# TODOs
1. More api calls
2. Session persistence with cache interface
3. Better exception treatment
4. more...