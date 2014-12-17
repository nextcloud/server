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
 * Provides a template for search functionality throughout ownCloud; 
 */
abstract class PagedProvider extends Provider {

	/**
	 * List of options (currently unused)
	 * @var array
	 */
	private $options;

	/**
	 * Constructor
	 * @param array $options
	 */
	public function __construct($options) {
		$this->options = $options;
	}

	/**
	 * Search for $query
	 * @param string $query
	 * @return array An array of OCP\Search\Result's
	 */
	public function search($query) {
		// old apps might assume they get all results, so we set size 0
		$this->searchPaged($query, 1, 0);
	}

	/**
	 * Search for $query
	 * @param string $query
	 * @param int $page pages start at page 1
	 * @param int $size, 0 = all
	 * @return array An array of OCP\Search\Result's
	 */
	abstract public function searchPaged($query, $page, $size);
}
