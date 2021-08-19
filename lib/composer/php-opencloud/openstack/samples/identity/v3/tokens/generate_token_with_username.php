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

$identity = $openstack->identityV3();

// Since usernames will not be unique across an entire OpenStack installation,
// when authenticating with them you must also provide your domain ID. You do
// not have to do this if you authenticate with a user ID.

$token = $identity->generateToken([
    'user' => [
        'name'     => '{username}',
        'password' => '{password}',
        'domain'   => [
            'id' => '{domainId}'
        ]
    ]
]);
