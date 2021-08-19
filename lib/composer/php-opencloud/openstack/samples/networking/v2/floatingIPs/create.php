<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}',
    ],
    'scope'   => ['project' => ['id' => '{projectId}']],
]);

$networking = $openstack->networkingV2ExtLayer3();

/** @var \OpenStack\Networking\v2\Extensions\Layer3\Models\FloatingIp $ip */
$ip = $networking->createFloatingIp([
    "floatingNetworkId" => "{networkId}",
    "portId"            => "{portId}",
    'fixedIpAddress'    => '{fixedIpAddress}',
]);
