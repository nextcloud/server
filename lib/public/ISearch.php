<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Small Interface for Search
 * @since 7.0.0
 * @deprecated 20.0.0
 */
interface ISearch {
	/**
	 * Search all providers for $query
	 * @param string $query
	 * @param string[] $inApps optionally limit results to the given apps
	 * @param int $page pages start at page 1
	 * @param int $size
	 * @return array An array of OCP\Search\Result's
	 * @since 8.0.0
	 * @deprecated 20.0.0
	 */
	public function searchPaged($query, array $inApps = [], $page = 1, $size = 30);

	/**
	 * Register a new search provider to search with
	 * @param string $class class name of a OCP\Search\Provider
	 * @param array $options optional
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public function registerProvider($class, array $options = []);

	/**
	 * Remove one existing search provider
	 * @param string $provider class name of a OCP\Search\Provider
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public function removeProvider($provider);

	/**
	 * Remove all registered search providers
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public function clearProviders();
}
