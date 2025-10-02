<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2;

use OpenStack\Common\Api\AbstractParams;

class Params extends AbstractParams
{
    public function urlId(string $type): array
    {
        return array_merge(parent::id($type), [
            'required'   => true,
            'location'   => self::URL,
            'documented' => false,
        ]);
    }

    public function resetState(): array
    {
        return [
            'type'       => self::OBJECT_TYPE,
            'location'   => self::JSON,
            'sentAs'     => 'os-resetState',
            'required'   => true,
            'properties' => [
                'state' => ['type' => self::STRING_TYPE],
            ],
        ];
    }

    public function minDisk(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::QUERY,
            'description' => 'Return flavors that have a minimum disk space in GB.',
        ];
    }

    public function minRam(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::QUERY,
            'description' => 'Return flavors that have a minimum RAM size in GB.',
        ];
    }

    public function flavorName(): array
    {
        return [
            'location'    => self::QUERY,
            'description' => 'Return images which match a certain name.',
        ];
    }

    public function filterChangesSince($type)
    {
        return [
            'location'    => self::QUERY,
            'sentAs'      => 'changes-since',
            'description' => sprintf(
                'Return %ss which have been changed since a certain time. This value needs to be in an ISO 8601 format.',
                $type
            ),
        ];
    }

    public function flavorServer(): array
    {
        return [
            'location'    => self::QUERY,
            'description' => sprintf('Return images which are associated with a server. This value needs to be in a URL format.'),
        ];
    }

    public function filterStatus(string $type): array
    {
        return [
            'location'    => self::QUERY,
            'description' => sprintf(
                'Return %ss that have a particular status, such as "ACTIVE".',
                $type
            ),
        ];
    }

    public function flavorType(): array
    {
        return [
            'location'    => self::QUERY,
            'description' => 'Return images that are of a particular type, such as "snapshot" or "backup".',
        ];
    }

    public function key(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::URL,
            'required'    => true,
            'description' => 'The specific metadata key you are interacting with',
        ];
    }

    public function ipv4(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'accessIPv4',
            'description' => 'The IP address (version 4) of the remote resource',
        ];
    }

    public function ipv6(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'accessIPv6',
            'description' => 'The IP address (version 6) of the remote resource',
        ];
    }

    public function imageId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'imageRef',
            'description' => 'The UUID of the image to use for your server instance. This is not required in case of boot from volume. In all other cases it is required and must be a valid UUID',
        ];
    }

    public function rescueImageId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'rescue_image_ref',
            'description' => 'The image reference to use to rescue your server instance. Specify the image reference by ID or full URL. If you omit an image reference, default is the base image reference',
        ];
    }

    public function flavorId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'flavorRef',
            'description' => 'The unique ID of the flavor that this server will be based on',
        ];
    }

    public function networkId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'net_id',
            'description' => 'The unique ID of a network',
        ];
    }

    public function portId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'port_id',
            'description' => 'The unique ID of a port',
        ];
    }

    public function tag(): array
    {
        return [
            'type' => self::STRING_TYPE,
        ];
    }

    public function fixedIpAddresses(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'sentAs'      => 'fixed_ips',
            'description' => 'A list of ip addresses which this interface will be associated with',
            'items'       => [
                'type'       => self::OBJECT_TYPE,
                'properties' => ['ip_address' => ['type' => self::STRING_TYPE]],
            ],
        ];
    }

    public function metadata(): array
    {
        return [
            'type'        => self::OBJECT_TYPE,
            'location'    => self::JSON,
            'required'    => true,
            'description' => 'An arbitrary key/value pairing that will be used for metadata.',
            'properties'  => [
                'type'        => self::STRING_TYPE,
                'description' => <<<TYPEOTHER
The value being set for your key. Bear in mind that "key" is just an example, you can name it anything.
TYPEOTHER
            ],
        ];
    }

    public function personality(): array
    {
        return [
            'type'  => self::ARRAY_TYPE,
            'items' => [
                'type'       => self::OBJECT_TYPE,
                'properties' => [
                    'path' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'The path, on the filesystem, where the personality file will be placed',
                    ],
                    'contents' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'Base64-encoded content of the personality file',
                    ],
                ],
            ],
            'description' => <<<EOL
