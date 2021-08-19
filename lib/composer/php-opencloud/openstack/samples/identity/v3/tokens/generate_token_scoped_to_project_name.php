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
        'project' => ['id' => '{projectId}']
    ]
]);

$identity = $openstack->identityV3();

// Since project names will not be unique across an entire OpenStack installation,
// when authenticating with them you must also provide your domain ID. You do
// not have to do this if you authenticate with a project ID.

$token = $identity->generateToken([
    'user' => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope' => [
        'project' => [
            'name' => '{projectName}',
            'domain' => [
                'id' => '{domainId}'
            ]
        ]
    ]
]);
