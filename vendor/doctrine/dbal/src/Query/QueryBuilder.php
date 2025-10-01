<?php

namespace Doctrine\DBAL\Query;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function array_key_exists;
use function array_keys;
use function array_unshift;
use function count;
use function func_get_arg;
use function func_get_args;
use function func_num_args;
use function implode;
use function is_array;
use function is_object;
use function key;
use function method_exists;
use function strtoupper;
use function substr;
use function ucfirst;

/**
 * QueryBuilder class is responsible to dynamically create SQL queries.
 *
 * Important: Verify that every feature you use will work with your database vendor.
 * SQL Query Builder does not attempt to validate the generated SQL at all.
 *
 * The query builder does no validation whatsoever if certain features even work with the
 * underlying database vendor. Limit queries and joins are NOT applied to UPDATE and DELETE statements
 * even if some vendors such as MySQL support it.
 *
 * @method $this distinct(bool $distinct = true) Adds or removes DISTINCT to/from the query.
 */
class QueryBuilder
{
    /** @deprecated */
    public const SELECT = 0;

    /** @deprecated */
    public const DELETE = 1;

    /** @deprecated */
    public const UPDATE = 2;

    /** @deprecated */
    public const INSERT = 3;

    /** @deprecated */
    public const STATE_DIRTY = 0;

    /** @deprecated */
    public const STATE_CLEAN = 1;

    /**
     * The DBAL Connection.
     */
    private Connection $connection;

    /*
     * The default values of SQL parts collection
     */
    private const SQL_PARTS_DEFAULTS = [
        'select'     => [],
        'distinct'   => false,
        'from'       => [],
        'join'       => [],
        'set'        => [],
        'where'      => null,
        'groupBy'    => [],
        'having'     => null,
        'orderBy'    => [],
        'values'     => [],
        'for_update' => null,
    ];

    /**
     * The array of SQL parts collected.
     *
     * @var mixed[]
     */
    private array $sqlParts = self::SQL_PARTS_DEFAULTS;

    /**
     * The complete SQL string for this query.
     */
    private ?string $sql = null;

    /**
     * The query parameters.
     *
     * @var list<mixed>|array<string, mixed>
     */
    private $params = [];

    /**
     * The parameter type map of this query.
     *
     * @var array<int, int|string|Type|null>|array<string, int|string|Type|null>
     */
    private array $paramTypes = [];

    /**
     * The type of query this is. Can be select, update or delete.
     *
     * @phpstan-var self::SELECT|self::DELETE|self::UPDATE|self::INSERT
     */
    private int $type = self::SELECT;

    /**
     * The state of the query object. Can be dirty or clean.
     *
     * @phpstan-var self::STATE_*
     */
    private int $state = self::STATE_CLEAN;

    /**
     * The index of the first result to retrieve.
     */
    private int $firstResult = 0;

    /**
     * The maximum number of results to retrieve or NULL to retrieve all results.
     */
    private ?int $maxResults = null;

    /**
     * The counter of bound parameters used with {@see bindValue).
     */
    private int $boundCounter = 0;

    /**
     * The query cache profile used for caching results.
     */
    private ?QueryCacheProfile $resultCacheProfile = null;

    /**
     * Initializes a new <tt>QueryBuilder</tt>.
     *
     * @param Connection $connection The DBAL Connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     * This producer method is intended for convenient inline usage. Example:
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where($qb->expr()->eq('u.id', 1));
     * </code>
     *
     * For more complex expression construction, consider storing the expression
     * builder object in a local variable.
     *
     * @return ExpressionBuilder
     */
    public function expr()
    {
        return $this->connection->getExpressionBuilder();
    }

