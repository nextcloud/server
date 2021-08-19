<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;

use function sprintf;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class FailedReadingStreamOffset extends AbstractException
{
    public static function new(int $parameter): self
    {
        return new self(sprintf('Failed reading the stream resource for parameter #%d.', $parameter));
    }
}
