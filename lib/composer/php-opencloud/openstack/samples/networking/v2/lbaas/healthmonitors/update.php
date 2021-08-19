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

// Get the healthmonitor
$healthmonitor = $networking->getLoadBalancerHealthMonitor('{healthmonitorId}');

$healthmonitor->delay = 30;
$healthmonitor->timeout = 60;
$healthmonitor->httpMethod = 'POST';
$healthmonitor->update();
