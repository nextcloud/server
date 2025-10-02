<?php

namespace Doctrine\DBAL\Platforms\Keywords;

use Doctrine\Deprecations\Deprecation;

/**
 * Oracle Keywordlist.
 */
class OracleKeywords extends KeywordList
{
    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getName()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5433',
            'OracleKeywords::getName() is deprecated.',
        );

        return 'Oracle';
    }

    /**
     * {@inheritDoc}
     */
    protected function getKeywords()
    {
        return [
            'ACCESS',
            'ADD',
            'ALL',
            'ALTER',
            'AND',
            'ANY',
            'ARRAYLEN',
            'AS',
            'ASC',
            'AUDIT',
            'BETWEEN',
            'BY',
            'CHAR',
            'CHECK',
            'CLUSTER',
            'COLUMN',
            'COMMENT',
            'COMPRESS',
            'CONNECT',
            'CREATE',
            'CURRENT',
            'DATE',
            'DECIMAL',
            'DEFAULT',
            'DELETE',
            'DESC',
            'DISTINCT',
            'DROP',
            'ELSE',
            'EXCLUSIVE',
            'EXISTS',
            'FILE',
            'FLOAT',
            'FOR',
            'FROM',
            'GRANT',
            'GROUP',
            'HAVING',
            'IDENTIFIED',
            'IMMEDIATE',
            'IN',
            'INCREMENT',
            'INDEX',
            'INITIAL',
            'INSERT',
            'INTEGER',
            'INTERSECT',
            'INTO',
            'IS',
            'LEVEL',
            'LIKE',
            'LOCK',
            'LONG',
            'MAXEXTENTS',
            'MINUS',
            'MODE',
            'MODIFY',
            'NOAUDIT',
            'NOCOMPRESS',
            'NOT',
            'NOTFOUND',
            'NOWAIT',
            'NULL',
            'NUMBER',
            'OF',
            'OFFLINE',
            'ON',
            'ONLINE',
            'OPTION',
            'OR',
            'ORDER',
            'PCTFREE',
            'PRIOR',
            'PRIVILEGES',
            'PUBLIC',
            'RANGE',
            'RAW',
            'RENAME',
            'RESOURCE',
            'REVOKE',
            'ROW',
            'ROWID',
            'ROWLABEL',
            'ROWNUM',
            'ROWS',
            'SELECT',
            'SESSION',
            'SET',
            'SHARE',
            'SIZE',
            'SMALLINT',
            'SQLBUF',
            'START',
            'SUCCESSFUL',
            'SYNONYM',
            'SYSDATE',
            'TABLE',
            'THEN',
            'TO',
            'TRIGGER',
            'UID',
            'UNION',
            'UNIQUE',
            'UPDATE',
            'USER',
            'VALIDATE',
            'VALUES',
            'VARCHAR',
            'VARCHAR2',
            'VIEW',
            'WHENEVER',
            'WHERE',
            'WITH',
        ];
    }
}
