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

$service = $openstack->objectStoreV1();

foreach ($service->listContainers() as $container) {
    /** @var $container \OpenStack\ObjectStore\v1\Models\Container */
}
