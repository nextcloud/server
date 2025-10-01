<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Parser;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;

/**
 * Provides the behavior, features and SQL dialect of the MySQL 5.7 database platform.
 *
 * @deprecated This class will be merged with {@see MySQLPlatform} in 4.0 because support for MySQL
 *             releases prior to 5.7 will be dropped.
 */
class MySQL57Platform extends MySQLPlatform
{
    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function hasNativeJsonType()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5509',
            '%s is deprecated.',
            __METHOD__,
        );

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonTypeDeclarationSQL(array $column)
    {
        return 'JSON';
    }

    public function createSQLParser(): Parser
    {
        return new Parser(true);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getPostAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        return ['ALTER TABLE ' . $tableName . ' RENAME INDEX ' . $oldIndexName . ' TO ' . $index->getQuotedName($this)];
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Implement {@see createReservedKeywordsList()} instead.
     */
    protected function getReservedKeywordsClass()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'MySQL57Platform::getReservedKeywordsClass() is deprecated,'
                . ' use MySQL57Platform::createReservedKeywordsList() instead.',
        );

        return Keywords\MySQL57Keywords::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        parent::initializeDoctrineTypeMappings();

        $this->doctrineTypeMapping['json'] = Types::JSON;
    }
}
