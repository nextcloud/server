<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Transport\Utils;

/**
 * Represents Neutron v2 LoadBalancer Stats.
 *
 * @property Api $api
 */
class LoadBalancerStatus extends OperatorResource implements Retrievable
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $loadbalancerId;

    /**
     * @var string
     */
    public $operatingStatus;

    /**
     * @var string
     */
    public $provisioningStatus;

    /**
     * @var LoadBalancerListener[]
     */
    public $listeners;

    protected $resourceKey = 'statuses';

    protected $aliases = [
        'loadbalancer_id'     => 'loadbalancerId',
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
    public function retrieve()
    {
        $response = $this->execute($this->api->getLoadBalancerStatuses(), ['loadbalancerId' => (string) $this->loadbalancerId]);
        $json     = Utils::jsonDecode($response);
        $this->populateFromArray($json[$this->resourceKey]['loadbalancer']);
    }
}
