<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Connection;

/** @internal Will be removed in 4.0. */
final class LegacySchemaManagerFactory implements SchemaManagerFactory
{
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        return $connection->getDriver()->getSchemaManager(
            $connection,
            $connection->getDatabasePlatform(),
        );
    }
}
