<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB\QueryBuilder;

/**
 * This class provides a builder for sql some functions
 *
 * @since 12.0.0
 */
interface IFunctionBuilder {
	/**
	 * Calculates the MD5 hash of a given input
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $input The input to be hashed
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function md5($input): IQueryFunction;

	/**
	 * Combines two input strings
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x Expressions or literal strings
	 * @param string|ILiteral|IParameter|IQueryFunction ...$exprs Expressions or literal strings
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function concat($x, ...$expr): IQueryFunction;

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
	public function groupConcat($expr, ?string $separator = ','): IQueryFunction;

	/**
	 * Takes a substring from the input string
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $input The input string
	 * @param string|ILiteral|IParameter|IQueryFunction $start The start of the substring, note that counting starts at 1
	 * @param null|ILiteral|IParameter|IQueryFunction $length The length of the substring
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function substring($input, $start, $length = null): IQueryFunction;

	/**
	 * Takes the sum of all rows in a column
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field the column to sum
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function sum($field): IQueryFunction;

	/**
	 * Transforms a string field or value to lower case
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function lower($field): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $x The first input field or number
	 * @param string|ILiteral|IParameter|IQueryFunction $y The second input field or number
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function add($x, $y): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $x The first input field or number
	 * @param string|ILiteral|IParameter|IQueryFunction $y The second input field or number
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function subtract($x, $y): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $count The input to be counted
	 * @param string $alias Alias for the counter
	 *
	 * @return IQueryFunction
	 * @since 14.0.0
	 */
	public function count($count = '', $alias = ''): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $field The input to be measured
	 * @param string $alias Alias for the length
	 *
	 * @return IQueryFunction
	 * @since 24.0.0
	 */
	public function octetLength($field, $alias = ''): IQueryFunction;

	/**
	 * @param string|ILiteral|IParameter|IQueryFunction $field The input to be measured
	 * @param string $alias Alias for the length
	 *
	 * @return IQueryFunction
	 * @since 24.0.0
	 */
	public function charLength($field, $alias = ''): IQueryFunction;

	/**
	 * Takes the maximum of all rows in a column
	 *
	 * If you want to get the maximum value of multiple columns in the same row, use `greatest` instead
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field the column to maximum
	 *
	 * @return IQueryFunction
	 * @since 18.0.0
	 */
	public function max($field): IQueryFunction;

	/**
	 * Takes the minimum of all rows in a column
	 *
	 * If you want to get the minimum value of multiple columns in the same row, use `least` instead
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $field the column to minimum
	 *
	 * @return IQueryFunction
	 * @since 18.0.0
	 */
	public function min($field): IQueryFunction;

	/**
	 * Takes the maximum of multiple values
	 *
	 * If you want to get the maximum value of all rows in a column, use `max` instead
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x
	 * @param string|ILiteral|IParameter|IQueryFunction $y
	 * @return IQueryFunction
	 * @since 18.0.0
	 */
	public function greatest($x, $y): IQueryFunction;

	/**
	 * Takes the minimum of multiple values
	 *
	 * If you want to get the minimum value of all rows in a column, use `min` instead
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x
	 * @param string|ILiteral|IParameter|IQueryFunction $y
	 * @return IQueryFunction
	 * @since 18.0.0
	 */
	public function least($x, $y): IQueryFunction;
}
