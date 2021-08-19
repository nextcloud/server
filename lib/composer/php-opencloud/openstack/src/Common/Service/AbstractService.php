<?php

declare(strict_types=1);

namespace OpenStack\Common\Service;

use OpenStack\Common\Api\OperatorTrait;

/**
 * Represents the top-level abstraction of a service.
 */
abstract class AbstractService implements ServiceInterface
{
    use OperatorTrait;
}
