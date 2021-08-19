<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope'   => [
        'project' => [
            'id' => '{projectId}'
        ]
    ]
]);

$networking = $openstack->networkingV2();

// Get the pool
$pool = $networking->getLoadBalancerPool('{poolId}');

// Member options
$options = [
    'address'      => '10.1.2.3',
    'protocolPort' => 443,
    'weight'       => 1,
    'adminStateUp' => true,
    'subnetId'     => '{subnetId}'
];

$member = $pool->addMember($options);
