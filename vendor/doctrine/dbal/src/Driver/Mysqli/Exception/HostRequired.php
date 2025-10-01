<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;

/** @internal */
final class HostRequired extends AbstractException
{
    public static function forPersistentConnection(): self
    {
        return new self('The "host" parameter is required for a persistent connection');
    }
}
