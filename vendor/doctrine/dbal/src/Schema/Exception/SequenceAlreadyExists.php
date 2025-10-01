<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema\Exception;

use Doctrine\DBAL\Schema\SchemaException;

use function sprintf;

final class SequenceAlreadyExists extends SchemaException
{
    public static function new(string $sequenceName): self
    {
        return new self(
            sprintf('The sequence "%s" already exists.', $sequenceName),
            self::SEQUENCE_ALREADY_EXISTS,
        );
    }
}
