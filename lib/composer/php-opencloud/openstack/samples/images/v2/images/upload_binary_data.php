<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => ['id' => '{userId}', 'password' => '{password}'],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$service = $openstack->imagesV2();

$image  = $service->getImage('{imageId}');
$stream = \GuzzleHttp\Psr7\stream_for(fopen('{fileName}', 'r'));
$image->uploadData($stream);
