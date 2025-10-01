<?php

namespace Doctrine\DBAL\Query;

final class SelectQuery
{
    private bool $distinct;

    /** @var string[] */
    private array $columns;

    /** @var string[] */
    private array $from;

    private ?string $where;

    /** @var string[] */
    private array $groupBy;

    private ?string $having;

    /** @var string[] */
    private array $orderBy;

    private Limit $limit;

    private ?ForUpdate $forUpdate;

    /**
     * @internal This class should be instantiated only by {@link QueryBuilder}.
     *
     * @param string[] $columns
     * @param string[] $from
     * @param string[] $groupBy
     * @param string[] $orderBy
     */
    public function __construct(
        bool $distinct,
        array $columns,
        array $from,
        ?string $where,
        array $groupBy,
        ?string $having,
        array $orderBy,
        Limit $limit,
        ?ForUpdate $forUpdate
    ) {
        $this->distinct  = $distinct;
        $this->columns   = $columns;
        $this->from      = $from;
        $this->where     = $where;
        $this->groupBy   = $groupBy;
        $this->having    = $having;
        $this->orderBy   = $orderBy;
        $this->limit     = $limit;
        $this->forUpdate = $forUpdate;
    }

    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    /** @return string[] */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return string[] */
    public function getFrom(): array
    {
        return $this->from;
    }

    public function getWhere(): ?string
    {
        return $this->where;
    }

    /** @return string[] */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getHaving(): ?string
    {
        return $this->having;
    }

    /** @return string[] */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getLimit(): Limit
    {
        return $this->limit;
    }

    public function getForUpdate(): ?ForUpdate
    {
        return $this->forUpdate;
    }
}
