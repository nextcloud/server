<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Parser;
use Doctrine\DBAL\Types\Types;

/**
 * Provides the behavior, features and SQL dialect of the MySQL 5.7 (5.7.9 GA) database platform.
 */
class MySQL57Platform extends MySQLPlatform
{
    /**
     * {@inheritdoc}
     */
    public function hasNativeJsonType()
    {
        return true;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function getPreAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPostAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        return ['ALTER TABLE ' . $tableName . ' RENAME INDEX ' . $oldIndexName . ' TO ' . $index->getQuotedName($this)];
    }

    /**
     * {@inheritdoc}
     */
    protected function getReservedKeywordsClass()
    {
        return Keywords\MySQL57Keywords::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        parent::initializeDoctrineTypeMappings();

        $this->doctrineTypeMapping['json'] = Types::JSON;
    }
}
