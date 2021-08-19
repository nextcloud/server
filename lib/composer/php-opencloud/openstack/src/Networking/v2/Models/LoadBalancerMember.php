<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;
use OpenStack\Networking\v2\Api;

/**
 * Represents a Neutron v2 LoadBalancer member.
 *
 * @property Api $api
 */
class LoadBalancerMember extends OperatorResource implements Creatable, Retrievable, Updateable, Deletable
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $tenantId;

    /**
     * @var string
     */
    public $address;

    /**
     * @var int
     */
    public $protocolPort;

    /**
     * @var int
     */
    public $weight;

    /**
     * @var string
     */
    public $subnetId;

    /**
     * @var string
     */
    public $poolId;

    /**
     * @var bool
     */
    public $adminStateUp;

    /**
     * @var string
     */
    public $operatingStatus;

    /**
     * @var string
     */
    public $provisioningStatus;

    protected $resourcesKey = 'members';
    protected $resourceKey  = 'member';

    protected $aliases = [
        'tenant_id'           => 'tenantId',
        'admin_state_up'      => 'adminStateUp',
        'protocol_port'       => 'protocolPort',
        'subnet_id'           => 'subnetId',
        'pool_id'             => 'poolId',
        'operating_status'    => 'operatingStatus',
        'provisioning_status' => 'provisioningStatus',
    ];

    /**
     * {@inheritdoc}
     */
    public function create(array $userOptions): Creatable
    {
        $userOptions = array_merge(['poolId' => $this->poolId], $userOptions);
        $response    = $this->execute($this->api->postLoadBalancerMember(), $userOptions);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getLoadBalancerMember(), ['poolId' => (string) $this->poolId, 'id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putLoadBalancerMember(), ['poolId' => (string) $this->poolId, 'id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteLoadBalancerMember(), ['poolId' => (string) $this->poolId, 'id' => (string) $this->id]);
    }
}
