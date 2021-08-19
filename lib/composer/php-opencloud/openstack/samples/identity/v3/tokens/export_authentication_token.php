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

$openstack = new OpenStack\OpenStack($params);

$identity = $openstack->identityV3();

$token = $identity->generateToken($params);

// Display token expiry
echo sprintf('Token expires at %s'. PHP_EOL, $token->expires->format('c'));

// Save token to file
file_put_contents('token.json', json_encode($token->export()));


// Alternatively, one may persist token to memcache or redis
// Redis and memcache then can purge the entry when token expires.

/**@var \Memcached $memcache */
$memcache->set('token', $token->export(), $token->expires->format('U'));
