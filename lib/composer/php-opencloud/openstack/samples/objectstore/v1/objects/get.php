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

/** @var \OpenStack\ObjectStore\v1\Models\StorageObject $object */
$object = $openstack->objectStoreV1()
                    ->getContainer('{containerName}')
                    ->getObject('{objectName}');
