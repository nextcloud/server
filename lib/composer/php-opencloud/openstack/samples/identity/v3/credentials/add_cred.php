<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$identity = $openstack->identityV3(['region' => '{region}']);

$credential = $identity->createCredential([
    'blob'      => '{blob}',
    'projectId' => '{projectId}',
    'type'      => '{type}',
    'userId'    => '{userId}'
]);
