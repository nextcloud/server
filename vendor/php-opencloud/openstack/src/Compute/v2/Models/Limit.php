<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\AbstractResource;

/**
 * Represents a Compute v2 Limit.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class Limit extends AbstractResource
{
    /** @var object */
    public $rate;

    /** @var object */
    public $absolute;

    protected $resourceKey = 'limits';
}
