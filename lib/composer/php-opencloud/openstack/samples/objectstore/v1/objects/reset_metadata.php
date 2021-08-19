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

$openstack->objectStoreV1()
          ->getContainer('{containerName}')
          ->getObject('{objectName}')
          ->resetMetadata([
              '{key_1}' => '{val_1}',
              '{key_2}' => '{val_2}',
          ]);
