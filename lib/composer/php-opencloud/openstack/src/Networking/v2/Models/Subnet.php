<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * Represents a Networking v2 Network.
 *
 * @property \OpenStack\Networking\v2\Api $api
 */
class Subnet extends OperatorResource implements Listable, Retrievable, Creatable, Deletable, Updateable
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var bool */
    public $enableDhcp;

    /** @var string */
    public $networkId;

    /** @var array */
    public $dnsNameservers;

    /** @var array */
    public $allocationPools;

    /** @var array */
    public $hostRoutes;

    /** @var int */
    public $ipVersion;

    /** @var string */
    public $gatewayIp;

    /** @var string */
    public $cidr;

    /** @var string */
    public $tenantId;

    /** @var array */
    public $links;

    protected $aliases = [
        'enable_dhcp'      => 'enableDhcp',
        'network_id'       => 'networkId',
        'dns_nameservers'  => 'dnsNameservers',
        'allocation_pools' => 'allocationPools',
        'host_routes'      => 'hostRoutes',
        'ip_version'       => 'ipVersion',
        'gateway_ip'       => 'gatewayIp',
        'tenant_id'        => 'tenantId',
    ];

    protected $resourceKey  = 'subnet';
    protected $resourcesKey = 'subnets';

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getSubnet(), ['id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * Creates multiple subnets in a single request.
     *
     * @param array $data {@see \OpenStack\Networking\v2\Api::postSubnets}
     *
     * @return Subnet[]
     */
    public function bulkCreate(array $data): array
    {
        $response = $this->execute($this->api->postSubnets(), ['subnets' => $data]);

        return $this->extractMultipleInstances($response);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Networking\v2\Api::postSubnet}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postSubnet(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putSubnet());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteSubnet());
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttrs(array $keys)
    {
        $output = parent::getAttrs($keys);

        if ('' === $this->gatewayIp) {
            $output['gatewayIp'] = null;
        }

        return $output;
    }
}
