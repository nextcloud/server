<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope' => ['project' => ['id' => '{projectId}']]
]);

/** @var \OpenStack\Networking\v2\Extensions\Layer3\Models\FloatingIp $ip */
$ip = $openstack->networkingV2ExtLayer3()
                ->getFloatingIp('{id}');
