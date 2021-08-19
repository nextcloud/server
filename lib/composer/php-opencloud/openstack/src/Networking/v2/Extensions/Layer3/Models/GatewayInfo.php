<?php

namespace OpenStack\Networking\v2\Extensions\Layer3\Models;

use OpenStack\Common\Resource\AbstractResource;
use OpenStack\Common\Resource\Alias;

class GatewayInfo extends AbstractResource
{
    /** @var string */
    public $networkId;

    /** @var string */
    public $enableSnat;

    /** @var FixedIp[] */
    public $fixedIps;

    protected $aliases = [
        'network_id'  => 'networkId',
        'enable_snat' => 'enableSnat',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'external_fixed_ips' => new Alias('fixedIps', FixedIp::class, true),
        ];
    }
}
