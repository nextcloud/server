<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

use OCP\IUser;

/**
 * Interface for search providers
 *
 * These providers will be implemented in apps, so they can participate in the
 * global search results of Nextcloud. If an app provides more than one type of
 * resource, e.g. contacts and address books in Nextcloud Contacts, it should
 * register one provider per group.
 *
 * @since 20.0.0
 */
interface IProvider {
	/**
	 * Get the unique ID of this search provider
	 *
	 * Ideally this should be the app name or an identifier identified with the
	 * app name, especially if the app registers more than one provider.
	 *
	 * Example: 'mail', 'mail_recipients', 'files_sharing'
	 *
	 * @return string
	 *
	 * @since 20.0.0
	 */
	public function getId(): string;

	/**
	 * Get the translated name of this search provider
	 *
	 * Example: 'Mail', 'Contacts'...
	 *
	 * @return string
	 *
	 * @since 20.0.0
	 */
	public function getName(): string;

	/**
	 * Get the search provider order
	 * The lower the int, the higher it will be sorted (0 will be before 10)
	 * If null, the search provider will be hidden in the UI and the API not called
	 *
	 * @param string $route the route the user is currently at, e.g. files.view.index
	 * @param array $routeParameters the parameters of the route the user is currently at, e.g. [fileId = 982, dir = "/"]
	 *
	 * @return int|null
	 *
	 * @since 20.0.0
	 * @since 28.0.0 Can return null
	 */
	public function getOrder(string $route, array $routeParameters): ?int;

	/**
	 * Find matching search entries in an app
	 *
	 * Search results can either be a complete list of all the matches the app can
	 * find, or ideally a paginated result set where more data can be fetched on
	 * demand. To be able to tell where the next offset starts the search uses
	 * "cursors" which are a property of the last result entry. E.g. search results
	 * that show most recent entries first can look for entries older than the last
	 * one of the first result set. This approach was chosen over a numeric limit/
	 * offset approach as the offset moves as new data comes in. The cursor is
	 * resistant to these changes and will still show results without overlaps or
	 * gaps.
	 *
	 * See https://dev.to/jackmarchant/offset-and-cursor-pagination-explained-b89
	 * for the concept of cursors.
	 *
	 * Implementations that return result pages have to adhere to the limit
	 * property of a search query.
	 *
	 * @param IUser $user
	 * @param ISearchQuery $query
	 *
	 * @return SearchResult
	 *
	 * @since 20.0.0
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult;
}
