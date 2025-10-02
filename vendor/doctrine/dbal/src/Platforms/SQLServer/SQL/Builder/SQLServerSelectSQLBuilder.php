<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms\SQLServer\SQL\Builder;

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
use Doctrine\DBAL\Query\SelectQuery;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;

use function count;
use function implode;

final class SQLServerSelectSQLBuilder implements SelectSQLBuilder
{
    private SQLServerPlatform $platform;

    /** @internal The SQL builder should be instantiated only by database platforms. */
    public function __construct(SQLServerPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function buildSQL(SelectQuery $query): string
    {
        $parts = ['SELECT'];

        if ($query->isDistinct()) {
            $parts[] = 'DISTINCT';
        }

        $parts[] = implode(', ', $query->getColumns());

        $from = $query->getFrom();

        if (count($from) > 0) {
            $parts[] = 'FROM ' . implode(', ', $from);
        }

        $forUpdate = $query->getForUpdate();

        if ($forUpdate !== null) {
            $with = ['UPDLOCK', 'ROWLOCK'];

            if ($forUpdate->getConflictResolutionMode() === ConflictResolutionMode::SKIP_LOCKED) {
                $with[] = 'READPAST';
            }

            $parts[] = 'WITH (' . implode(', ', $with) . ')';
        }

        $where = $query->getWhere();

        if ($where !== null) {
            $parts[] = 'WHERE ' . $where;
        }

        $groupBy = $query->getGroupBy();

        if (count($groupBy) > 0) {
            $parts[] = 'GROUP BY ' . implode(', ', $groupBy);
        }

        $having = $query->getHaving();

        if ($having !== null) {
            $parts[] = 'HAVING ' . $having;
        }

        $orderBy = $query->getOrderBy();

        if (count($orderBy) > 0) {
            $parts[] = 'ORDER BY ' . implode(', ', $orderBy);
        }

        $sql   = implode(' ', $parts);
        $limit = $query->getLimit();

        if ($limit->isDefined()) {
            $sql = $this->platform->modifyLimitQuery($sql, $limit->getMaxResults(), $limit->getFirstResult());
        }

        return $sql;
    }
}
