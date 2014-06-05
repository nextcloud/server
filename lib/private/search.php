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
use OCP\Search\Provider;
use OCP\ISearch;

/**
 * Provide an interface to all search providers
 */
class Search implements ISearch {

	private $providers = array();
	private $registeredProviders = array();

	/**
	 * Search all providers for $query
	 * @param string $query
	 * @return array An array of OC\Search\Result's
	 */
	public function search($query) {
		$this->initProviders();
		$results = array();
		foreach($this->providers as $provider) {
			/** @var $provider Provider */
			$results = array_merge($results, $provider->search($query));
		}
		return $results;
	}

	/**
	 * Remove all registered search providers
	 */
	public function clearProviders() {
		$this->providers=array();
		$this->registeredProviders=array();
	}

	/**
	 * Remove one existing search provider
	 * @param string $provider class name of a OC\Search\Provider
	 */
	public function removeProvider($provider) {
		$this->registeredProviders = array_filter(
			$this->registeredProviders,
			function ($element) use ($provider) {
				return ($element['class'] != $provider);
			}
		);
		// force regeneration of providers on next search
		$this->providers=array();
	}

	/**
	 * Register a new search provider to search with
	 * @param string $class class name of a OC\Search\Provider
	 * @param array $options optional
	 */
	public function registerProvider($class, $options=array()) {
		$this->registeredProviders[]=array('class'=>$class, 'options'=>$options);
	}

	/**
	 * Create instances of all the registered search providers
	 */
	private function initProviders() {
		if(count($this->providers)>0) {
			return;
		}
		foreach($this->registeredProviders as $provider) {
			$class = $provider['class'];
			$options = $provider['options'];
			$this->providers[]=new $class($options);
		}
	}

}
