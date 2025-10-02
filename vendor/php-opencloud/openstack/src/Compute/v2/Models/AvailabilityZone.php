<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;

/**
 * Represents a Compute v2 AvailabilityZone.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class AvailabilityZone extends OperatorResource implements Listable
{
    /** @var string */
    public $name;

    /** @var string */
    public $state;

    /** @var array */
    public $hosts;

    protected $resourceKey  = 'availabilityZoneInfo';
    protected $resourcesKey = 'availabilityZoneInfo';

    protected $aliases = [
      'zoneName'  => 'name',
      'zoneState' => 'state',
    ];
}
