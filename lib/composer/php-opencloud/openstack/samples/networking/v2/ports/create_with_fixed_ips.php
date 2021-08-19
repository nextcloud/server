<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => ['id' => '{userId}', 'password' => '{password}'],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$networking = $openstack->networkingV2();

$port = $networking->createPort([
    'name'         => 'portName',
    'networkId'    => '{networkId}',
    'adminStateUp' => true,
    'fixedIps' => [
        [
            'ipAddress' => '192.168.199.100'
        ],
        [
            'ipAddress' => '192.168.199.200'
        ]
    ]
]);
