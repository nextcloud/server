<?php

namespace Doctrine\DBAL\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;

use function array_key_exists;
use function array_keys;
use function array_unshift;
use function count;
use function func_get_args;
use function func_num_args;
use function implode;
use function is_array;
use function is_object;
use function key;
use function strtoupper;
use function substr;

/**
 * QueryBuilder class is responsible to dynamically create SQL queries.
 *
 * Important: Verify that every feature you use will work with your database vendor.
 * SQL Query Builder does not attempt to validate the generated SQL at all.
 *
 * The query builder does no validation whatsoever if certain features even work with the
 * underlying database vendor. Limit queries and joins are NOT applied to UPDATE and DELETE statements
 * even if some vendors such as MySQL support it.
 */
class QueryBuilder
{
    /*
     * The query types.
     */
    public const SELECT = 0;
    public const DELETE = 1;
    public const UPDATE = 2;
    public const INSERT = 3;

    /*
     * The builder states.
     */
    public const STATE_DIRTY = 0;
    public const STATE_CLEAN = 1;

    /**
     * The DBAL Connection.
     *
     * @var Connection
     */
    private $connection;

    /*
     * The default values of SQL parts collection
     */
    private const SQL_PARTS_DEFAULTS = [
        'select'   => [],
        'distinct' => false,
        'from'     => [],
        'join'     => [],
        'set'      => [],
        'where'    => null,
        'groupBy'  => [],
        'having'   => null,
        'orderBy'  => [],
        'values'   => [],
    ];

    /**
     * The array of SQL parts collected.
     *
     * @var mixed[]
     */
    private $sqlParts = self::SQL_PARTS_DEFAULTS;

    /**
     * The complete SQL string for this query.
     *
     * @var string
     */
    private $sql;

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
    private $paramTypes = [];

    /**
     * The type of query this is. Can be select, update or delete.
     *
     * @var int
     */
    private $type = self::SELECT;

    /**
     * The state of the query object. Can be dirty or clean.
     *
     * @var int
     */
    private $state = self::STATE_CLEAN;

    /**
     * The index of the first result to retrieve.
     *
     * @var int
     */
    private $firstResult;

    /**
     * The maximum number of results to retrieve or NULL to retrieve all results.
     *
     * @var int|null
     */
    private $maxResults;

    /**
     * The counter of bound parameters used with {@see bindValue).
     *
     * @var int
     */
    private $boundCounter = 0;

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
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the associated DBAL Connection for this query builder.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Gets the state of this query builder instance.
     *
     * @return int Either QueryBuilder::STATE_DIRTY or QueryBuilder::STATE_CLEAN.
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Executes this query using the bound parameters and their types.
     *
     * @return Result|int
     *
     * @throws Exception
     */
    public function execute()
    {
        if ($this->type === self::SELECT) {
            return $this->connection->executeQuery($this->getSQL(), $this->params, $this->paramTypes);
        }

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
            default:
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
     *         ->setParameter(':user_id', 1);
     * </code>
     *
     * @param int|string           $key   Parameter position or name
     * @param mixed                $value Parameter value
     * @param int|string|Type|null $type  One of the {@link ParameterType} constants or DBAL type
     *
     * @return $this This QueryBuilder instance.
     */
    public function setParameter($key, $value, $type = null)
    {
        if ($type !== null) {
            $this->paramTypes[$key] = $type;
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
     *             ':user_id1' => 1,
     *             ':user_id2' => 2
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
     * @return int|string|Type|null The value of the bound parameter type
     */
    public function getParameterType($key)
    {
        return $this->paramTypes[$key] ?? null;
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

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', $selects);
    }

    /**
     * Adds DISTINCT to the query.
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
    public function distinct(): self
    {
        $this->sqlParts['distinct'] = true;

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
     *     // You can optionally programatically build and/or expressions
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
     * @param string $queryPartName
     *
     * @return mixed
     */
    public function getQueryPart($queryPartName)
    {
        return $this->sqlParts[$queryPartName];
    }

    /**
     * Gets all query parts.
     *
     * @return mixed[]
     */
    public function getQueryParts()
    {
        return $this->sqlParts;
    }

    /**
     * Resets SQL parts.
     *
     * @param string[]|null $queryPartNames
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetQueryParts($queryPartNames = null)
    {
        if ($queryPartNames === null) {
            $queryPartNames = array_keys($this->sqlParts);
        }

        foreach ($queryPartNames as $queryPartName) {
            $this->resetQueryPart($queryPartName);
        }

        return $this;
    }

    /**
     * Resets a single SQL part.
     *
     * @param string $queryPartName
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetQueryPart($queryPartName)
    {
        $this->sqlParts[$queryPartName] = self::SQL_PARTS_DEFAULTS[$queryPartName];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * @return string
     *
     * @throws QueryException
     */
    private function getSQLForSelect()
    {
        $query = 'SELECT ' . ($this->sqlParts['distinct'] ? 'DISTINCT ' : '') .
                  implode(', ', $this->sqlParts['select']);

        $query .= ($this->sqlParts['from'] ? ' FROM ' . implode(', ', $this->getFromClauses()) : '')
            . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '')
            . ($this->sqlParts['groupBy'] ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupBy']) : '')
            . ($this->sqlParts['having'] !== null ? ' HAVING ' . ((string) $this->sqlParts['having']) : '')
            . ($this->sqlParts['orderBy'] ? ' ORDER BY ' . implode(', ', $this->sqlParts['orderBy']) : '');

        if ($this->isLimitQuery()) {
            return $this->connection->getDatabasePlatform()->modifyLimitQuery(
                $query,
                $this->maxResults,
                $this->firstResult
            );
        }

        return $query;
    }

    /**
     * @return string[]
     *
     * @throws QueryException
     */
    private function getFromClauses()
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
     * @return bool
     */
    private function isLimitQuery()
    {
        return $this->maxResults !== null || $this->firstResult !== null;
    }

    /**
     * Converts this instance into an INSERT string in SQL.
     *
     * @return string
     */
    private function getSQLForInsert()
    {
        return 'INSERT INTO ' . $this->sqlParts['from']['table'] .
        ' (' . implode(', ', array_keys($this->sqlParts['values'])) . ')' .
        ' VALUES(' . implode(', ', $this->sqlParts['values']) . ')';
    }

    /**
     * Converts this instance into an UPDATE string in SQL.
     *
     * @return string
     */
    private function getSQLForUpdate()
    {
        $table = $this->sqlParts['from']['table']
            . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');

        return 'UPDATE ' . $table
            . ' SET ' . implode(', ', $this->sqlParts['set'])
            . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');
    }

    /**
     * Converts this instance into a DELETE string in SQL.
     *
     * @return string
     */
    private function getSQLForDelete()
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
     * This method provides a shortcut for {@link Statement::bindValue()}
     * when using prepared statements.
     *
     * The parameter $value specifies the value that you want to bind. If
     * $placeholder is not provided bindValue() will automatically create a
     * placeholder for you. An automatic placeholder will be of the name
     * ':dcValue1', ':dcValue2' etc.
     *
     * Example:
     * <code>
     * $value = 2;
     * $q->eq( 'id', $q->bindValue( $value ) );
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
     * @return string
     *
     * @throws QueryException
     */
    private function getSQLForJoins($fromAlias, array &$knownAliases)
    {
        $sql = '';

        if (isset($this->sqlParts['join'][$fromAlias])) {
            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                if (array_key_exists($join['joinAlias'], $knownAliases)) {
                    throw QueryException::nonUniqueAlias($join['joinAlias'], array_keys($knownAliases));
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
}
