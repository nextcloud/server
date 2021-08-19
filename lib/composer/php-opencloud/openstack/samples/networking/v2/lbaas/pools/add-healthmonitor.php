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

// Health Monitor options
$options = [
    'type'          => 'HTTPS',
    'delay'         => 1,
    'timeout'       => 1,
    'httpMethod'    => 'GET',
    'urlPath'       => '/',
    'expectedCodes' => '200,201,302',
    'maxRetries'    => 5,
    'adminStateUp'  => true
];

$healthmonitor = $pool->addHealthMonitor($options);