    /**
     * Gets the type of the currently built query.
     *
     * @deprecated If necessary, track the type of the query being built outside of the builder.
     *
     * @return int
     */
    public function getType()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5551',
            'Relying on the type of the query being built is deprecated.'
                . ' If necessary, track the type of the query being built outside of the builder.',
        );

        return $this->type;
    }

    /**
     * Gets the associated DBAL Connection for this query builder.
     *
     * @deprecated Use the connection used to instantiate the builder instead.
     *
     * @return Connection
     */
    public function getConnection()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5780',
            '%s is deprecated. Use the connection used to instantiate the builder instead.',
            __METHOD__,
        );

        return $this->connection;
    }

    /**
     * Gets the state of this query builder instance.
     *
     * @deprecated The builder state is an internal concern.
     *
     * @return int Either QueryBuilder::STATE_DIRTY or QueryBuilder::STATE_CLEAN.
     * @phpstan-return self::STATE_*
     */
    public function getState()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5551',
            'Relying on the query builder state is deprecated as it is an internal concern.',
        );

        return $this->state;
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @return array<string, mixed>|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchAssociative()
    {
        return $this->executeQuery()->fetchAssociative();
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @return array<int, mixed>|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchNumeric()
    {
        return $this->executeQuery()->fetchNumeric();
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @return mixed|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchOne()
    {
        return $this->executeQuery()->fetchOne();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of numeric arrays.
     *
     * @return array<int,array<int,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllNumeric(): array
    {
        return $this->executeQuery()->fetchAllNumeric();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociative(): array
    {
        return $this->executeQuery()->fetchAllAssociative();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @return array<mixed,mixed>
     *
     * @throws Exception
     */
    public function fetchAllKeyValue(): array
    {
        return $this->executeQuery()->fetchAllKeyValue();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @return array<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociativeIndexed(): array
    {
        return $this->executeQuery()->fetchAllAssociativeIndexed();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of the first column values.
     *
     * @return array<int,mixed>
     *
     * @throws Exception
     */
    public function fetchFirstColumn(): array
    {
        return $this->executeQuery()->fetchFirstColumn();
    }

    /**
     * Executes an SQL query (SELECT) and returns a Result.
     *
     * @throws Exception
     */
    public function executeQuery(): Result
    {
        return $this->connection->executeQuery(
            $this->getSQL(),
            $this->params,
            $this->paramTypes,
            $this->resultCacheProfile,
        );
    }

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * Should be used for INSERT, UPDATE and DELETE
     *
     * @return int The number of affected rows.
     *
     * @throws Exception
     */
    public function executeStatement(): int
    {
        return $this->connection->executeStatement($this->getSQL(), $this->params, $this->paramTypes);
    }

    /**
     * Executes this query using the bound parameters and their types.
     *
     * @deprecated Use {@see executeQuery()} or {@see executeStatement()} instead.
     *
     * @return Result|int|string
     *
     * @throws Exception
     */
    public function execute()
    {
        if ($this->type === self::SELECT) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/4578',
                'QueryBuilder::execute() is deprecated, use QueryBuilder::executeQuery() for SQL queries instead.',
            );

            return $this->executeQuery();
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4578',
            'QueryBuilder::execute() is deprecated, use QueryBuilder::executeStatement() for SQL statements instead.',
        );

        return $this->connection->executeStatement($this->getSQL(), $this->params, $this->paramTypes);
    }

    /**
     * Gets the complete SQL string formed by the current specifications of this QueryBuilder.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *     echo $qb->getSQL(); // SELECT u FROM User u
     * </code>
     *
     * @return string The SQL query string.
     */
    public function getSQL()
    {
        if ($this->sql !== null && $this->state === self::STATE_CLEAN) {
            return $this->sql;
        }

        switch ($this->type) {
            case self::INSERT:
                $sql = $this->getSQLForInsert();
                break;

            case self::DELETE:
                $sql = $this->getSQLForDelete();
                break;

            case self::UPDATE:
                $sql = $this->getSQLForUpdate();
                break;

            case self::SELECT:
                $sql = $this->getSQLForSelect();
                break;
        }

        $this->state = self::STATE_CLEAN;
        $this->sql   = $sql;

        return $sql;
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param int|string           $key   Parameter position or name
     * @param mixed                $value Parameter value
     * @param int|string|Type|null $type  Parameter type
     *
     * @return $this This QueryBuilder instance.
     */
    public function setParameter($key, $value, $type = ParameterType::STRING)
    {
        if ($type !== null) {
            $this->paramTypes[$key] = $type;
        } else {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5550',
                'Using NULL as prepared statement parameter type is deprecated.'
                    . 'Omit or use ParameterType::STRING instead',
            );
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id1 OR u.id = :user_id2')
     *         ->setParameters(array(
     *             'user_id1' => 1,
     *             'user_id2' => 2
     *         ));
     * </code>
     *
     * @param list<mixed>|array<string, mixed>                                     $params Parameters to set
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return $this This QueryBuilder instance.
     */
    public function setParameters(array $params, array $types = [])
    {
        $this->paramTypes = $types;
        $this->params     = $params;

        return $this;
    }

    /**
     * Gets all defined query parameters for the query being constructed indexed by parameter index or name.
     *
     * @return list<mixed>|array<string, mixed> The currently defined query parameters
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     *
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Gets all defined query parameter types for the query being constructed indexed by parameter index or name.
     *
     * @return array<int, int|string|Type|null>|array<string, int|string|Type|null> The currently defined
     *                                                                              query parameter types
     */
    public function getParameterTypes()
    {
        return $this->paramTypes;
    }

    /**
     * Gets a (previously set) query parameter type of the query being constructed.
     *
     * @param int|string $key The key of the bound parameter type
     *
     * @return int|string|Type The value of the bound parameter type
     */
    public function getParameterType($key)
    {
        return $this->paramTypes[$key] ?? ParameterType::STRING;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param int $firstResult The first result to return.
     *
     * @return $this This QueryBuilder instance.
     */
    public function setFirstResult($firstResult)
    {
        $this->state       = self::STATE_DIRTY;
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     *
     * @return int The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param int|null $maxResults The maximum number of results to retrieve or NULL to retrieve all results.
     *
     * @return $this This QueryBuilder instance.
     */
    public function setMaxResults($maxResults)
    {
        $this->state      = self::STATE_DIRTY;
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if all results will be returned.
     *
     * @return int|null The maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * Locks the queried rows for a subsequent update.
     *
     * @return $this
     */
    public function forUpdate(int $conflictResolutionMode = ConflictResolutionMode::ORDINARY): self
    {
        $this->state = self::STATE_DIRTY;

        $this->sqlParts['for_update'] = new ForUpdate($conflictResolutionMode);

        return $this;
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * The available parts are: 'select', 'from', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'.
     *
     * @param string $sqlPartName
     * @param mixed  $sqlPart
     * @param bool   $append
     *
     * @return $this This QueryBuilder instance.
     */
    public function add($sqlPartName, $sqlPart, $append = false)
    {
        $isArray    = is_array($sqlPart);
        $isMultiple = is_array($this->sqlParts[$sqlPartName]);

        if ($isMultiple && ! $isArray) {
            $sqlPart = [$sqlPart];
        }

        $this->state = self::STATE_DIRTY;

        if ($append) {
            if (
                $sqlPartName === 'orderBy'
                || $sqlPartName === 'groupBy'
                || $sqlPartName === 'select'
                || $sqlPartName === 'set'
            ) {
                foreach ($sqlPart as $part) {
                    $this->sqlParts[$sqlPartName][] = $part;
                }
            } elseif ($isArray && is_array($sqlPart[key($sqlPart)])) {
                $key                                  = key($sqlPart);
                $this->sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } elseif ($isMultiple) {
                $this->sqlParts[$sqlPartName][] = $sqlPart;
            } else {
                $this->sqlParts[$sqlPartName] = $sqlPart;
            }

            return $this;
        }

        $this->sqlParts[$sqlPartName] = $sqlPart;

        return $this;
    }

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id', 'p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'u.id = p.user_id');
     * </code>
     *
     * @param string|string[]|null $select The selection expression. USING AN ARRAY OR NULL IS DEPRECATED.
     *                                     Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function select($select = null/*, string ...$selects*/)
    {
        $this->type = self::SELECT;

        if ($select === null) {
            return $this;
        }

        if (is_array($select)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3837',
                'Passing an array for the first argument to QueryBuilder::select() is deprecated, ' .
                'pass each value as an individual variadic argument instead.',
            );
        }

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', $selects);
    }

    /**
     * Adds or removes DISTINCT to/from the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->distinct()
     *         ->from('users', 'u')
     * </code>
     *
     * @return $this This QueryBuilder instance.
     */
    public function distinct(/* bool $distinct = true */): self
    {
        $this->sqlParts['distinct'] = func_num_args() < 1 || func_get_arg(0);
        $this->state                = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->addSelect('p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'u.id = p.user_id');
     * </code>
     *
     * @param string|string[]|null $select The selection expression. USING AN ARRAY OR NULL IS DEPRECATED.
     *                                     Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addSelect($select = null/*, string ...$selects*/)
    {
        $this->type = self::SELECT;

        if ($select === null) {
            return $this;
        }

        if (is_array($select)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3837',
                'Passing an array for the first argument to QueryBuilder::addSelect() is deprecated, ' .
                'pass each value as an individual variadic argument instead.',
            );
        }

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', $selects, true);
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->delete('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1);
     * </code>
     *
     * @param string $delete The table whose rows are subject to the deletion.
     * @param string $alias  The table alias used in the constructed query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function delete($delete = null, $alias = null)
    {
        $this->type = self::DELETE;

        if ($delete === null) {
            return $this;
        }

        return $this->add('from', [
            'table' => $delete,
            'alias' => $alias,
        ]);
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where('c.id = ?');
     * </code>
     *
     * @param string $update The table whose rows are subject to the update.
     * @param string $alias  The table alias used in the constructed query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function update($update = null, $alias = null)
    {
        $this->type = self::UPDATE;

        if ($update === null) {
            return $this;
        }

        return $this->add('from', [
            'table' => $update,
            'alias' => $alias,
        ]);
    }

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param string $insert The table into which the rows should be inserted.
     *
     * @return $this This QueryBuilder instance.
     */
    public function insert($insert = null)
    {
        $this->type = self::INSERT;

        if ($insert === null) {
            return $this;
        }

        return $this->add('from', ['table' => $insert]);
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->from('users', 'u')
     * </code>
     *
     * @param string      $from  The table.
     * @param string|null $alias The alias of the table.
     *
     * @return $this This QueryBuilder instance.
     */
    public function from($from, $alias = null)
    {
        return $this->add('from', [
            'table' => $from,
            'alias' => $alias,
        ], true);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function join($fromAlias, $join, $alias, $condition = null)
    {
        return $this->innerJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->innerJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function innerJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->add('join', [
            $fromAlias => [
                'joinType'      => 'inner',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], true);
    }

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->add('join', [
            $fromAlias => [
                'joinType'      => 'left',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], true);
    }

    /**
     * Creates and adds a right join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->add('join', [
            $fromAlias => [
                'joinType'      => 'right',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], true);
    }

    /**
     * Sets a new value for a column in a bulk update query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where('c.id = ?');
     * </code>
     *
     * @param string $key   The column to set.
     * @param string $value The value, expression, placeholder, etc.
     *
     * @return $this This QueryBuilder instance.
     */
    public function set($key, $value)
    {
        return $this->add('set', $key . ' = ' . $value, true);
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('c.value')
     *         ->from('counters', 'c')
     *         ->where('c.id = ?');
     *
     *     // You can optionally programmatically build and/or expressions
     *     $qb = $conn->createQueryBuilder();
     *
     *     $or = $qb->expr()->orx();
     *     $or->add($qb->expr()->eq('c.id', 1));
     *     $or->add($qb->expr()->eq('c.id', 2));
     *
     *     $qb->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where($or);
     * </code>
     *
     * @param mixed $predicates The restriction predicates.
     *
     * @return $this This QueryBuilder instance.
     */
    public function where($predicates)
    {
        if (! (func_num_args() === 1 && $predicates instanceof CompositeExpression)) {
            $predicates = CompositeExpression::and(...func_get_args());
        }

        return $this->add('where', $predicates);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     * </code>
     *
     * @see where()
     *
     * @param mixed $where The query restrictions.
     *
     * @return $this This QueryBuilder instance.
     */
    public function andWhere($where)
    {
        $args  = func_get_args();
        $where = $this->getQueryPart('where');

        if ($where instanceof CompositeExpression && $where->getType() === CompositeExpression::TYPE_AND) {
            $where = $where->with(...$args);
        } else {
            array_unshift($args, $where);
            $where = CompositeExpression::and(...$args);
        }

        return $this->add('where', $where, true);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @see where()
     *
     * @param mixed $where The WHERE statement.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orWhere($where)
    {
        $args  = func_get_args();
        $where = $this->getQueryPart('where');

        if ($where instanceof CompositeExpression && $where->getType() === CompositeExpression::TYPE_OR) {
            $where = $where->with(...$args);
        } else {
            array_unshift($args, $where);
            $where = CompositeExpression::or(...$args);
        }

        return $this->add('where', $where, true);
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param string|string[] $groupBy The grouping expression. USING AN ARRAY IS DEPRECATED.
     *                                 Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function groupBy($groupBy/*, string ...$groupBys*/)
    {
        if (is_array($groupBy) && count($groupBy) === 0) {
            return $this;
        }

        if (is_array($groupBy)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3837',
                'Passing an array for the first argument to QueryBuilder::groupBy() is deprecated, ' .
                'pass each value as an individual variadic argument instead.',
            );
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->add('groupBy', $groupBy, false);
    }

    /**
     * Adds a grouping expression to the query.
     *
     * USING AN ARRAY ARGUMENT IS DEPRECATED. Pass each value as an individual argument.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.lastLogin')
     *         ->addGroupBy('u.createdAt');
     * </code>
     *
     * @param string|string[] $groupBy The grouping expression. USING AN ARRAY IS DEPRECATED.
     *                                 Pass each value as an individual argument.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addGroupBy($groupBy/*, string ...$groupBys*/)
    {
        if (is_array($groupBy) && count($groupBy) === 0) {
            return $this;
        }

        if (is_array($groupBy)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3837',
                'Passing an array for the first argument to QueryBuilder::addGroupBy() is deprecated, ' .
                'pass each value as an individual variadic argument instead.',
            );
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->add('groupBy', $groupBy, true);
    }

    /**
     * Sets a value for a column in an insert query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?'
     *             )
     *         )
     *         ->setValue('password', '?');
     * </code>
     *
     * @param string $column The column into which the value should be inserted.
     * @param string $value  The value that should be inserted into the column.
     *
     * @return $this This QueryBuilder instance.
     */
    public function setValue($column, $value)
    {
        $this->sqlParts['values'][$column] = $value;

        return $this;
    }

    /**
     * Specifies values for an insert query indexed by column names.
     * Replaces any previous values, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param mixed[] $values The values to specify for the insert query indexed by column names.
     *
     * @return $this This QueryBuilder instance.
     */
    public function values(array $values)
    {
        return $this->add('values', $values);
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed $having The restriction over the groups.
     *
     * @return $this This QueryBuilder instance.
     */
    public function having($having)
    {
        if (! (func_num_args() === 1 && $having instanceof CompositeExpression)) {
            $having = CompositeExpression::and(...func_get_args());
        }

        return $this->add('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to append.
     *
     * @return $this This QueryBuilder instance.
     */
    public function andHaving($having)
    {
        $args   = func_get_args();
        $having = $this->getQueryPart('having');

        if ($having instanceof CompositeExpression && $having->getType() === CompositeExpression::TYPE_AND) {
            $having = $having->with(...$args);
        } else {
            array_unshift($args, $having);
            $having = CompositeExpression::and(...$args);
        }

        return $this->add('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to add.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orHaving($having)
    {
        $args   = func_get_args();
        $having = $this->getQueryPart('having');

        if ($having instanceof CompositeExpression && $having->getType() === CompositeExpression::TYPE_OR) {
            $having = $having->with(...$args);
        } else {
            array_unshift($args, $having);
            $having = CompositeExpression::or(...$args);
        }

        return $this->add('having', $having);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orderBy($sort, $order = null)
    {
        return $this->add('orderBy', $sort . ' ' . ($order ?? 'ASC'), false);
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addOrderBy($sort, $order = null)
    {
        return $this->add('orderBy', $sort . ' ' . ($order ?? 'ASC'), true);
    }

    /**
     * Gets a query part by its name.
     *
     * @deprecated The query parts are implementation details and should not be relied upon.
     *
     * @param string $queryPartName
     *
     * @return mixed
     */
    public function getQueryPart($queryPartName)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6179',
            'Getting query parts is deprecated as they are implementation details.',
        );

        return $this->sqlParts[$queryPartName];
    }

    /**
     * Gets all query parts.
     *
     * @deprecated The query parts are implementation details and should not be relied upon.
     *
     * @return mixed[]
     */
    public function getQueryParts()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6179',
            'Getting query parts is deprecated as they are implementation details.',
        );

        return $this->sqlParts;
    }

    /**
     * Resets SQL parts.
     *
     * @deprecated Use the dedicated reset*() methods instead.
     *
     * @param string[]|null $queryPartNames
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetQueryParts($queryPartNames = null)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6193',
            '%s() is deprecated, instead use dedicated reset methods for the parts that shall be reset.',
            __METHOD__,
        );

        $queryPartNames ??= array_keys($this->sqlParts);

        foreach ($queryPartNames as $queryPartName) {
            $this->sqlParts[$queryPartName] = self::SQL_PARTS_DEFAULTS[$queryPartName];
        }

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Resets a single SQL part.
     *
     * @deprecated Use the dedicated reset*() methods instead.
     *
     * @param string $queryPartName
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetQueryPart($queryPartName)
    {
        if ($queryPartName === 'distinct') {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6193',
                'Calling %s() with "distinct" is deprecated, call distinct(false) instead.',
                __METHOD__,
            );

            return $this->distinct(false);
        }

        $newMethodName = 'reset' . ucfirst($queryPartName);
        if (array_key_exists($queryPartName, self::SQL_PARTS_DEFAULTS) && method_exists($this, $newMethodName)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6193',
                'Calling %s() with "%s" is deprecated, call %s() instead.',
                __METHOD__,
                $queryPartName,
                $newMethodName,
            );

            return $this->$newMethodName();
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6193',
            'Calling %s() with "%s" is deprecated without replacement.',
            __METHOD__,
            $queryPartName,
            $newMethodName,
        );

        $this->sqlParts[$queryPartName] = self::SQL_PARTS_DEFAULTS[$queryPartName];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Resets the WHERE conditions for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetWhere(): self
    {
        $this->sqlParts['where'] = self::SQL_PARTS_DEFAULTS['where'];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Resets the grouping for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetGroupBy(): self
    {
        $this->sqlParts['groupBy'] = self::SQL_PARTS_DEFAULTS['groupBy'];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Resets the HAVING conditions for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetHaving(): self
    {
        $this->sqlParts['having'] = self::SQL_PARTS_DEFAULTS['having'];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Resets the ordering for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetOrderBy(): self
    {
        $this->sqlParts['orderBy'] = self::SQL_PARTS_DEFAULTS['orderBy'];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /** @throws Exception */
    private function getSQLForSelect(): string
    {
        return $this->connection->getDatabasePlatform()
            ->createSelectSQLBuilder()
            ->buildSQL(
                new SelectQuery(
                    $this->sqlParts['distinct'],
                    $this->sqlParts['select'],
                    $this->getFromClauses(),
                    $this->sqlParts['where'],
                    $this->sqlParts['groupBy'],
                    $this->sqlParts['having'],
                    $this->sqlParts['orderBy'],
                    new Limit($this->maxResults, $this->firstResult),
                    $this->sqlParts['for_update'],
                ),
            );
    }

    /**
     * @return string[]
     *
     * @throws QueryException
     */
    private function getFromClauses(): array
    {
        $fromClauses  = [];
        $knownAliases = [];

        // Loop through all FROM clauses
        foreach ($this->sqlParts['from'] as $from) {
            if ($from['alias'] === null) {
                $tableSql       = $from['table'];
                $tableReference = $from['table'];
            } else {
                $tableSql       = $from['table'] . ' ' . $from['alias'];
                $tableReference = $from['alias'];
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql . $this->getSQLForJoins($tableReference, $knownAliases);
        }

        $this->verifyAllAliasesAreKnown($knownAliases);

        return $fromClauses;
    }

    /**
     * @param array<string,true> $knownAliases
     *
     * @throws QueryException
     */
    private function verifyAllAliasesAreKnown(array $knownAliases): void
    {
        foreach ($this->sqlParts['join'] as $fromAlias => $joins) {
            if (! isset($knownAliases[$fromAlias])) {
                throw QueryException::unknownAlias($fromAlias, array_keys($knownAliases));
            }
        }
    }

    /**
     * Converts this instance into an INSERT string in SQL.
     */
    private function getSQLForInsert(): string
    {
        return 'INSERT INTO ' . $this->sqlParts['from']['table'] .
        ' (' . implode(', ', array_keys($this->sqlParts['values'])) . ')' .
        ' VALUES(' . implode(', ', $this->sqlParts['values']) . ')';
    }

    /**
     * Converts this instance into an UPDATE string in SQL.
     */
    private function getSQLForUpdate(): string
    {
        $table = $this->sqlParts['from']['table']
            . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');

        return 'UPDATE ' . $table
            . ' SET ' . implode(', ', $this->sqlParts['set'])
            . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');
    }

    /**
     * Converts this instance into a DELETE string in SQL.
     */
    private function getSQLForDelete(): string
    {
        $table = $this->sqlParts['from']['table']
            . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');

        return 'DELETE FROM ' . $table
            . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     */
    public function __toString()
    {
        return $this->getSQL();
    }

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * This method provides a shortcut for {@see Statement::bindValue()}
     * when using prepared statements.
     *
     * The parameter $value specifies the value that you want to bind. If
     * $placeholder is not provided createNamedParameter() will automatically
     * create a placeholder for you. An automatic placeholder will be of the
     * name ':dcValue1', ':dcValue2' etc.
     *
     * Example:
     * <code>
     * $value = 2;
     * $q->eq( 'id', $q->createNamedParameter( $value ) );
     * $stmt = $q->executeQuery(); // executed with 'id = 2'
     * </code>
     *
     * @link http://www.zetacomponents.org
     *
     * @param mixed                $value
     * @param int|string|Type|null $type
     * @param string               $placeHolder The name to bind with. The string must start with a colon ':'.
     *
     * @return string the placeholder name used.
     */
    public function createNamedParameter($value, $type = ParameterType::STRING, $placeHolder = null)
    {
        if ($placeHolder === null) {
            $this->boundCounter++;
            $placeHolder = ':dcValue' . $this->boundCounter;
        }

        $this->setParameter(substr($placeHolder, 1), $value, $type);

        return $placeHolder;
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     *
     * Attention: If you are using positional parameters with the query builder you have
     * to be very careful to bind all parameters in the order they appear in the SQL
     * statement , otherwise they get bound in the wrong order which can lead to serious
     * bugs in your code.
     *
     * Example:
     * <code>
     *  $qb = $conn->createQueryBuilder();
     *  $qb->select('u.*')
     *     ->from('users', 'u')
     *     ->where('u.username = ' . $qb->createPositionalParameter('Foo', ParameterType::STRING))
     *     ->orWhere('u.username = ' . $qb->createPositionalParameter('Bar', ParameterType::STRING))
     * </code>
     *
     * @param mixed                $value
     * @param int|string|Type|null $type
     *
     * @return string
     */
    public function createPositionalParameter($value, $type = ParameterType::STRING)
    {
        $this->setParameter($this->boundCounter, $value, $type);
        $this->boundCounter++;

        return '?';
    }

    /**
     * @param string             $fromAlias
     * @param array<string,true> $knownAliases
     *
     * @throws QueryException
     */
    private function getSQLForJoins($fromAlias, array &$knownAliases): string
    {
        $sql = '';

        if (isset($this->sqlParts['join'][$fromAlias])) {
            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                if (array_key_exists($join['joinAlias'], $knownAliases)) {
                    throw QueryException::nonUniqueAlias((string) $join['joinAlias'], array_keys($knownAliases));
                }

                $sql .= ' ' . strtoupper($join['joinType'])
                    . ' JOIN ' . $join['joinTable'] . ' ' . $join['joinAlias'];
                if ($join['joinCondition'] !== null) {
                    $sql .= ' ON ' . $join['joinCondition'];
                }

                $knownAliases[$join['joinAlias']] = true;
            }

            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                $sql .= $this->getSQLForJoins($join['joinAlias'], $knownAliases);
            }
        }

        return $sql;
    }

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->sqlParts as $part => $elements) {
            if (is_array($this->sqlParts[$part])) {
                foreach ($this->sqlParts[$part] as $idx => $element) {
                    if (! is_object($element)) {
                        continue;
                    }

                    $this->sqlParts[$part][$idx] = clone $element;
                }
            } elseif (is_object($elements)) {
                $this->sqlParts[$part] = clone $elements;
            }
        }

        foreach ($this->params as $name => $param) {
            if (! is_object($param)) {
                continue;
            }

            $this->params[$name] = clone $param;
        }
    }

    /**
     * Enables caching of the results of this query, for given amount of seconds
     * and optionally specified which key to use for the cache entry.
     *
     * @return $this
     */
    public function enableResultCache(QueryCacheProfile $cacheProfile): self
    {
        $this->resultCacheProfile = $cacheProfile;

        return $this;
    }

    /**
     * Disables caching of the results of this query.
     *
     * @return $this
     */
    public function disableResultCache(): self
    {
        $this->resultCacheProfile = null;

        return $this;
    }
}
