<?php

/**
 * This file is an example on how to use FileStationClient
 */

use Synology\Api\Client\FileStationClient;
use Synology\Api\Client\SynologyException;

// Basic usage
$synology = new FileStationClient('192.168.10.5', 5001, 'https', 1);
$synology->activateDebug();
try {
    $synology->connect('admin', '****');
} catch (SynologyException $e) {
    echo $e->getMessage();
}
print_r($synology->getAvailableApi());
