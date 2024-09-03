<?php

/**
 * This file is an example on how to use FileStationClient
 */
require 'vendor/autoload.php';

use Synology\Api\Client\FileStationClient;
use Synology\Api\Client\SynologyDriveClient;
use Synology\Api\Client\SynologyException;

// Basic usage
//$synology = new SynologyDriveClient('213.77.35.103', 5001, 'https', 1);
$synology = new SynologyDriveClient('213.77.35.99', 5001, 'https', 1);
try {
    $synology->connect('master.zg', 'CybeR2024$');
} catch (SynologyException $e) {
    echo $e->getMessage();
}
$synology->activateDebug();

//print_r($synology->getAvailableApi());
//print_r($synology->getList('/homes/zg_edytor'));
//print_r($synology->createFolder('/ZG','Kazio'));
// print_r($file);
print_r($synology->uploadFile('/home/kazio/synology-api-client/README.md','README.md', '/ZG'));
//print_r($synology->rename('["/home/kazio/Kazio.md"]','[README.md]'));
//print_r($synology->delete( '/home/kazio/README.md'));
print_r($synology->getList('/ZG'));
//print_r($synology->shareCreate('["/homes/zg_edytor/Kazio/README.md"]'));
print_r($synology->shareList());
//print_r($synology->delete('/homes/zg_edytor/Kazio/README.md'));
//print_r($synology->shareList());

//print_r($synology->shareActiveList());
//print_r($synology->getShares());
//print_r($synology->getInfo());
//print_r($synology->download('["/zg_documents/README.md"]', 'download'));
//$synology-> download(['/home/README.md'],'download');s