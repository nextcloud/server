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
 * Represents a Neutron v2 LoadBalancer Health Monitor.
 *
 * @property Api $api
 */
class LoadBalancerHealthMonitor extends OperatorResource implements Creatable, Retrievable, Updateable, Deletable
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
    public $type;

    /**
     * @var int
     */
    public $delay;

    /**
     * @var int
     */
    public $timeout;

    /**
     * @var int
     */
    public $maxRetries;

    /**
     * @var string
     */
    public $httpMethod;

    /**
     * @var string
     */
    public $urlPath;

    /**
     * @var string
     */
    public $expectedCodes;

    /**
     * @var bool
     */
    public $adminStateUp;

    /**
     * @var string
     */
    public $poolId;

    /**
     * @var LoadBalancerPool[]
     */
    public $pools;

    /**
     * @var string
     */
    public $operatingStatus;

    /**
     * @var string
     */
    public $provisioningStatus;

    protected $resourcesKey = 'healthmonitors';
    protected $resourceKey  = 'healthmonitor';

    protected $aliases = [
        'tenant_id'           => 'tenantId',
        'admin_state_up'      => 'adminStateUp',
        'max_retries'         => 'maxRetries',
        'http_method'         => 'httpMethod',
        'url_path'            => 'urlPath',
        'expected_codes'      => 'expectedCodes',
        'pool_id'             => 'poolId',
        'operating_status'    => 'operatingStatus',
        'provisioning_status' => 'provisioningStatus',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'pools' => new Alias('pools', LoadBalancerPool::class, true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postLoadBalancerHealthMonitor(), $userOptions);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getLoadBalancerHealthMonitor(), ['id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putLoadBalancerHealthMonitor());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteLoadBalancerHealthMonitor());
    }
}
