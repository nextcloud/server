<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB\QueryBuilder;

use OCP\AppFramework\Attribute\Consumable;

/**
 * This class provides a builder for sql some functions
 *
 * @since 12.0.0
 */
#[Consumable(since: '12.0.0')]
interface IFunctionBuilder {
	/**
	 * Calculates the MD5 hash of a given input
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $input The input to be hashed
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function md5(string|ILiteral|IParameter|IQueryFunction $input): IQueryFunction;

	/**
	 * Combines two input strings
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x Expressions or literal strings
	 * @param string|ILiteral|IParameter|IQueryFunction ...$exprs Expressions or literal strings
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function concat(string|ILiteral|IParameter|IQueryFunction $x, string|ILiteral|IParameter|IQueryFunction ...$expr): IQueryFunction;

	/**
	 * Returns a string which is the concatenation of all non-NULL values of X
	 *
	 * Usage examples:
	 *
	 * groupConcat('column') -- with comma as separator (default separator)
	 *
	 * groupConcat('column', ';') -- with different separator
	 *
	 * @param string|IQueryFunction $expr The expression to group
	 * @param string|null $separator The separator
	 * @return IQueryFunction
	 * @since 24.0.0
	 */
	public function groupConcat(string|IQueryFunction $expr, ?string $separator = ','): IQueryFunction;

	/**
	 * Takes a substring from the input string
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $input The input string
	 * @param string|ILiteral|IParameter|IQueryFunction $start The start of the substring, note that counting starts at 1
	 * @param null|ILiteral|IParameter|IQueryFunction $length The length of the substring
	 *
	 * @since 12.0.0
	 */
	public function substring(
		string|ILiteral|IParameter|IQueryFunction $input,
		string|ILiteral|IParameter|IQueryFunction $start,
		null|ILiteral|IParameter|IQueryFunction $length = null,
	): IQueryFunction;

	/**
	 * Takes the sum of all rows in a column
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field the column to sum
	 *
	 * @since 12.0.0
	 */
	public function sum(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction;

	/**
	 * Transforms a string field or value to lower case
	 *
	 * @since 14.0.0
	 */
	public function lower(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $x The first input field or number
	 * @param string|ILiteral|IParameter|IQueryFunction $y The second input field or number
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function add(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $x The first input field or number
	 * @param string|ILiteral|IParameter|IQueryFunction $y The second input field or number
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function subtract(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $count The input to be counted
	 * @param string $alias Alias for the counter
	 *
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function count(string|ILiteral|IParameter|IQueryFunction $count = '', string $alias = ''): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $field The input to be measured
	 * @param string $alias Alias for the length
	 *
	 * @return IQueryFunction
	 * @since 24.0.0
	 */
	public function octetLength(string|ILiteral|IParameter|IQueryFunction $field, string $alias = ''): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $field The input to be measured
	 * @param string $alias Alias for the length
	 *
	 * @since 24.0.0
	 */
	public function charLength(string|ILiteral|IParameter|IQueryFunction $field, string $alias = ''): IQueryFunction;

	/**
	 * Takes the maximum of all rows in a column
	 *
	 * If you want to get the maximum value of multiple columns in the same row, use `greatest` instead
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field the column to maximum
	 *
	 * @since 18.0.0
	 */
	public function max(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction;

	/**
	 * Takes the minimum of all rows in a column
	 *
	 * If you want to get the minimum value of multiple columns in the same row, use `least` instead
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field the column to minimum
	 *
	 * @since 18.0.0
	 */
	public function min(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction;

	/**
	 * Takes the maximum of multiple values
	 *
	 * If you want to get the maximum value of all rows in a column, use `max` instead
	 *
	 * @since 18.0.0
	 */
	public function greatest(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction;

	/**
	 * Takes the minimum of multiple values
	 *
	 * If you want to get the minimum value of all rows in a column, use `min` instead
	 *
	 * @since 18.0.0
	 */
	public function least(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction;

	/**
	 * Get the current date and time as a UNIX timestamp.
	 * @since 34.0.0
	 */
	public function now(): IQueryFunction;
}
