<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

use IteratorAggregate;

/**
 * Interface for search filters
 *
 * @since 28.0.0
 * @extends IteratorAggregate<string, \OCP\Search\IFilter>
 */
interface IFilterCollection extends IteratorAggregate {
	/**
	 * Check if a filter exits
	 *
	 * @since 28.0.0
	 */
	public function has(string $name): bool;

	/**
	 * Get a filter by name
	 *
	 * @since 28.0.0
	 */
	public function get(string $name): ?IFilter;

	/**
	 * Return Iterator of filters
	 *
	 * @since 28.0.0
	 */
	public function getIterator(): \Traversable;
}
