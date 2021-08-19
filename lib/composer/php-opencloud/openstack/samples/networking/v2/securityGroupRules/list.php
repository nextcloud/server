<?php

use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroup;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Service;

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

/** @var Service $networkingExtSecGroup */
$networkingExtSecGroup = $openstack->networkingV2ExtSecGroups();

//List all security group rules
foreach ($networkingExtSecGroup->listSecurityGroupRules() as $rule) {
    /** @var SecurityGroupRule $rule */
}

/** @var SecurityGroup $securityGroup */
$securityGroup = $networkingExtSecGroup->getSecurityGroup(['id' => '{uuid}']);

//List rules belong to a security group
foreach ($securityGroup->securityGroupRules as $rule) {
    /**@var SecurityGroupRule $rule */
}
