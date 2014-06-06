<?php
/**
 * ownCloud
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Search;

/**
 * The generic result of a search
 */
class Result {

	/**
	 * A unique identifier for the result, usually given as the item ID in its
	 * corresponding application.
	 * @var string
	 */
	public $id;

	/**
	 * The name of the item returned; this will be displayed in the search
	 * results.
	 * @var string
	 */
	public $name;

	/**
	 * URL to the application item.
	 * @var string
	 */
	public $link;

	/**
	 * The type of search result returned; for consistency, name this the same
	 * as the class name (e.g. \OC\Search\File -> 'file') in lowercase. 
	 * @var string
	 */
	public $type = 'generic';

	/**
	 * Create a new search result
	 * @param string $id unique identifier from application: '[app_name]/[item_identifier_in_app]'
	 * @param string $name displayed text of result
	 * @param string $link URL to the result within its app
	 */
	public function __construct($id = null, $name = null, $link = null) {
		$this->id = $id;
		$this->name = $name;
		$this->link = $link;
	}
}
