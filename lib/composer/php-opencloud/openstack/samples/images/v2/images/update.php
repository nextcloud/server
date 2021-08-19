<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => ['id' => '{userId}', 'password' => '{password}'],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$service = $openstack->imagesV2();

$image = $service->getImage('{imageId}');
$image->update([
    'minDisk'    => 1,
    'minRam'     => 1,
    'name'       => '{name}',
    'protected'  => false,
    'visibility' => '{visibility}',
]);
