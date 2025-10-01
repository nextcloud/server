<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema\Exception;

use Doctrine\DBAL\Schema\SchemaException;

use function sprintf;

final class IndexAlreadyExists extends SchemaException
{
    public static function new(string $indexName, string $table): self
    {
        return new self(
            sprintf('An index with name "%s" was already defined on table "%s".', $indexName, $table),
            self::INDEX_ALREADY_EXISTS,
        );
    }
}
