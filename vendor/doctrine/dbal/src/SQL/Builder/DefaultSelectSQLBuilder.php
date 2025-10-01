<?php

namespace Doctrine\DBAL\SQL\Builder;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
use Doctrine\DBAL\Query\SelectQuery;

use function count;
use function implode;

final class DefaultSelectSQLBuilder implements SelectSQLBuilder
{
    private AbstractPlatform $platform;
    private ?string $forUpdateSQL;
    private ?string $skipLockedSQL;

    /** @internal The SQL builder should be instantiated only by database platforms. */
    public function __construct(AbstractPlatform $platform, ?string $forUpdateSQL, ?string $skipLockedSQL)
    {
        $this->platform      = $platform;
        $this->forUpdateSQL  = $forUpdateSQL;
        $this->skipLockedSQL = $skipLockedSQL;
    }

    /** @throws Exception */
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

        $forUpdate = $query->getForUpdate();

        if ($forUpdate !== null) {
            if ($this->forUpdateSQL === null) {
                throw Exception::notSupported('FOR UPDATE');
            }

            $sql .= ' ' . $this->forUpdateSQL;

            if ($forUpdate->getConflictResolutionMode() === ConflictResolutionMode::SKIP_LOCKED) {
                if ($this->skipLockedSQL === null) {
                    throw Exception::notSupported('SKIP LOCKED');
                }

                $sql .= ' ' . $this->skipLockedSQL;
            }
        }

        return $sql;
    }
}
