<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\DB\QueryBuilder;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;

/**
 * This class provides a wrapper around Doctrine's ExpressionBuilder
 * @since 8.2.0
 *
 * @psalm-taint-specialize
 */
interface IExpressionBuilder {
	/**
	 * @since 9.0.0
	 */
	public const EQ = ExpressionBuilder::EQ;
	/**
	 * @since 9.0.0
	 */
	public const NEQ = ExpressionBuilder::NEQ;
	/**
	 * @since 9.0.0
	 */
	public const LT = ExpressionBuilder::LT;
	/**
	 * @since 9.0.0
	 */
	public const LTE = ExpressionBuilder::LTE;
	/**
	 * @since 9.0.0
	 */
	public const GT = ExpressionBuilder::GT;
	/**
	 * @since 9.0.0
	 */
	public const GTE = ExpressionBuilder::GTE;

	/**
	 * Creates a conjunction of the given boolean expressions.
	 *
	 * Example:
	 *
	 *     [php]
	 *     // (u.type = ?) AND (u.role = ?)
	 *     $expr->andX('u.type = ?', 'u.role = ?'));
	 *
	 * @param mixed ...$x Optional clause. Defaults = null, but requires
	 *                 at least one defined when converting to string.
	 *
	 * @return \OCP\DB\QueryBuilder\ICompositeExpression
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function andX(...$x): ICompositeExpression;

	/**
	 * Creates a disjunction of the given boolean expressions.
	 *
	 * Example:
	 *
	 *     [php]
	 *     // (u.type = ?) OR (u.role = ?)
	 *     $qb->where($qb->expr()->orX('u.type = ?', 'u.role = ?'));
	 *
	 * @param mixed ...$x Optional clause. Defaults = null, but requires
	 *                 at least one defined when converting to string.
	 *
	 * @return \OCP\DB\QueryBuilder\ICompositeExpression
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function orX(...$x): ICompositeExpression;

	/**
	 * Creates a comparison expression.
	 *
	 * @param mixed $x The left expression.
	 * @param string $operator One of the IExpressionBuilder::* constants.
	 * @param mixed $y The right expression.
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $operator
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function comparison($x, string $operator, $y, $type = null): string;

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
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function eq($x, $y, $type = null): string;

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
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function neq($x, $y, $type = null): string;

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
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function lt($x, $y, $type = null): string;

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
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function lte($x, $y, $type = null): string;

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
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function gt($x, $y, $type = null): string;

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
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function gte($x, $y, $type = null): string;

	/**
	 * Creates an IS NULL expression with the given arguments.
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be restricted by IS NULL.
	 *
	 * @return string
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function isNull($x): string;

	/**
	 * Creates an IS NOT NULL expression with the given arguments.
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be restricted by IS NOT NULL.
	 *
	 * @return string
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function isNotNull($x): string;

	/**
	 * Creates a LIKE() comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x Field in string format to be inspected by LIKE() comparison.
	 * @param mixed $y Argument to be used in LIKE() comparison.
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function like($x, $y, $type = null): string;

	/**
	 * Creates a NOT LIKE() comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x Field in string format to be inspected by NOT LIKE() comparison.
	 * @param mixed $y Argument to be used in NOT LIKE() comparison.
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function notLike($x, $y, $type = null): string;

	/**
	 * Creates a ILIKE() comparison expression with the given arguments.
	 *
	 * @param string $x Field in string format to be inspected by ILIKE() comparison.
	 * @param mixed $y Argument to be used in ILIKE() comparison.
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function iLike($x, $y, $type = null): string;

	/**
	 * Creates a IN () comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x The field in string format to be inspected by IN() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string|array $y The placeholder or the array of values to be used by IN() comparison.
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function in($x, $y, $type = null): string;

	/**
	 * Creates a NOT IN () comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x The field in string format to be inspected by NOT IN() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string|array $y The placeholder or the array of values to be used by NOT IN() comparison.
	 * @param mixed|null $type one of the IQueryBuilder::PARAM_* constants
	 *                  required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function notIn($x, $y, $type = null): string;

	/**
	 * Creates a $x = '' statement, because Oracle needs a different check
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be inspected by the comparison.
	 * @return string
	 * @since 13.0.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function emptyString($x): string;

	/**
	 * Creates a `$x <> ''` statement, because Oracle needs a different check
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be inspected by the comparison.
	 * @return string
	 * @since 13.0.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function nonEmptyString($x): string;


	/**
	 * Creates a bitwise AND comparison
	 *
	 * @param string|ILiteral $x The field or value to check
	 * @param int $y Bitmap that must be set
	 * @return IQueryFunction
	 * @since 12.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 */
	public function bitwiseAnd($x, int $y): IQueryFunction;

	/**
	 * Creates a bitwise OR comparison
	 *
	 * @param string|ILiteral $x The field or value to check
	 * @param int $y Bitmap that must be set
	 * @return IQueryFunction
	 * @since 12.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 */
	public function bitwiseOr($x, int $y): IQueryFunction;

	/**
	 * Quotes a given input parameter.
	 *
	 * @param mixed $input The parameter to be quoted.
	 * @param int $type One of the IQueryBuilder::PARAM_* constants
	 *
	 * @return ILiteral
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $input
	 * @psalm-taint-sink sql $type
	 */
	public function literal($input, $type = IQueryBuilder::PARAM_STR): ILiteral;

	/**
	 * Returns a IQueryFunction that casts the column to the given type
	 *
	 * @param string|IQueryFunction $column
	 * @param mixed $type One of IQueryBuilder::PARAM_*
	 * @psalm-param IQueryBuilder::PARAM_* $type
	 * @return IQueryFunction
	 * @since 9.0.0
	 *
	 * @psalm-taint-sink sql $column
	 * @psalm-taint-sink sql $type
	 */
	public function castColumn($column, $type): IQueryFunction;
}
