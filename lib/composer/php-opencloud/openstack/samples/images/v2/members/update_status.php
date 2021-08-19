<?php

require 'vendor/autoload.php';

use OpenStack\Images\v2\Models\Member;

$openstack = new OpenStack\OpenStack([
    'authUrl' => '{authUrl}',
    'region'  => '{region}',
    'user'    => ['id' => '{userId}', 'password' => '{password}'],
    'scope'   => ['project' => ['id' => '{projectId}']]
]);

$openstack->imagesV2()
    ->getImage('{imageId}')
    ->getMember('{projectId}')
    ->updateStatus(Member::STATUS_ACCEPTED);
