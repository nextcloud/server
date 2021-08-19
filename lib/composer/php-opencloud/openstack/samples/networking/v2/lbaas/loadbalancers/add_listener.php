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

// Get the loadbalancer
$lb = $networking->getLoadBalancer('{loadbalancerId}');

// Options for listener
$options = [
    'name'            => 'listenerName',
    'description'     => 'Load Balancer Listener',
    'adminStateUp'    => true,
    'protocol'        => 'HTTPS',
    'protocolPort'    => 443,
    'connectionLimit' => 1000
];

$listener = $lb->addListener($options);
