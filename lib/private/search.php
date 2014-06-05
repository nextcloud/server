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

namespace OC;
use OC\Search\Provider;

/**
 * Provide an interface to all search providers
 */
class Search {

	static private $providers=array();
	static private $registeredProviders=array();

	/**
	 * Search all providers for $query
	 * @param string $query
	 * @return array An array of OC\Search\Result's
	 */
	public static function search($query) {
		self::initProviders();
		$results=array();
		foreach(self::$providers as $provider) {
			/** @var $provider Provider */
			$results=array_merge($results, $provider->search($query));
		}
		return $results;
	}

	/**
	 * Remove all registered search providers
	 */
	public static function clearProviders() {
		self::$providers=array();
		self::$registeredProviders=array();
	}

	/**
	 * Remove one existing search provider
	 * @param string $provider class name of a OC\Search\Provider
	 */
	public static function removeProvider($provider) {
		self::$registeredProviders = array_filter(
			self::$registeredProviders,
			function ($element) use ($provider) {
				return ($element['class'] != $provider);
			}
		);
		// force regeneration of providers on next search
		self::$providers=array();
	}

	/**
	 * Register a new search provider to search with
	 * @param string $class class name of a OC\Search\Provider
	 * @param array $options optional
	 */
	public static function registerProvider($class, $options=array()) {
		self::$registeredProviders[]=array('class'=>$class, 'options'=>$options);
	}

	/**
	 * Create instances of all the registered search providers
	 */
	private static function initProviders() {
		if(count(self::$providers)>0) {
			return;
		}
		foreach(self::$registeredProviders as $provider) {
			$class=$provider['class'];
			$options=$provider['options'];
			self::$providers[]=new $class($options);
		}
	}

}
