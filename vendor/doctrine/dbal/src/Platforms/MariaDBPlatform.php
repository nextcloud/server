<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;

/**
 * Provides the behavior, features and SQL dialect of the MariaDB database platform of the oldest supported version.
 */
class MariaDBPlatform extends MySQLPlatform
{
    /**
     * {@inheritDoc}
     *
     * Hop over the {@see AbstractMySQLPlatform} implementation until 4.0.x
     * where {@see MariaDBPlatform} no longer extends {@see MySQLPlatform}.
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getDefaultValueDeclarationSQL($column)
    {
        return AbstractPlatform::getDefaultValueDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://mariadb.com/kb/en/library/json-data-type/
     */
    public function getJsonTypeDeclarationSQL(array $column): string
    {
        return 'LONGTEXT';
    }

    /** @deprecated Implement {@see createReservedKeywordsList()} instead. */
    protected function getReservedKeywordsClass(): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'MariaDb1027Platform::getReservedKeywordsClass() is deprecated,'
                . ' use MariaDb1027Platform::createReservedKeywordsList() instead.',
        );

        return Keywords\MariaDb102Keywords::class;
    }

    protected function initializeDoctrineTypeMappings(): void
    {
        parent::initializeDoctrineTypeMappings();

        $this->doctrineTypeMapping['json'] = Types::JSON;
    }
}
