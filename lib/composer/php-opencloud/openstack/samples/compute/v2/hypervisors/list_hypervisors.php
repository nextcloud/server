<?php

use OpenStack\Compute\v2\Models\Hypervisor;

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

$hypervisors = $compute->listHypervisors();

foreach ($hypervisors as $hypervisor) {
    /**@var Hypervisor $hypervisor*/
}
