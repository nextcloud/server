<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

use Doctrine\DBAL\Types\Type;

/**
 * An SQL query together with its bound parameters.
 *
 * @psalm-immutable
 */
final class Query
{
    /**
     * The SQL query.
     *
     * @var string
     */
    private $sql;

    /**
     * The parameters bound to the query.
     *
     * @var array<mixed>
     */
    private $params;

    /**
     * The types of the parameters bound to the query.
     *
     * @var array<Type|int|string|null>
     */
    private $types;

    /**
     * @param array<mixed>                $params
     * @param array<Type|int|string|null> $types
     *
     * @psalm-suppress ImpurePropertyAssignment
     */
    public function __construct(string $sql, array $params, array $types)
    {
        $this->sql    = $sql;
        $this->params = $params;
        $this->types  = $types;
    }

    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @return array<mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return array<Type|int|string|null>
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
