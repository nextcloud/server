<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Exception;

use function sprintf;

/**
 * Provides the behavior, features and SQL dialect of the IBM DB2 11.1 (11.1 GA) database platform.
 *
 * @deprecated This class will be merged with {@see DB2Platform} in 4.0 because support for IBM DB2
 *             releases prior to 11.1 will be dropped.
 *
 * @see https://www.ibm.com/docs/en/db2/11.1?topic=database-whats-new-db2-version-111-ga
 */
class DB2111Platform extends DB2Platform
{
    /**
     * {@inheritDoc}
     *
     * @see https://www.ibm.com/docs/en/db2/11.1?topic=subselect-fetch-clause
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($offset > 0) {
            $query .= sprintf(' OFFSET %u ROWS', $offset);
        }

        if ($limit !== null) {
            if ($limit < 0) {
                throw new Exception(sprintf('Limit must be a positive integer or zero, %d given', $limit));
            }

            $query .= sprintf(' FETCH %s %u ROWS ONLY', $offset === 0 ? 'FIRST' : 'NEXT', $limit);
        }

        return $query;
    }
}
