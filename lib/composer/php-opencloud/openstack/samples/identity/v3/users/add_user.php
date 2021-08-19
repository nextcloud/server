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

$identity = $openstack->identityV3();

$user = $identity->createUser([
    'defaultProjectId' => '{defaultProjectId}',
    'description'      => '{description}',
    'domainId'         => '{domainId}',
    'email'            => '{email}',
    'enabled'          => true,
    'name'             => '{name}',
    'password'         => '{userPass}'
]);
