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

$domain = $identity->getDomain('{domainId}');

$result = $domain->checkUserRole(['userId' => '{domainUserId}', 'roleId' => '{roleId}']);

if (true === $result) {
    // It exists!
}
