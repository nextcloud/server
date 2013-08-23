<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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


/**
 * provides an interface to all search providers
 * @deprecated see lib/search.php
 */
class OC_Search{
	static private $providers=array();
	static private $registeredProviders=array();

	/**
	 * remove all registered search providers
         * @deprecated see lib/search.php
	 */
	public static function clearProviders() {
		return \OC\Search::clearProviders();
	}

	/**
	 * register a new search provider to be used
	 * @param string $provider class name of a OC_Search_Provider
         * @deprecated see lib/search.php
	 */
	public static function registerProvider($class, $options=array()) {
		return \OC\Search::registerProvider($class, $options);
	}

	/**
	 * search all provider for $query
	 * @param string query
	 * @return array An array of OC_Search_Result's
         * @deprecated see lib/search.php
	 */
	public static function search($query) {
		return \OC\Search::search($query);
	}

	/**
	 * remove an existing search provider
	 * @param string $provider class name of a OC_Search_Provider
         * @deprecated see lib/search.php
	 */
	public static function removeProvider($provider) {
		return \OC\Search::removeProvider($provider);
	}
}
