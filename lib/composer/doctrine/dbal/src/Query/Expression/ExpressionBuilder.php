<?php

namespace Doctrine\DBAL\Query\Expression;

use Doctrine\DBAL\Connection;

use function func_get_arg;
use function func_get_args;
use function func_num_args;
use function implode;
use function sprintf;

/**
 * ExpressionBuilder class is responsible to dynamically create SQL query parts.
 */
class ExpressionBuilder
{
    public const EQ  = '=';
    public const NEQ = '<>';
    public const LT  = '<';
    public const LTE = '<=';
    public const GT  = '>';
    public const GTE = '>=';

    /**
     * The DBAL Connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Initializes a new <tt>ExpressionBuilder</tt>.
     *
     * @param Connection $connection The DBAL Connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Creates a conjunction of the given expressions.
     *
     * @param string|CompositeExpression $expression
     * @param string|CompositeExpression ...$expressions
     */
    public function and($expression, ...$expressions): CompositeExpression
    {
        return CompositeExpression::and($expression, ...$expressions);
    }

    /**
     * Creates a disjunction of the given expressions.
     *
     * @param string|CompositeExpression $expression
     * @param string|CompositeExpression ...$expressions
     */
    public function or($expression, ...$expressions): CompositeExpression
    {
        return CompositeExpression::or($expression, ...$expressions);
    }

    /**
     * @deprecated Use `and()` instead.
     *
     * @param mixed $x Optional clause. Defaults = null, but requires
     *                 at least one defined when converting to string.
     *
     * @return CompositeExpression
     */
    public function andX($x = null)
    {
        return new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
    }

    /**
     * @deprecated Use `or()` instead.
     *
     * @param mixed $x Optional clause. Defaults = null, but requires
     *                 at least one defined when converting to string.
     *
     * @return CompositeExpression
     */
    public function orX($x = null)
    {
        return new CompositeExpression(CompositeExpression::TYPE_OR, func_get_args());
    }

    /**
     * Creates a comparison expression.
     *
     * @param mixed  $x        The left expression.
     * @param string $operator One of the ExpressionBuilder::* constants.
     * @param mixed  $y        The right expression.
     *
     * @return string
     */
    public function comparison($x, $operator, $y)
    {
        return $x . ' ' . $operator . ' ' . $y;
    }

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>. Example:
     *
     *     [php]
     *     // u.id = ?
     *     $expr->eq('u.id', '?');
     *
     * @param mixed $x The left expression.
     * @param mixed $y The right expression.
     *
     * @return string
     */
    public function eq($x, $y)
    {
        return $this->comparison($x, self::EQ, $y);
    }

    /**
     * Creates a non equality comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <> <right expr>. Example:
     *
     *     [php]
     *     // u.id <> 1
     *     $q->where($q->expr()->neq('u.id', '1'));
     *
     * @param mixed $x The left expression.
     * @param mixed $y The right expression.
     *
     * @return string
     */
    public function neq($x, $y)
    {
        return $this->comparison($x, self::NEQ, $y);
    }

    /**
     * Creates a lower-than comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> < <right expr>. Example:
     *
     *     [php]
     *     // u.id < ?
     *     $q->where($q->expr()->lt('u.id', '?'));
     *
     * @param mixed $x The left expression.
     * @param mixed $y The right expression.
     *
     * @return string
     */
    public function lt($x, $y)
    {
        return $this->comparison($x, self::LT, $y);
    }

    /**
     * Creates a lower-than-equal comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <= <right expr>. Example:
     *
     *     [php]
     *     // u.id <= ?
     *     $q->where($q->expr()->lte('u.id', '?'));
     *
     * @param mixed $x The left expression.
     * @param mixed $y The right expression.
     *
     * @return string
     */
    public function lte($x, $y)
    {
        return $this->comparison($x, self::LTE, $y);
    }

    /**
     * Creates a greater-than comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> > <right expr>. Example:
     *
     *     [php]
     *     // u.id > ?
     *     $q->where($q->expr()->gt('u.id', '?'));
     *
     * @param mixed $x The left expression.
     * @param mixed $y The right expression.
     *
     * @return string
     */
    public function gt($x, $y)
    {
        return $this->comparison($x, self::GT, $y);
    }

    /**
     * Creates a greater-than-equal comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> >= <right expr>. Example:
     *
     *     [php]
     *     // u.id >= ?
     *     $q->where($q->expr()->gte('u.id', '?'));
     *
     * @param mixed $x The left expression.
     * @param mixed $y The right expression.
     *
     * @return string
     */
    public function gte($x, $y)
    {
        return $this->comparison($x, self::GTE, $y);
    }

    /**
     * Creates an IS NULL expression with the given arguments.
     *
     * @param string $x The expression to be restricted by IS NULL.
     *
     * @return string
     */
    public function isNull($x)
    {
        return $x . ' IS NULL';
    }

    /**
     * Creates an IS NOT NULL expression with the given arguments.
     *
     * @param string $x The expression to be restricted by IS NOT NULL.
     *
     * @return string
     */
    public function isNotNull($x)
    {
        return $x . ' IS NOT NULL';
    }

    /**
     * Creates a LIKE() comparison expression with the given arguments.
     *
     * @param string $x Field in string format to be inspected by LIKE() comparison.
     * @param mixed  $y Argument to be used in LIKE() comparison.
     *
     * @return string
     */
    public function like($x, $y/*, ?string $escapeChar = null */)
    {
        return $this->comparison($x, 'LIKE', $y) .
            (func_num_args() >= 3 ? sprintf(' ESCAPE %s', func_get_arg(2)) : '');
    }

    /**
     * Creates a NOT LIKE() comparison expression with the given arguments.
     *
     * @param string $x Field in string format to be inspected by NOT LIKE() comparison.
     * @param mixed  $y Argument to be used in NOT LIKE() comparison.
     *
     * @return string
     */
    public function notLike($x, $y/*, ?string $escapeChar = null */)
    {
        return $this->comparison($x, 'NOT LIKE', $y) .
            (func_num_args() >= 3 ? sprintf(' ESCAPE %s', func_get_arg(2)) : '');
    }

    /**
     * Creates a IN () comparison expression with the given arguments.
     *
     * @param string          $x The field in string format to be inspected by IN() comparison.
     * @param string|string[] $y The placeholder or the array of values to be used by IN() comparison.
     *
     * @return string
     */
    public function in($x, $y)
    {
        return $this->comparison($x, 'IN', '(' . implode(', ', (array) $y) . ')');
    }

    /**
     * Creates a NOT IN () comparison expression with the given arguments.
     *
     * @param string          $x The expression to be inspected by NOT IN() comparison.
     * @param string|string[] $y The placeholder or the array of values to be used by NOT IN() comparison.
     *
     * @return string
     */
    public function notIn($x, $y)
    {
        return $this->comparison($x, 'NOT IN', '(' . implode(', ', (array) $y) . ')');
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed    $input The parameter to be quoted.
     * @param int|null $type  The type of the parameter.
     *
     * @return string
     */
    public function literal($input, $type = null)
    {
        return $this->connection->quote($input, $type);
    }
}
