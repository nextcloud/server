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

$flavor = $compute->createFlavor([
    'name'  => '{flavorName}',
    'ram'   => 128,
    'vcpus' => 1,
    'swap'  => 0,
    'disk'  => 1,
]);
