<?php

namespace Doctrine\Common\Cache\Psr6;

use InvalidArgumentException;
use Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * @internal
 */
final class InvalidArgument extends InvalidArgumentException implements PsrInvalidArgumentException
{
}
