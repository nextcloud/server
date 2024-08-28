<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Search;

/**
 * The generic result of a search
 * @since 7.0.0
 * @deprecated 20.0.0
 */
class Result {
	/**
	 * A unique identifier for the result, usually given as the item ID in its
	 * corresponding application.
	 * @var string
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public $id;

	/**
	 * The name of the item returned; this will be displayed in the search
	 * results.
	 * @var string
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public $name;

	/**
	 * URL to the application item.
	 * @var string
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public $link;

	/**
	 * The type of search result returned; for consistency, name this the same
	 * as the class name (e.g. \OC\Search\File -> 'file') in lowercase.
	 * @var string
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public $type = 'generic';

	/**
	 * Create a new search result
	 * @param string $id unique identifier from application: '[app_name]/[item_identifier_in_app]'
	 * @param string $name displayed text of result
	 * @param string $link URL to the result within its app
	 * @since 7.0.0
	 * @deprecated 20.0.0
	 */
	public function __construct($id = null, $name = null, $link = null) {
		$this->id = $id;
		$this->name = $name;
		$this->link = $link;
	}
}
