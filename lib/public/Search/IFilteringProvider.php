<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

/**
 * Interface for advanced search providers
 *
 * These providers will be implemented in apps, so they can participate in the
 * global search results of Nextcloud. If an app provides more than one type of
 * resource, e.g. contacts and address books in Nextcloud Contacts, it should
 * register one provider per group.
 *
 * @since 28.0.0
 */
interface IFilteringProvider extends IProvider {
	/**
	 * Return the names of filters supported by the application
	 *
	 * If a filter sent by client is not in this list,
	 * the current provider will be ignored.
	 * Example:
	 *   array('term', 'since', 'custom-filter');
	 *
	 * @since 28.0.0
	 * @return string[] Name of supported filters (default or defined by application)
	 */
	public function getSupportedFilters(): array;

	/**
	 * Get alternate IDs handled by this provider
	 *
	 * A search provider can complete results from other search providers.
	 * For example, files and full-text-search can search in files.
	 * If you use `in:files` in a search, provider files will be invoked,
	 * with all other providers declaring `files` in this method
	 *
	 * @since 28.0.0
	 * @return string[] IDs
	 */
	public function getAlternateIds(): array;

	/**
	 * Allows application to declare custom filters
	 *
	 * @since 28.0.0
	 * @return list<FilterDefinition>
	 */
	public function getCustomFilters(): array;
}
