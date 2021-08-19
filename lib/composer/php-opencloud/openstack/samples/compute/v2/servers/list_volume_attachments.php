<?php

use OpenStack\BlockStorage\v2\Models\VolumeAttachment;
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

foreach ($server->listVolumeAttachments() as $volumeAttachment) {
    /**@var VolumeAttachment $volumeAttachment*/
}
