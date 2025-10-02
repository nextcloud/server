<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema\Exception;

use Doctrine\DBAL\Schema\SchemaException;

use function sprintf;

final class IndexDoesNotExist extends SchemaException
{
    public static function new(string $indexName, string $table): self
    {
        return new self(
            sprintf('Index "%s" does not exist on table "%s".', $indexName, $table),
            self::INDEX_DOESNT_EXIST,
        );
    }
}
