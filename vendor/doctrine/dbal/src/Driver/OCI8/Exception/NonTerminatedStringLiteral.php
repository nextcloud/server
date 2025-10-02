<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\OCI8\Exception;

use Doctrine\DBAL\Driver\AbstractException;

use function sprintf;

/** @internal */
final class NonTerminatedStringLiteral extends AbstractException
{
    public static function new(int $offset): self
    {
        return new self(
            sprintf(
                'The statement contains non-terminated string literal starting at offset %d.',
                $offset,
            ),
        );
    }
}
