<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Platforms\Keywords\PostgreSQL100Keywords;

/**
 * Provides the behavior, features and SQL dialect of the PostgreSQL 10.0 database platform.
 */
class PostgreSQL100Platform extends PostgreSQL94Platform
{
    protected function getReservedKeywordsClass(): string
    {
        return PostgreSQL100Keywords::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getListSequencesSQL($database): string
    {
        return 'SELECT sequence_name AS relname,
                       sequence_schema AS schemaname,
                       minimum_value AS min_value, 
                       increment AS increment_by
                FROM   information_schema.sequences
                WHERE  sequence_catalog = ' . $this->quoteStringLiteral($database) . "
                AND    sequence_schema NOT LIKE 'pg\_%'
                AND    sequence_schema != 'information_schema'";
    }
}
