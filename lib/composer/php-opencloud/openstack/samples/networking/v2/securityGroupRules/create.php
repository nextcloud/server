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

$networking = $openstack->networkingV2ExtSecGroups();

/** @var \OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule $rule */
$rule = $networking->createSecurityGroupRule([
    "direction"       => "ingress",
    "ethertype"       => "IPv4",
    "portRangeMin"    => "80",
    "portRangeMax"    => "80",
    "protocol"        => "tcp",
    "remoteGroupId"   => "{groupId}",
    "securityGroupId" => "{secGroupId}",
]);
