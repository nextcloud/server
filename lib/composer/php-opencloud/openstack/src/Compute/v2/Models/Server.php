<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\BlockStorage\v2\Models\VolumeAttachment;
use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\HasWaiterTrait;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;
use OpenStack\Common\Transport\Utils;
use OpenStack\Compute\v2\Enum;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroup;
use OpenStack\Networking\v2\Models\InterfaceAttachment;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \OpenStack\Compute\v2\Api $api
 */
class Server extends OperatorResource implements Creatable, Updateable, Deletable, Retrievable, Listable
{
    use HasWaiterTrait;

    /** @var string */
    public $id;

    /** @var string */
    public $ipv4;

    /** @var string */
    public $ipv6;

    /** @var array */
    public $addresses;

    /** @var \DateTimeImmutable */
    public $created;

    /** @var \DateTimeImmutable */
    public $updated;

    /** @var Flavor */
    public $flavor;

    /** @var string */
    public $hostId;

    /** @var string */
    public $hypervisorHostname;

    /** @var Image */
    public $image;

    /** @var array */
    public $links;

    /** @var array */
    public $metadata;

    /** @var string */
    public $name;

    /** @var string */
    public $progress;

    /** @var string */
    public $status;

    /** @var string */
    public $tenantId;

    /** @var string */
    public $userId;

    /** @var string */
    public $adminPass;

    /** @var string */
    public $taskState;

    /** @var string */
    public $powerState;

    /** @var string */
    public $vmState;

    /** @var Fault */
    public $fault;

    /** @var string */
    public $keyName;

    protected $resourceKey  = 'server';
    protected $resourcesKey = 'servers';
    protected $markerKey    = 'id';

    protected $aliases = [
        'block_device_mapping_v2'             => 'blockDeviceMapping',
        'accessIPv4'                          => 'ipv4',
        'accessIPv6'                          => 'ipv6',
        'tenant_id'                           => 'tenantId',
        'key_name'                            => 'keyName',
        'user_id'                             => 'userId',
        'security_groups'                     => 'securityGroups',
        'OS-EXT-STS:task_state'               => 'taskState',
        'OS-EXT-STS:power_state'              => 'powerState',
        'OS-EXT-STS:vm_state'                 => 'vmState',
        'OS-EXT-SRV-ATTR:hypervisor_hostname' => 'hypervisorHostname',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'image'   => new Alias('image', Image::class),
            'flavor'  => new Alias('flavor', Flavor::class),
            'created' => new Alias('created', \DateTimeImmutable::class),
            'updated' => new Alias('updated', \DateTimeImmutable::class),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $userOptions {@see \OpenStack\Compute\v2\Api::postServer}
     */
    public function create(array $userOptions): Creatable
    {
        if (!isset($userOptions['imageId']) && !isset($userOptions['blockDeviceMapping'][0]['uuid'])) {
            throw new \RuntimeException('imageId or blockDeviceMapping.uuid must be set.');
        }

        $response = $this->execute($this->api->postServer(), $userOptions);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->execute($this->api->putServer(), $this->getAttrs(['id', 'name', 'ipv4', 'ipv6']));
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->execute($this->api->deleteServer(), $this->getAttrs(['id']));
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getServer(), $this->getAttrs(['id']));
        $this->populateFromResponse($response);
    }

    /**
     * Changes the root password for a server.
     *
     * @param string $newPassword The new root password
     */
    public function changePassword(string $newPassword)
    {
        $this->execute($this->api->changeServerPassword(), [
            'id'       => $this->id,
            'password' => $newPassword,
        ]);
    }

    /**
     * Issue a resetState call to the server.
     */
    public function resetState()
    {
        $this->execute($this->api->resetServerState(), [
            'id'         => $this->id,
            'resetState' => ['state' => 'active'],
        ]);
    }

    /**
     * Reboots the server.
     *
     * @param string $type The type of reboot that will be performed. Either SOFT or HARD is supported.
     */
    public function reboot(string $type = Enum::REBOOT_SOFT)
    {
        if (!in_array($type, ['SOFT', 'HARD'])) {
            throw new \RuntimeException('Reboot type must either be SOFT or HARD');
        }

        $this->execute($this->api->rebootServer(), [
            'id'   => $this->id,
            'type' => $type,
        ]);
    }

    /**
     * Starts server.
     */
    public function start()
    {
        $this->execute($this->api->startServer(), [
            'id'       => $this->id,
            'os-start' => null,
        ]);
    }

    /**
     * Stops server.
     */
    public function stop()
    {
        $this->execute($this->api->stopServer(), [
            'id'      => $this->id,
            'os-stop' => null,
        ]);
    }