File path and contents (text only) to inject into the server at launch. The maximum size of the file path data is 255
bytes. The maximum limit refers to the number of bytes in the decoded data and not the number of characters in the
encoded data.
EOL
        ];
    }

    public function securityGroups(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'sentAs'      => 'security_groups',
            'description' => 'A list of security group objects which this server will be associated with',
            'items'       => [
                'type'       => self::OBJECT_TYPE,
                'properties' => ['name' => $this->name('security group')],
            ],
        ];
    }

    public function userData(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'user_data',
            'description' => 'Configuration information or scripts to use upon launch. Must be Base64 encoded.',
        ];
    }

    public function availabilityZone(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'availability_zone',
            'description' => 'The availability zone in which to launch the server.',
        ];
    }

    public function networks(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'description' => <<<EOT
A list of network objects which this server will be associated with. By default, the server instance is provisioned
with all isolated networks for the tenant. Optionally, you can create one or more NICs on the server.

To provision the server instance with a NIC for a network, specify the UUID of the network in the uuid attribute in a
networks object.

To provision the server instance with a NIC for an already existing port, specify the port-id in the port attribute in
a networks object.
EOT
            ,
            'items' => [
                'type'       => self::OBJECT_TYPE,
                'properties' => [
                    'uuid' => [
                        'type'        => self::STRING_TYPE,
                        'description' => <<<EOL
To provision the server instance with a NIC for a network, specify the UUID of the network in the uuid attribute in a
networks object. Required if you omit the port attribute
EOL
                    ],
                    'port' => [
                        'type'        => self::STRING_TYPE,
                        'description' => <<<EOL
To provision the server instance with a NIC for an already existing port, specify the port-id in the port attribute in
a networks object. The port status must be DOWN. Required if you omit the uuid attribute.
EOL
                    ],
                ],
            ],
        ];
    }

    public function blockDeviceMapping(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'sentAs'      => 'block_device_mapping_v2',
            'description' => <<<EOL
Enables booting the server from a volume when additional parameters are given. If specified, the volume status must be
available, and the volume attach_status in OpenStack Block Storage DB must be detached.
EOL
            ,
            'items' => [
                'type'       => self::OBJECT_TYPE,
                'properties' => [
                    'uuid' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'The unique ID for the volume which the server is to be booted from.',
                    ],
                    'bootIndex' => [
                        'type'        => self::INT_TYPE,
                        'sentAs'      => 'boot_index',
                        'description' => 'Indicates a number designating the boot order of the device. Use -1 for the boot volume, choose 0 for an attached volume.',
                    ],
                    'deleteOnTermination' => [
                        'type'        => self::BOOL_TYPE,
                        'sentAs'      => 'delete_on_termination',
                        'description' => 'To delete the boot volume when the server stops, specify true. Otherwise, specify false.',
                    ],
                    'guestFormat' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'guest_format',
                        'description' => 'Specifies the guest server disk file system format, such as "ephemeral" or "swap".',
                    ],
                    'destinationType' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'destination_type',
                        'description' => 'Describes where the volume comes from. Choices are "local" or "volume". When using "volume" the volume ID',
                    ],
                    'sourceType' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'source_type',
                        'description' => 'Describes the volume source type for the volume. Choices are "blank", "snapshot", "volume", or "image".',
                    ],
                    'deviceName' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'device_name',
                        'description' => 'Describes a path to the device for the volume you want to use to boot the server.',
                    ],
                    'volumeSize' => [
                        'type'        => self::INT_TYPE,
                        'sentAs'      => 'volume_size',
                        'description' => 'Size of the volume created if we are doing vol creation',
                    ],
                    'volumeType' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'volume_type',
                        'description' => 'The type of volume which the compute service will create and attach to the server.',
                    ],
                ],
            ],
        ];
    }

    public function filterHost(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'description' => '',
        ];
    }

    public function filterName(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'description' => '',
        ];
    }

    public function filterFlavor(): array
    {
        return [
            'sentAs'      => 'flavor',
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'description' => '',
        ];
    }

    public function filterImage(): array
    {
        return [
            'sentAs'      => 'image',
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'description' => '',
        ];
    }

    public function password(): array
    {
        return [
            'sentAs'      => 'adminPass',
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'required'    => true,
            'description' => '',
        ];
    }

    public function rebootType(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'required'    => true,
            'description' => '',
        ];
    }

    public function nullAction(): array
    {
        return [
            'type'     => self::NULL_TYPE,
            'location' => self::JSON,
            'required' => true,
        ];
    }

    public function networkLabel(): array
    {
        return [
            'type'     => self::STRING_TYPE,
            'location' => self::URL,
            'required' => true,
        ];
    }

    public function keyName(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => false,
            'sentAs'      => 'key_name',
            'description' => 'The key name',
        ];
    }

    public function keypairPublicKey(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'public_key',
            'location'    => self::JSON,
            'description' => 'The public ssh key to import. If you omit this value, a key is generated.',
        ];
    }

    public function keypairName(): array
    {
        return [
            'location' => self::URL,
        ];
    }

    public function userId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'user_id',
            'location'    => self::QUERY,
            'description' => 'This allows administrative users to operate key-pairs of specified user ID. Requires micro version 2.10.',
        ];
    }

    public function keypairUserId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'user_id',
            'location'    => self::JSON,
            'description' => 'This allows administrative users to upload keys for other users than themselves. Requires micro version 2.10.',
        ];
    }

    public function keypairType(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'description' => 'The type of the keypair. Allowed values are ssh or x509. Require micro version 2.2.',
        ];
    }

    public function flavorRam(): array
    {
        return [
            'type'     => self::INT_TYPE,
            'location' => self::JSON,
        ];
    }

    public function flavorVcpus(): array
    {
        return [
            'type'     => self::INT_TYPE,
            'location' => self::JSON,
        ];
    }

    public function flavorDisk(): array
    {
        return [
            'type'     => self::INT_TYPE,
            'location' => self::JSON,
        ];
    }

    public function flavorSwap(): array
    {
        return [
            'type'     => self::INT_TYPE,
            'location' => self::JSON,
        ];
    }

    public function volumeId(): array
    {
        return [
            'type'     => self::STRING_TYPE,
            'location' => self::JSON,
        ];
    }

    public function attachmentId(): array
    {
        return [
            'type'     => self::STRING_TYPE,
            'location' => self::URL,
            'required' => true,
        ];
    }

    public function consoleType(): array
    {
        return [
            'type'     => self::STRING_TYPE,
            'location' => self::JSON,
            'required' => true,
        ];
    }

    public function consoleLogLength(): array
    {
        return [
            'type'     => self::INT_TYPE,
            'location' => self::JSON,
            'required' => false,
        ];
    }

    public function emptyObject(): array
    {
        return [
            'type' => self::OBJECT_TYPE,
        ];
    }

    protected function quotaSetLimit($sentAs, $description): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => $sentAs,
            'description' => $description,
        ];
    }

    public function quotaSetLimitForce(): array
    {
        return [
            'type'        => self::BOOLEAN_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'force',
            'description' => 'You can force the update even if the quota has already been used and the reserved quota exceeds the new quota',
        ];
    }

    public function quotaSetLimitInstances(): array
    {
        return $this->quotaSetLimit('instances', 'The number of allowed instances for each tenant.');
    }

    public function quotaSetLimitCores(): array
    {
        return $this->quotaSetLimit('cores', 'The number of allowed instance cores for each tenant.');
    }

    public function quotaSetLimitFixedIps(): array
    {
        return $this->quotaSetLimit('fixed_ips', 'The number of allowed fixed IP addresses for each tenant. Must be equal to or greater than the number of allowed instances.');
    }

    public function quotaSetLimitFloatingIps(): array
    {
        return $this->quotaSetLimit('floating_ips', 'The number of allowed floating IP addresses for each tenant.');
    }

    public function quotaSetLimitInjectedFileContentBytes(): array
    {
        return $this->quotaSetLimit('injected_file_content_bytes', 'The number of allowed bytes of content for each injected file.');
    }

    public function quotaSetLimitInjectedFilePathBytes(): array
    {
        return $this->quotaSetLimit('injected_file_path_bytes', 'The number of allowed bytes for each injected file path.');
    }

    public function quotaSetLimitInjectedFiles(): array
    {
        return $this->quotaSetLimit('injected_files', 'The number of allowed injected files for each tenant.');
    }

    public function quotaSetLimitKeyPairs(): array
    {
        return $this->quotaSetLimit('key_pairs', 'The number of allowed key pairs for each user.');
    }

    public function quotaSetLimitMetadataItems(): array
    {
        return $this->quotaSetLimit('metadata_items', 'The number of allowed metadata items for each instance.');
    }

    public function quotaSetLimitRam(): array
    {
        return $this->quotaSetLimit('ram', 'The amount of allowed instance RAM (in MB) for each tenant.');
    }

    public function quotaSetLimitSecurityGroupRules(): array
    {
        return $this->quotaSetLimit('security_group_rules', 'The number of allowed rules for each security group.');
    }

    public function quotaSetLimitSecurityGroups(): array
    {
        return $this->quotaSetLimit('security_groups', 'The number of allowed security groups for each tenant.');
    }

    public function quotaSetLimitServerGroups(): array
    {
        return $this->quotaSetLimit('server_groups', 'The number of allowed server groups for each tenant.');
    }

    public function quotaSetLimitServerGroupMembers(): array
    {
        return $this->quotaSetLimit('server_group_members', 'The number of allowed members for each server group.');
    }
}
