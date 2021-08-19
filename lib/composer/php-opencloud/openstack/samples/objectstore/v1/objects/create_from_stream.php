<?php

require 'vendor/autoload.php';

use GuzzleHttp\Psr7\Stream;

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

// You can use any instance of \Psr\Http\Message\StreamInterface
$stream = new Stream(fopen('/path/to/object.txt', 'r'));

$options = [
    'name'   => '{objectName}',
    'stream' => $stream,
];

/** @var \OpenStack\ObjectStore\v1\Models\StorageObject $object */
$object = $openstack->objectStoreV1()
    ->getContainer('{containerName}')
    ->createObject($options);
