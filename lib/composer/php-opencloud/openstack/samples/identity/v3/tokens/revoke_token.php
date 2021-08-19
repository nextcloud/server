<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'name'     => '{username}',
        'password' => '{password}',
        'domain'   => ['id' => '{domainId}']
    ],
    'scope' => [
        'project' => ['id' => '{projectId}']
    ]
]);

$identity = $openstack->identityV3(['region' => '{region}']);

$identity->revokeToken('{tokenId}');
