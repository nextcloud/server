<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

/**
 * The query objected passed into \OCP\Search\IProvider::search
 *
 * This mainly wraps the search term, but will ensure that Nextcloud can add new
 * optional properties to a search request without having break the interface of
 * \OCP\Search\IProvider::search.
 *
 * @see \OCP\Search\IProvider::search
 *
 * @since 20.0.0
 */
interface ISearchQuery {
	/**
	 * @since 20.0.0
	 */
	public const SORT_DATE_DESC = 1;

	/**
	 * Get the user-entered search term to find matches for
	 *
	 * @return string the search term
	 * @since 20.0.0
	 */
	public function getTerm(): string;

	/**
	 * Get a single request filter
	 *
	 * @since 28.0.0
	 */
	public function getFilter(string $name): ?IFilter;

	/**
	 * Get request filters
	 *
	 * @since 28.0.0
	 */
	public function getFilters(): IFilterCollection;

	/**
	 * Get the sort order of results as defined as SORT_* constants on this interface
	 *
	 * @return int
	 * @since 20.0.0
	 */
	public function getSortOrder(): int;

	/**
	 * Get the number of items to return for a paginated result
	 *
	 * @return int
	 * @see \OCP\Search\IProvider for details
	 * @since 20.0.0
	 */
	public function getLimit(): int;

	/**
	 * Get the app-specific cursor of the tail of the previous result entries
	 *
	 * @return int|string|null
	 * @see \OCP\Search\IProvider for details
	 * @since 20.0.0
	 */
	public function getCursor();

	/**
	 * Get the offset for pagination (number of results to skip)
	 *
	 * @return int|null
	 * @since 33.0.0
	 */
	public function getOffset(): ?int;

	/**
	 * Get the effective offset for pagination (offset or cursor as fallback)
	 * This method helps with backward compatibility for providers that use cursor as offset
	 *
	 * @return int
	 * @since 33.0.0
	 */
	public function getEffectiveOffset(): int;

	/**
	 * @return string
	 * @since 20.0.0
	 */
	public function getRoute(): string;

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getRouteParameters(): array;
}
