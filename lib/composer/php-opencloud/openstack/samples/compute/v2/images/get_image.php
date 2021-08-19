<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$compute = $openstack->computeV2(['region' => '{region}']);

$image = $compute->getImage(['id' => '{imageId}']);

// By default, this will return an empty Image object and NOT hit the API.
// This is convenient for when you want to use the object for operations
// that do not require an initial GET request. To retrieve the image's details,
// run the following, which *will* call the API with a GET request:

$image->retrieve();
