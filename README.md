Synology FileStation Client API
=================

This is a PHP Library that consume Synology FileStation APIs

* SYNO.Api :
    * connect
    * disconnect
    * getAvailableApi

* SYNO.FileStation:
    * connect
    * disconnect
    * getInfo
    * getShares
    * getObjectInfo
    * getList
    * search
    * download

Usage for FileStationClient Synology Api:
```php
$synology = new FileStationClient('192.168.10.5', 5000, 'http', 1);
$synology->activateDebug();
$synology->connect('admin', 'xxxx');
print_r($synology->getAvailableApi());
``` 
