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

$compute = $openstack->computeV2(['region' => '{region}']);

/**@var OpenStack\Compute\v2\Models\Server $server */
$server = $compute->getServer([
    'id' => '{serverId}',
]);

$server->removeSecurityGroup(['name' => '{secGroupName}']);
