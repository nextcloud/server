<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Search;

/**
 * @since 12.0.0
 *
 * @psalm-type ParamSingleValue = \DateTime|int|string|bool
 * @psalm-type ParamValue = ParamSingleValue|list<ParamSingleValue>
 */
interface ISearchComparison extends ISearchOperator {
	/**
	 * @since 12.0.0
	 */
	public const COMPARE_EQUAL = 'eq';

	/**
	 * @since 12.0.0
	 */
	public const COMPARE_GREATER_THAN = 'gt';

	/**
	 * @since 12.0.0
	 */
	public const COMPARE_GREATER_THAN_EQUAL = 'gte';

	/**
	 * @since 12.0.0
	 */
	public const COMPARE_LESS_THAN = 'lt';

	/**
	 * @since 12.0.0
	 */
	public const COMPARE_LESS_THAN_EQUAL = 'lte';

	/**
	 * @since 12.0.0
	 */
	public const COMPARE_LIKE = 'like';

	/**
	 * @since 23.0.0
	 */
	public const COMPARE_LIKE_CASE_SENSITIVE = 'clike';

	/**
	 * @since 28.0.0
	 */
	public const COMPARE_DEFINED = 'is-defined';

	/**
	 * @since 29.0.0
	 */
	public const COMPARE_IN = 'in';

	/**
	 * @since 23.0.0
	 */
	public const HINT_PATH_EQ_HASH = 'path_eq_hash'; // transform `path = "$path"` into `path_hash = md5("$path")`, on by default

	/**
	 * Get the type of comparison, one of the ISearchComparison::COMPARE_* constants
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getType(): string;

	/**
	 * Get the name of the field to compare with
	 *
	 * i.e. 'size', 'name' or 'mimetype'
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getField(): string;

	/**
	 * extra means data are not related to the main files table
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getExtra(): string;

	/**
	 * Get the value to compare the field with
	 *
	 * @return ParamValue
	 * @since 12.0.0
	 */
	public function getValue(): string|int|bool|\DateTime|array;
}
