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

$options = [
    'name'         => 'loadBalancerName',
    'description'  => 'My LoadBalancer',
    'vipSubnetId'  => '{subnetId}',
    'adminStateUp' => true,
];

// Create the loadbalancer
$lb = $networking->createLoadBalancer($options);
