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
$pool->retrieve();

// Get a member
$member = $pool->getMember('{memberId}');
$member->retrieve();
