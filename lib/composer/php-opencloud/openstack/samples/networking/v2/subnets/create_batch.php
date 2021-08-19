<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope' => [
        'project' => [
            'id' => '{projectId}'
        ]
    ]
]);

$networking = $openstack->networkingV2();

$options = [
    [
      'name'        => '{subnetName1}',
      'networkId'   => '{networkId1}',
      'ipVersion'   => 4,
      'cidr'        => '192.168.199.0/24',
    ],
    [
      'name'        => '{subnetName2}',
      'networkId'   => '{networkId2}',
      'ipVersion'   => 4,
      'cidr'        => '10.56.4.0/22',
    ],
];

$subnets = $networking->createSubnets($options);
