<?php

use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroup;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule;

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

$server = $compute->getServer(['id' => '{serverId}']);

$securityGroups = $server->listSecurityGroups();

foreach ($securityGroups as $securityGroup) {
    /**@var SecurityGroup $securityGroup */
    $rules = $securityGroup->securityGroupRules;

    foreach ($rules as $rule) {
        /**@var SecurityGroupRule $rule */
    }
}
