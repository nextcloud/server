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

$options = [
    // Required
    'name'     => '{serverName}',
    'imageId'  => '{imageId}',
    'flavorId' => '{flavorId}',

    // Required if multiple network is defined
    'networks'  => [
        ['uuid' => '{networkId}']
    ],

    // Optional
    'metadata' => ['foo' => 'bar'],
    'userData' => base64_encode('echo "Hello World. The time is now $(date -R)!" | tee /root/output.txt')
];

// Create the server
/**@var OpenStack\Compute\v2\Models\Server $server */
$server = $compute->createServer($options);
