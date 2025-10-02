<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

/**
 * Provides the behavior, features and SQL dialect of the PostgreSQL 12.0 database platform.
 */
class PostgreSQL120Platform extends PostgreSQL100Platform
{
    public function getDefaultColumnValueSQLSnippet(): string
    {
        // in case of GENERATED ALWAYS AS (foobar) STORED column (added in PostgreSQL 12.0)
        // PostgreSQL's pg_get_expr(adbin, adrelid) will return the 'foobar' part
        // which is not the 'default' value of the column but its 'definition'
        // so in that case we force it to NULL as DBAL will use that column only for the
        // 'default' value
        return <<<'SQL'
            SELECT
                CASE
                    WHEN a.attgenerated = 's' THEN NULL
                    ELSE pg_get_expr(adbin, adrelid)
                END
             FROM pg_attrdef
             WHERE c.oid = pg_attrdef.adrelid
                AND pg_attrdef.adnum=a.attnum
        SQL;
    }
}
