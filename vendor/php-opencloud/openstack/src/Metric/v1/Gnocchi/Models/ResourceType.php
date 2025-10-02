<?php

declare(strict_types=1);

namespace OpenStack\Metric\v1\Gnocchi\Models;

use OpenStack\Common\Resource\OperatorResource;

class ResourceType extends OperatorResource
{
    /** @var string */
    public $state;

    /** @var string */
    public $name;

    /** @var object */
    public $attributes;
}
