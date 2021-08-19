<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;

/**
 * Represents Neutron v2 LoadBalancer Stats.
 *
 * @property Api $api
 */
class LoadBalancerStat extends OperatorResource implements Retrievable
{
    /**
     * @var string
     */
    public $bytesIn;

    /**
     * @var string
     */
    public $bytesOut;

    /**
     * @var int
     */
    public $totalConnections;

    /**
     * @var int
     */
    public $activeConnections;

    /**
     * @var string
     */
    public $loadbalancerId;

    protected $resourceKey = 'stats';

    protected $aliases = [
        'bytes_in'           => 'bytesIn',
        'bytes_out'          => 'bytesOut',
        'total_connections'  => 'totalConnections',
        'active_connections' => 'activeConnections',
        'loadbalancer_id'    => 'loadbalancerId',
    ];

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getLoadBalancerStats(), ['loadbalancerId' => (string) $this->loadbalancerId]);
        $this->populateFromResponse($response);
    }
}
