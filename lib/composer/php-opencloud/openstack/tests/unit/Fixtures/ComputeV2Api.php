<?php

namespace OpenStack\Test\Fixtures;

use OpenStack\Common\Api\ApiInterface;

class ComputeV2Api implements ApiInterface
{
    private $idParam = ['type' => 'string', 'required' => true, 'location' => 'url'];

    public function getImage()
    {
        return [
            'method' => 'GET',
            'path'   => 'images/{id}',
            'params' => [self::$idParam]
        ];
    }

    public function postServer()
    {
        return [
            'path' => 'servers',
            'method' => 'POST',
            'jsonKey' => 'server',
            'params' => [
                'removeMetadata' => [
                    'type' => 'object',
                    'properties' => ['type' => 'string'],
                ],
                'securityGroups' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string']
                        ]
                    ],
                    'sentAs' => 'security_groups',
                ],
                'userData' => ['type' => 'string', 'sentAs' => 'user_data'],
                'availabilityZone' => ['type' => 'string', 'sentAs' => 'availability_zone'],
                'imageId' => ['type' => 'string', 'required' => true, 'sentAs' => 'imageRef'],
                'flavorId' => ['type' => 'string', 'required' => true, 'sentAs' => 'flavorRef'],
                'networks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'uuid' => ['type' => 'string'],
                            'port' => ['type' => 'string'],
                        ]
                    ]
                ],
                'name' => ['type' => 'string', 'required' => true],
                'metadata' => [
                    'type'        => 'object',
                    'location'    => 'json',
                    'description' => 'An arbitrary key/value pairing that will be used for metadata.',
                    'properties'  => [
                        'type'        => 'string',
                        'description' => <<<TYPEOTHER
The value being set for your key. Bear in mind that "key" is just an example, you can name it anything.
TYPEOTHER
                    ]
                ],
                'personality' => ['type' => 'string'],
                'blockDeviceMapping' => [
                    'type' => 'array',
                    'sentAs' => 'block_device_mapping_v2',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'configDrive' => ['type' => 'string', 'sentAs' => 'config_drive'],
                            'bootIndex' => ['type' => 'string', 'sentAs' => 'boot_index'],
                            'deleteOnTermination' => ['type' => 'boolean', 'sentAs' => 'delete_on_termination'],
                            'guestFormat' => ['type' => 'string', 'sentAs' => 'guest_format'],
                            'destinationType' => ['type' => 'string', 'sentAs' => 'destination_type'],
                            'sourceType' => ['type' => 'string', 'sentAs' => 'source_type'],
                            'deviceName' => ['type' => 'string', 'sentAs' => 'device_name'],
                        ]
                    ],
                ],
            ]
        ];
    }

    public function test()
    {
        return [
            'method' => 'GET',
            'path'   => 'foo',
            'params' => [
                'id'  => ['type' => 'string', 'location' => 'json'],
                'bar' => ['type' => 'string', 'location' => 'json'],
            ]
        ];
    }

    public function getServers()
    {
        return [
            'method' => 'GET',
            'path'   => 'servers',
            'params' => [
                'changesSince' => ['sentAs' => 'changes-since', 'type' => 'string', 'location' => 'query'],
                'imageId'      => ['sentAs' => 'image', 'type' => 'string', 'location' => 'query'],
                'flavorId'     => ['sentAs' => 'flavor', 'type' => 'string', 'location' => 'query'],
                'name'         => ['type' => 'string', 'location' => 'query'],
                'marker'       => ['type' => 'string', 'location' => 'query'],
                'limit'        => ['type' => 'integer', 'location' => 'query'],
                'status'       => ['type' => 'string', 'location' => 'query'],
                'host'         => ['type' => 'string', 'location' => 'query']
            ],
        ];
    }
}
