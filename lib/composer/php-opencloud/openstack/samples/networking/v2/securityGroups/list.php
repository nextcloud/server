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

$secGroups = $networking->listSecurityGroups();

foreach ($secGroups as $secGroup) {
    /** @var \OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroup $secGroup */
}
