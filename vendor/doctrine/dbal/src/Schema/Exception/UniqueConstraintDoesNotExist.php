<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema\Exception;

use Doctrine\DBAL\Schema\SchemaException;

use function sprintf;

final class UniqueConstraintDoesNotExist extends SchemaException
{
    public static function new(string $constraintName, string $table): self
    {
        return new self(
            sprintf('There exists no unique constraint with the name "%s" on table "%s".', $constraintName, $table),
            self::CONSTRAINT_DOESNT_EXIST,
        );
    }
}
