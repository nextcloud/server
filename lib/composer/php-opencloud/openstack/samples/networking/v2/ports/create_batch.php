<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => ['id' => '{userId}', 'password' => '{password}'],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$networking = $openstack->networkingV2();

$ports = $networking->createPorts([
    [
        'name'         => 'port1',
        'networkId'    => '{networkId}',
        'adminStateUp' => true
    ],
    [
        'name'         => 'port2',
        'networkId'    => '{networkId}',
        'adminStateUp' => true
    ],
]);
