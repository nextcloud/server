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

$result = $project->checkUserRole([
    'userId' => '{projectUserId}',
    'roleId' => '{roleId}',
]);

if (true === $result) {
}
