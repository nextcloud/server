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

// Options for pool
$options = [
    'name'               => 'poolName',
    'description'        => 'Load Balancer Pool',
    'listenerId'         => '{listenerId}',
    'adminStateUp'       => true,
    'protocol'           => 'HTTPS',
    'lbAlgorithm'        => 'ROUND_ROBIN',
    'sessionPersistence' => [
        'type'        => 'APP_COOKIE',
        'cookie_name' => 'example_cookie'
    ]
];

// Create the pool
$pool = $networking->createLoadBalancerPool($options);
