<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenStack\Common\Transport\Utils as TransportUtils;
use OpenStack\Identity\v2\Service;
use OpenStack\OpenStack;

$authUrl = 'https://keystone.example.com:5000/v2.0';

$httpClient = new Client([
    'base_uri' => TransportUtils::normalizeUrl($authUrl),
    'handler'  => HandlerStack::create(),
]);

$options = [
    'authUrl'         => $authUrl,
    'region'          => 'RegionOne',
    'username'        => 'foo',
    'password'        => 'bar',
    'tenantName'      => 'baz',
    'identityService' => Service::factory($httpClient),
];

/** @var OpenStack $openstack */
$openstack = new OpenStack($options);

/** @var \OpenStack\Compute\v2\Models\Server[] $servers */
$servers = $openstack->computeV2()->listServers();
