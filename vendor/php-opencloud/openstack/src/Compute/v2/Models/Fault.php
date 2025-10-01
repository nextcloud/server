<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\AbstractResource;

/**
 * Represents a Compute v2 Fault.
 */
class Fault extends AbstractResource
{
    /** @var int * */
    public $code;

    /** @var \DateTimeImmutable * */
    public $created;

    /** @var string * */
    public $message;

    /** @var string */
    public $details;
}
