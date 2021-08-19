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

$data = [
    'name'      => '{name}',
    'publicKey' => '{publicKey}'
];

/** @var \OpenStack\Compute\v2\Models\Keypair $keypair */
$keypair = $compute->createKeypair($data);
