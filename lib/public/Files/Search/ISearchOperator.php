<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Search;

/**
 * @since 12.0.0
 */
interface ISearchOperator {
	/**
	 * Get a query builder hint by name
	 *
	 * @param string $name
	 * @param $default
	 * @return mixed
	 * @since 23.0.0
	 */
	public function getQueryHint(string $name, $default);

	/**
	 * Get a query builder hint
	 *
	 * @param string $name
	 * @param $value
	 * @since 23.0.0
	 */
	public function setQueryHint(string $name, $value): void;
}
