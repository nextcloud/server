<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\HasWaiterTrait;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * @property \OpenStack\Networking\v2\Api $api
 */
class Port extends OperatorResource implements Creatable, Updateable, Deletable, Listable, Retrievable
{
    use HasWaiterTrait;

    /**
     * The port status. Value is ACTIVE or DOWN.
     *
     * @var string
     */
    public $status;

    /**
     * The port name.
     *
     * @var string
     */
    public $name;

    /**
     * A set of zero or more allowed address pairs. An address pair consists of an IP address and MAC address.
     *
     * @var array
     */
    public $allowedAddressPairs;

    /**
     * The administrative state of the port, which is up (true) or down (false).
     *
     * @var bool
     */
    public $adminStateUp;

    /**
     * The UUID of the attached network.
     *
     * @var string
     */
    public $networkId;

    /**
     * The UUID of the tenant who owns the network. Only administrative users can specify a tenant UUID other than
     * their own.
     *
     * @var string
     */
    public $tenantId;

    /**
     * A set of zero or more extra DHCP option pairs. An option pair consists of an option value and name.
     *
     * @var array
     */
    public $extraDhcpOpts;

    /**
     * The UUID of the entity that uses this port. For example, a DHCP agent.
     *
     * @var string
     */
    public $deviceOwner;

    /**
     * The MAC address of the port.
     *
     * @var string
     */
    public $macAddress;

    /**
     * The IP addresses for the port. Includes the IP address and UUID of the subnet.
     *
     * @var array
     */
    public $fixedIps;

    /**
     * The UUID of the port.
     *
     * @var string
     */
    public $id;

    /**
     * The UUIDs of any attached security groups.
     *
     * @var array
     */
    public $securityGroups;

    /**
     * The UUID of the device that uses this port. For example, a virtual server.
     *
     * @var string
     */
    public $deviceId;

    /**
     * The port security status. The status is enabled (true) or disabled (false).
     *
     * @var bool
     */
    public $portSecurityEnabled;

    protected $aliases = [
        'port_security_enabled' => 'portSecurityEnabled',
        'admin_state_up'        => 'adminStateUp',
        'display_name'          => 'displayName',
        'network_id'            => 'networkId',
        'tenant_id'             => 'tenantId',
        'device_owner'          => 'deviceOwner',
        'mac_address'           => 'macAddress',
        'port_id'               => 'portId',
        'security_groups'       => 'securityGroups',
        'device_id'             => 'deviceId',
        'fixed_ips'             => 'fixedIps',
        'allowed_address_pairs' => 'allowedAddressPairs',
    ];

    protected $resourceKey  = 'port';
    protected $resourcesKey = 'ports';

    /**
     * {@inheritdoc}
     */
    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postSinglePort(), $userOptions);

        return $this->populateFromResponse($response);
    }

    public function bulkCreate(array $userOptions): array
    {
        $response = $this->execute($this->api->postMultiplePorts(), ['ports' => $userOptions]);

        return $this->extractMultipleInstances($response);
    }

    public function retrieve()
    {
        $response = $this->execute($this->api->getPort(), ['id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    public function update()
    {
        $response = $this->executeWithState($this->api->putPort());
        $this->populateFromResponse($response);
    }

    public function delete()
    {
        $this->executeWithState($this->api->deletePort());
    }
}
