<?php

require 'vendor/autoload.php';

$params = [
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
];

$token = json_decode(file_get_contents('token.json'), true);

// Inject cached token to params if token is still fresh
if ((new \DateTimeImmutable($token['expires_at'])) > (new \DateTimeImmutable('now'))) {
    $params['cachedToken'] = $token;
}

$openstack = new OpenStack\OpenStack($params);
