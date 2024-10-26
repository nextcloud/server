<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Model;

/**
 * Interface ISearchRequestSimpleQuery
 *
 * Add a Query during a Search Request...
 * - on a specific field,
 * - using a specific value,
 * - with a specific comparison
 *
 * @since 17.0.0
 *
 */
interface ISearchRequestSimpleQuery {
	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_TEXT = 1;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_KEYWORD = 2;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_INT_EQ = 3;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_INT_GTE = 4;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_INT_GT = 5;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_INT_LTE = 6;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_INT_LT = 7;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_BOOL = 8;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_ARRAY = 9;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_REGEX = 10;

	/**
	 * @since 17.0.0
	 */
	public const COMPARE_TYPE_WILDCARD = 11;


	/**
	 * Get the compare type of the query
	 *
	 * @return int
	 * @since 17.0.0
	 */
	public function getType(): int;


	/**
	 * Get the field to apply query
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getField(): string;

	/**
	 * Set the field to apply query
	 *
	 * @param string $field
	 *
	 * @return ISearchRequestSimpleQuery
	 * @since 17.0.0
	 */
	public function setField(string $field): ISearchRequestSimpleQuery;


	/**
	 * Get the all values to compare
	 *
	 * @return array
	 * @since 17.0.0
	 */
	public function getValues(): array;

	/**
	 * Add value to compare (string)
	 *
	 * @param string $value
	 *
	 * @return ISearchRequestSimpleQuery
	 * @since 17.0.0
	 */
	public function addValue(string $value): ISearchRequestSimpleQuery;

	/**
	 * Add value to compare (int)
	 *
	 * @param int $value
	 *
	 * @return ISearchRequestSimpleQuery
	 * @since 17.0.0
	 */
	public function addValueInt(int $value): ISearchRequestSimpleQuery;

	/**
	 * Add value to compare (array)
	 *
	 * @param array $value
	 *
	 * @return ISearchRequestSimpleQuery
	 * @since 17.0.0
	 */
	public function addValueArray(array $value): ISearchRequestSimpleQuery;

	/**
	 * Add value to compare (bool)
	 *
	 * @param bool $value
	 *
	 * @return ISearchRequestSimpleQuery
	 * @since 17.0.0
	 */
	public function addValueBool(bool $value): ISearchRequestSimpleQuery;
}
