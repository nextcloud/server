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

/**@var OpenStack\Compute\v2\Models\Server $server */
$server = $compute->getServer(['id' => '{serverId}']);

//Must detach by volumeAttachment id
$server->detachVolume('{volumeAttachmentId}');
