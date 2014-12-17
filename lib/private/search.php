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
use OCP\Search\PagedProvider;
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
	 * @param string[] $inApps optionally limit results to the given apps
	 * @return array An array of OC\Search\Result's
	 */
	public function search($query, array $inApps = array()) {
		// old apps might assume they get all results, so we set size 0
		return $this->searchPaged($query, $inApps, 1, 0);
	}

	/**
	 * Search all providers for $query
	 * @param string $query
	 * @param string[] $inApps optionally limit results to the given apps
	 * @param int $page pages start at page 1
	 * @param int $size, 0 = all
	 * @return array An array of OC\Search\Result's
	 */
	public function searchPaged($query, array $inApps = array(), $page = 1, $size = 30) {
		$this->initProviders();
		$results = array();
		foreach($this->providers as $provider) {
			/** @var $provider Provider */
			if ( ! $provider->providesResultsFor($inApps) ) {
				continue;
			}
			if ($provider instanceof PagedProvider) {
				$results = array_merge($results, $provider->searchPaged($query, $page, $size));
			} else if ($provider instanceof Provider) {
				$providerResults = $provider->search($query);
				if ($size > 0) {
					$slicedResults = array_slice($providerResults, ($page - 1) * $size, $size);
					$results = array_merge($results, $slicedResults);
				} else {
					$results = array_merge($results, $providerResults);
				}
			} else {
				\OC::$server->getLogger()->warning('Ignoring Unknown search provider', array('provider' => $provider));
			}
		}
		return $results;
	}

	/**
	 * Remove all registered search providers
	 */
	public function clearProviders() {
		$this->providers = array();
		$this->registeredProviders = array();
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
		$this->providers = array();
	}

	/**
	 * Register a new search provider to search with
	 * @param string $class class name of a OC\Search\Provider
	 * @param array $options optional
	 */
	public function registerProvider($class, array $options = array()) {
		$this->registeredProviders[] = array('class' => $class, 'options' => $options);
	}

	/**
	 * Create instances of all the registered search providers
	 */
	private function initProviders() {
		if( ! empty($this->providers) ) {
			return;
		}
		foreach($this->registeredProviders as $provider) {
			$class = $provider['class'];
			$options = $provider['options'];
			$this->providers[] = new $class($options);
		}
	}

}
