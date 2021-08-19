<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;
use OpenStack\Networking\v2\Api;

/**
 * Represents a Neutron v2 LoadBalancer.
 *
 * @property Api $api
 */
class LoadBalancer extends OperatorResource implements Creatable, Retrievable, Updateable, Deletable
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var bool
     */
    public $adminStateUp;

    /**
     * @var string
     */
    public $tenantId;

    /**
     * @var LoadBalancerListener[]
     */
    public $listeners;

    /**
     * @var string
     */
    public $vipAddress;

    /**
     * @var string
     */
    public $vipSubnetId;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $operatingStatus;

    /**
     * @var string
     */
    public $provisioningStatus;

    protected $resourcesKey = 'loadbalancers';
    protected $resourceKey  = 'loadbalancer';

    protected $aliases = [
        'tenant_id'           => 'tenantId',
        'admin_state_up'      => 'adminStateUp',
        'vip_address'         => 'vipAddress',
        'vip_subnet_id'       => 'vipSubnetId',
        'operating_status'    => 'operatingStatus',
        'provisioning_status' => 'provisioningStatus',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'listeners' => new Alias('listeners', LoadBalancerListener::class, true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postLoadBalancer(), $userOptions);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getLoadBalancer(), ['id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putLoadBalancer());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteLoadBalancer());
    }

    /**
     * Add a listener to this load balancer.
     */
    public function addListener(array $userOptions = []): LoadBalancerListener
    {
        $userOptions = array_merge(['loadbalancerId' => $this->id], $userOptions);

        return $this->model(LoadBalancerListener::class)->create($userOptions);
    }

    /**
     * Get stats for this loadbalancer.
     */
    public function getStats(): LoadBalancerStat
    {
        $model = $this->model(LoadBalancerStat::class, ['loadbalancerId' => $this->id]);
        $model->retrieve();

        return $model;
    }

    /**
     * Get the status tree for this loadbalancer.
     */
    public function getStatuses(): LoadBalancerStatus
    {
        $model = $this->model(LoadBalancerStatus::class, ['loadbalancerId' => $this->id]);
        $model->retrieve();

        return $model;
    }
}
