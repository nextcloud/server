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

/** @var \GuzzleHttp\Stream\Stream $stream */
$stream = $openstack->objectStoreV1()
                    ->getContainer('{containerName}')
                    ->getObject('{objectName}')
                    ->download();
