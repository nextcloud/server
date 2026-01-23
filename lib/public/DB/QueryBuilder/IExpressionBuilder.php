<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\DB\QueryBuilder;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use OCP\AppFramework\Attribute\Consumable;

/**
 * This class provides a wrapper around Doctrine's ExpressionBuilder
 * @since 8.2.0
 *
 * @psalm-taint-specialize
 */
#[Consumable(since: '8.2.0')]
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
	 * @param ICompositeExpression|string ...$x Optional clause. Defaults = null, but requires
	 *                                          at least one defined when converting to string.
	 *
	 * @since 8.2.0
	 * @since 30.0.0 Calling the method without any arguments is deprecated and will throw with the next Doctrine/DBAL update
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function andX(ICompositeExpression|string ...$x): ICompositeExpression;

	/**
	 * Creates a disjunction of the given boolean expressions.
	 *
	 * Example:
	 *
	 *     [php]
	 *     // (u.type = ?) OR (u.role = ?)
	 *     $qb->where($qb->expr()->orX('u.type = ?', 'u.role = ?'));
	 *
	 * @param ICompositeExpression|string ...$x Optional clause. Defaults = null, but requires
	 *                                          at least one defined when converting to string.
	 *
	 * @since 8.2.0
	 * @since 30.0.0 Calling the method without any arguments is deprecated and will throw with the next Doctrine/DBAL update
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function orX(ICompositeExpression|string ...$x): ICompositeExpression;

	/**
	 * Creates a comparison expression.
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string $operator One of the IExpressionBuilder::* constants.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $operator
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function comparison(string|ILiteral|IQueryFunction|IParameter $x, string $operator, string|ILiteral|IQueryFunction|IParameter $y, string|int|null $type = null): string;

	/**
	 * Creates an equality comparison expression with the given arguments.
	 *
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generate a <left expr> = <right expr>. Example:
	 *
	 *     [php]
	 *     // u.id = ?
	 *     $expr->eq('u.id', '?');
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function eq(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, string|int|null $type = null): string;

	/**
	 * Creates a non equality comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generate a <left expr> <> <right expr>. Example:
	 *
	 *     [php]
	 *     // u.id <> 1
	 *     $q->where($q->expr()->neq('u.id', '1'));
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function neq(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, string|int|null $type = null): string;

	/**
	 * Creates a lower-than comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generate a <left expr> < <right expr>. Example:
	 *
	 *     [php]
	 *     // u.id < ?
	 *     $q->where($q->expr()->lt('u.id', '?'));
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function lt(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, string|int|null $type = null): string;

	/**
	 * Creates a lower-than-equal comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generate a <left expr> <= <right expr>. Example:
	 *
	 *     [php]
	 *     // u.id <= ?
	 *     $q->where($q->expr()->lte('u.id', '?'));
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function lte(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, string|int|null $type = null): string;

	/**
	 * Creates a greater-than comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generate a <left expr> > <right expr>. Example:
	 *
	 *     [php]
	 *     // u.id > ?
	 *     $q->where($q->expr()->gt('u.id', '?'));
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function gt(
		string|ILiteral|IQueryFunction|IParameter $x,
		string|ILiteral|IQueryFunction|IParameter $y,
		string|int|null $type = null,
	): string;

	/**
	 * Creates a greater-than-equal comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generate a <left expr> >= <right expr>. Example:
	 *
	 *     [php]
	 *     // u.id >= ?
	 *     $q->where($q->expr()->gte('u.id', '?'));
	 *
	 * @param string|ILiteral|IQueryFunction|IParameter $x The left expression.
	 * @param string|ILiteral|IQueryFunction|IParameter $y The right expression.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function gte(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
		int|string|null $type = null,
	): string;

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
	public function isNull(string|ILiteral|IParameter|IQueryFunction $x): string;

	/**
	 * Creates an IS NOT NULL expression with the given arguments.
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be restricted by IS NOT NULL.
	 *
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function isNotNull(string|ILiteral|IParameter|IQueryFunction $x): string;

	/**
	 * Creates a LIKE() comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x Field in string format to be inspected by LIKE() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string $y Argument to be used in LIKE() comparison.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function like(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string $y,
		int|string|null $type = null,
	): string;

	/**
	 * Creates a NOT LIKE() comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x Field in string format to be inspected by NOT LIKE() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string $y Argument to be used in NOT LIKE() comparison.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function notLike(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string $y,
		int|string|null $type = null,
	): string;

	/**
	 * Creates an ILIKE() comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x Field in string format to be inspected by ILIKE() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string $y Argument to be used in ILIKE() comparison.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function iLike(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string $y,
		int|string|null $type = null,
	): string;

	/**
	 * Creates an IN () comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x The field in string format to be inspected by IN() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string|array $y The placeholder or the array of values to be used by IN() comparison.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function in(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string|array $y,
		int|string|null $type = null,
	): string;

	/**
	 * Creates a NOT IN () comparison expression with the given arguments.
	 *
	 * @param ILiteral|IParameter|IQueryFunction|string $x The field in string format to be inspected by NOT IN() comparison.
	 * @param ILiteral|IParameter|IQueryFunction|string|array $y The placeholder or the array of values to be used by NOT IN() comparison.
	 * @param IQueryBuilder::PARAM_*|null $type one of the IQueryBuilder::PARAM_* constants
	 *                                          required when comparing text fields for oci compatibility
	 *
	 * @return string
	 * @since 8.2.0 - Parameter $type was added in 9.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 * @psalm-taint-sink sql $type
	 */
	public function notIn(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string|array $y,
		int|string|null $type = null,
	): string;

	/**
	 * Creates a `$x = ''` statement, because Oracle needs a different check
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be inspected by the comparison.
	 * @since 13.0.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function emptyString(string|ILiteral|IParameter|IQueryFunction $x): string;

	/**
	 * Creates a `$x <> ''` statement, because Oracle needs a different check
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be inspected by the comparison.
	 * @since 13.0.0
	 *
	 * @psalm-taint-sink sql $x
	 */
	public function nonEmptyString(string|ILiteral|IParameter|IQueryFunction $x): string;


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
	public function bitwiseAnd(string|ILiteral $x, int $y): IQueryFunction;

	/**
	 * Creates a bitwise OR comparison
	 *
	 * @param string|ILiteral $x The field or value to check
	 * @param int $y Bitmap that must be set
	 * @since 12.0.0
	 *
	 * @psalm-taint-sink sql $x
	 * @psalm-taint-sink sql $y
	 */
	public function bitwiseOr(string|ILiteral $x, int $y): IQueryFunction;

	/**
	 * Quotes a given input parameter.
	 *
	 * @param mixed $input The parameter to be quoted.
	 * @param IQueryBuilder::PARAM_* $type One of the IQueryBuilder::PARAM_* constants
	 *
	 * @since 8.2.0
	 *
	 * @psalm-taint-sink sql $input
	 * @psalm-taint-sink sql $type
	 */
	public function literal(mixed $input, int|string $type = IQueryBuilder::PARAM_STR): ILiteral;

	/**
	 * Returns a IQueryFunction that casts the column to the given type
	 *
	 * @param IQueryBuilder::PARAM_* $type
	 * @since 9.0.0
	 *
	 * @psalm-taint-sink sql $column
	 * @psalm-taint-sink sql $type
	 */
	public function castColumn(string|IQueryFunction|ILiteral|IParameter $column, int|string $type): IQueryFunction;
}
