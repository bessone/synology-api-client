<?php

/**
 * This file is an example on how to use FileStationClient
 */
require 'vendor/autoload.php';

use Synology\Api\Client\FileStationClient;
use Synology\Api\Client\SynologyDriveClient;
use Synology\Api\Client\SynologyException;

// Basic usage
$synology = new SynologyDriveClient('', '', '', 1);
try {
    $synology->connect('', '');
} catch (SynologyException $e) {
    echo $e->getMessage();
}
$synology->activateDebug();

//print_r($synology->getAvailableApi());
