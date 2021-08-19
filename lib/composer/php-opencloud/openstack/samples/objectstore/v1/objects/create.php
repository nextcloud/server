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

$options = [
    'name'    => '{objectName}',
    'content' => '{objectContent}',
];

/** @var \OpenStack\ObjectStore\v1\Models\StorageObject $object */
$object = $openstack->objectStoreV1()
                    ->getContainer('{containerName}')
                    ->createObject($options);
