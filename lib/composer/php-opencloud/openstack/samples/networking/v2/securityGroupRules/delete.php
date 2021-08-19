<?php

require 'vendor/autoload.php';

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => [
        'id'       => '{userId}',
        'password' => '{password}'
    ],
    'scope' => ['project' => ['id' => '{projectId}']]
]);

/** @var \OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule $rule */
$rule = $openstack->networkingV2ExtSecGroups()
    ->getSecurityGroupRule('{id}');

$rule->delete();