    /**
     * Rebuilds the server.
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::rebuildServer}
     */
    public function rebuild(array $options)
    {
        $options['id'] = $this->id;
        $response      = $this->execute($this->api->rebuildServer(), $options);

        $this->populateFromResponse($response);
    }

    /**
     * Rescues the server.
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::rescueServer}
     */
    public function rescue(array $options): string
    {
        $options['id'] = $this->id;
        $response      = $this->execute($this->api->rescueServer(), $options);

        return Utils::jsonDecode($response)['adminPass'];
    }

    /**
     * Unrescues the server.
     */
    public function unrescue()
    {
        $this->execute($this->api->unrescueServer(), ['unrescue' => null, 'id' => $this->id]);
    }

    /**
     * Resizes the server to a new flavor. Once this operation is complete and server has transitioned
     * to an active state, you will either need to call {@see confirmResize()} or {@see revertResize()}.
     *
     * @param string $flavorId the UUID of the new flavor your server will be based on
     */
    public function resize(string $flavorId)
    {
        $response = $this->execute($this->api->resizeServer(), [
            'id'       => $this->id,
            'flavorId' => $flavorId,
        ]);

        $this->populateFromResponse($response);
    }

    /**
     * Confirms a previous resize operation.
     */
    public function confirmResize()
    {
        $this->execute($this->api->confirmServerResize(), ['confirmResize' => null, 'id' => $this->id]);
    }

    /**
     * Reverts a previous resize operation.
     */
    public function revertResize()
    {
        $this->execute($this->api->revertServerResize(), ['revertResize' => null, 'id' => $this->id]);
    }

    /**
     * Gets the console output of the server.
     *
     * @param int $length the number of lines, by default all lines will be returned
     */
    public function getConsoleOutput(int $length = -1): string
    {
        $definition = -1 == $length ? $this->api->getAllConsoleOutput() : $this->api->getConsoleOutput();

        $response = $this->execute($definition, [
            'os-getConsoleOutput' => new \stdClass(),
            'id'                  => $this->id,
            'length'              => $length,
        ]);

        return Utils::jsonDecode($response)['output'];
    }

    /**
     * Gets a VNC console for a server.
     *
     * @param string $type the type of VNC console: novnc|xvpvnc.
     *                     Defaults to novnc
     */
    public function getVncConsole($type = Enum::CONSOLE_NOVNC): array
    {
        $response = $this->execute($this->api->getVncConsole(), ['id' => $this->id, 'type' => $type]);

        return Utils::jsonDecode($response)['console'];
    }

    /**
     * Gets a RDP console for a server.
     *
     * @param string $type the type of VNC console: rdp-html5 (default)
     */
    public function getRDPConsole($type = Enum::CONSOLE_RDP_HTML5): array
    {
        $response = $this->execute($this->api->getRDPConsole(), ['id' => $this->id, 'type' => $type]);

        return Utils::jsonDecode($response)['console'];
    }

    /**
     * Gets a Spice console for a server.
     *
     * @param string $type the type of VNC console: spice-html5
     */
    public function getSpiceConsole($type = Enum::CONSOLE_SPICE_HTML5): array
    {
        $response = $this->execute($this->api->getSpiceConsole(), ['id' => $this->id, 'type' => $type]);

        return Utils::jsonDecode($response)['console'];
    }

    /**
     * Gets a serial console for a server.
     *
     * @param string $type the type of VNC console: serial
     */
    public function getSerialConsole($type = Enum::CONSOLE_SERIAL): array
    {
        $response = $this->execute($this->api->getSerialConsole(), ['id' => $this->id, 'type' => $type]);

        return Utils::jsonDecode($response)['console'];
    }

    /**
     * Creates an image for the current server.
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::createServerImage}
     */
    public function createImage(array $options)
    {
        $options['id'] = $this->id;
        $this->execute($this->api->createServerImage(), $options);
    }

    /**
     * Iterates over all the IP addresses for this server.
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::getAddressesByNetwork}
     *
     * @return array An array containing to two keys: "public" and "private"
     */
    public function listAddresses(array $options = []): array
    {
        $options['id'] = $this->id;

        $data     = (isset($options['networkLabel'])) ? $this->api->getAddressesByNetwork() : $this->api->getAddresses();
        $response = $this->execute($data, $options);

        return Utils::jsonDecode($response)['addresses'];
    }

    /**
     * Returns Generator for InterfaceAttachment.
     */
    public function listInterfaceAttachments(array $options = []): \Generator
    {
        return $this->model(InterfaceAttachment::class)->enumerate($this->api->getInterfaceAttachments(), ['id' => $this->id]);
    }

    /**
     * Gets an interface attachment.
     *
     * @param string $portId the unique ID of the port
     */
    public function getInterfaceAttachment(string $portId): InterfaceAttachment
    {
        $response = $this->execute($this->api->getInterfaceAttachment(), [
            'id'     => $this->id,
            'portId' => $portId,
        ]);

        return $this->model(InterfaceAttachment::class)->populateFromResponse($response);
    }

