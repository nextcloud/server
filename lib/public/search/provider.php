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
abstract class Provider {

	const OPTION_APPS = 'apps';

	/**
	 * List of options
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor
	 * @param array $options as key => value
	 */
	public function __construct($options = array()) {
		$this->options = $options;
	}

	/**
	 * get a value from the options array or null
	 * @param string $key
	 * @return mixed
	 */
	public function getOption($key) {
		if (is_array($this->options) && isset($this->options[$key])) {
			return $this->options[$key];
		} else {
			return null;
		}
	}

	/**
	 * checks if the given apps and the apps this provider has results for intersect
	 * returns true if the given array is empty (all apps)
	 * or if this provider does not have a list of apps it provides results for (legacy search providers)
	 * or if the two above arrays have elements in common (intersect)
	 * @param string[] $apps
	 * @return bool
	 */
	public function providesResultsFor(array $apps = array()) {
		$forApps = $this->getOption(self::OPTION_APPS);
		return empty($apps) || empty($forApps) || array_intersect($forApps, $apps);
	}

	/**
	 * Search for $query
	 * @param string $query
	 * @return array An array of OCP\Search\Result's
	 */
	abstract public function search($query);
}
