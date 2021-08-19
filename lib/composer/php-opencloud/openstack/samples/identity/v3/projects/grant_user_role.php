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

$identity = $openstack->identityV3(['region' => '{region}']);

$project = $identity->getProject('{id}');

$project->grantUserRole([
    'userId' => '{projectUserId}',
    'roleId' => '{roleId}',
]);
