<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema\Exception;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

use function implode;
use function sprintf;

final class NamedForeignKeyRequired extends SchemaException
{
    public static function new(Table $localTable, ForeignKeyConstraint $foreignKey): self
    {
        return new self(
            sprintf(
                'The performed schema operation on "%s" requires a named foreign key, ' .
                'but the given foreign key from (%s) onto foreign table "%s" (%s) is currently unnamed.',
                $localTable->getName(),
                implode(', ', $foreignKey->getColumns()),
                $foreignKey->getForeignTableName(),
                implode(', ', $foreignKey->getForeignColumns()),
            ),
        );
    }
}