    /**
     * Creates an interface attachment.
     *
     * @param array $userOptions {@see \OpenStack\Compute\v2\Api::postInterfaceAttachment}
     */
    public function createInterfaceAttachment(array $userOptions): InterfaceAttachment
    {
        if (!isset($userOptions['networkId']) && !isset($userOptions['portId'])) {
            throw new \RuntimeException('networkId or portId must be set.');
        }

        $response = $this->execute($this->api->postInterfaceAttachment(), array_merge($userOptions, ['id' => $this->id]));

        return $this->model(InterfaceAttachment::class)->populateFromResponse($response);
    }

    /**
     * Detaches an interface attachment.
     */
    public function detachInterface(string $portId)
    {
        $this->execute($this->api->deleteInterfaceAttachment(), [
            'id'     => $this->id,
            'portId' => $portId,
        ]);
    }

    /**
     * Retrieves metadata from the API.
     */
    public function getMetadata(): array
    {
        $response = $this->execute($this->api->getServerMetadata(), ['id' => $this->id]);

        return $this->parseMetadata($response);
    }

    /**
     * Resets all the metadata for this server with the values provided. All existing metadata keys
     * will either be replaced or removed.
     *
     * @param array $metadata {@see \OpenStack\Compute\v2\Api::putServerMetadata}
     */
    public function resetMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->putServerMetadata(), ['id' => $this->id, 'metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * Merges the existing metadata for the server with the values provided. Any existing keys
     * referenced in the user options will be replaced with the user's new values. All other
     * existing keys will remain unaffected.
     *
     * @param array $metadata {@see \OpenStack\Compute\v2\Api::postServerMetadata}
     *
     * @return array
     */
    public function mergeMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->postServerMetadata(), ['id' => $this->id, 'metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * Retrieve the value for a specific metadata key.
     *
     * @param string $key {@see \OpenStack\Compute\v2\Api::getServerMetadataKey}
     *
     * @return mixed
     */
    public function getMetadataItem(string $key)
    {
        $response             = $this->execute($this->api->getServerMetadataKey(), ['id' => $this->id, 'key' => $key]);
        $value                = $this->parseMetadata($response)[$key];
        $this->metadata[$key] = $value;

        return $value;
    }

    /**
     * Remove a specific metadata key.
     *
     * @param string $key {@see \OpenStack\Compute\v2\Api::deleteServerMetadataKey}
     */
    public function deleteMetadataItem(string $key)
    {
        if (isset($this->metadata[$key])) {
            unset($this->metadata[$key]);
        }

        $this->execute($this->api->deleteServerMetadataKey(), ['id' => $this->id, 'key' => $key]);
    }

    /**
     * Add security group to a server (addSecurityGroup action).
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::postSecurityGroup}
     */
    public function addSecurityGroup(array $options): SecurityGroup
    {
        $options['id'] = $this->id;

        $response = $this->execute($this->api->postSecurityGroup(), $options);

        return $this->model(SecurityGroup::class)->populateFromResponse($response);
    }

    /**
     * Add security group to a server (addSecurityGroup action).
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::deleteSecurityGroup}
     */
    public function removeSecurityGroup(array $options)
    {
        $options['id'] = $this->id;
        $this->execute($this->api->deleteSecurityGroup(), $options);
    }

    public function parseMetadata(ResponseInterface $response): array
    {
        return Utils::jsonDecode($response)['metadata'];
    }

    /**
     * Returns Generator for SecurityGroups.
     */
    public function listSecurityGroups(): \Generator
    {
        return $this->model(SecurityGroup::class)->enumerate($this->api->getSecurityGroups(), ['id' => $this->id]);
    }

    /**
     * Returns Generator for VolumeAttachment.
     */
    public function listVolumeAttachments(): \Generator
    {
        return $this->model(VolumeAttachment::class)->enumerate($this->api->getVolumeAttachments(), ['id' => $this->id]);
    }

    /**
     * Attach a volume and returns volume that was attached.
     *
     * @param $volumeId
     */
    public function attachVolume(string $volumeId): VolumeAttachment
    {
        $response = $this->execute($this->api->postVolumeAttachments(), ['id' => $this->id, 'volumeId' => $volumeId]);

        return $this->model(VolumeAttachment::class)->populateFromResponse($response);
    }

    /**
     * Detach a volume.
     *
     * @param $attachmentId
     */
    public function detachVolume(string $attachmentId)
    {
        $this->execute($this->api->deleteVolumeAttachments(), ['id' => $this->id, 'attachmentId' => $attachmentId]);
    }
}
