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
 */
class OC_Search{
	static private $providers=array();
	static private $registeredProviders=array();

	/**
	 * remove all registered search providers
	 */
	public static function clearProviders() {
		self::$providers=array();
		self::$registeredProviders=array();
	}

	/**
	 * register a new search provider to be used
	 * @param string $provider class name of a OC_Search_Provider
	 */
	public static function registerProvider($class, $options=array()) {
		self::$registeredProviders[]=array('class'=>$class, 'options'=>$options);
	}

	/**
	 * search all provider for $query
	 * @param string query
	 * @return array An array of OC_Search_Result's
	 */
	public static function search($query) {
		self::initProviders();
		$results=array();
		foreach(self::$providers as $provider) {
			$results=array_merge($results, $provider->search($query));
		}
		return $results;
	}

	/**
	 * remove an existing search provider
	 * @param string $provider class name of a OC_Search_Provider
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
	 * create instances of all the registered search providers
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
