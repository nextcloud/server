<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Search;

/**
 * Provides a template for search functionality throughout ownCloud;
 * @since 8.0.0
 * @deprecated 20.0.0
 */
abstract class PagedProvider extends Provider {
	/**
	 * show all results
	 * @since 8.0.0
	 * @deprecated 20.0.0
	 */
	public const SIZE_ALL = 0;

	/**
	 * Constructor
	 * @param array $options
	 * @since 8.0.0
	 * @deprecated 20.0.0
	 */
	public function __construct($options) {
		parent::__construct($options);
	}

	/**
	 * Search for $query
	 * @param string $query
	 * @return array An array of OCP\Search\Result's
	 * @since 8.0.0
	 * @deprecated 20.0.0
	 */
	public function search($query) {
		// old apps might assume they get all results, so we use SIZE_ALL
		return $this->searchPaged($query, 1, self::SIZE_ALL);
	}

	/**
	 * Search for $query
	 * @param string $query
	 * @param int $page pages start at page 1
	 * @param int $size 0 = SIZE_ALL
	 * @return array An array of OCP\Search\Result's
	 * @since 8.0.0
	 * @deprecated 20.0.0
	 */
	abstract public function searchPaged($query, $page, $size);
}
