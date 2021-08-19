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

// Health Monitor options
$options = [
    'poolId'        => '{poolId}',
    'type'          => 'HTTPS',
    'delay'         => 1,
    'timeout'       => 1,
    'httpMethod'    => 'GET',
    'urlPath'       => '/',
    'expectedCodes' => '200,201,302',
    'maxRetries'    => 5,
    'adminStateUp'  => true
];

// Create the listener
$healthmonitor = $networking->createLoadBalancerHealthMonitor($options);
