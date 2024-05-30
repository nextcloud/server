<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\ISearch;
use OCP\Search\PagedProvider;
use OCP\Search\Provider;
use Psr\Log\LoggerInterface;

/**
 * Provide an interface to all search providers
 */
class Search implements ISearch {
	/** @var Provider[] */
	private $providers = [];
	private $registeredProviders = [];

	/**
	 * Search all providers for $query
	 * @param string $query
	 * @param string[] $inApps optionally limit results to the given apps
	 * @param int $page pages start at page 1
	 * @param int $size, 0 = all
	 * @return array An array of OC\Search\Result's
	 */
	public function searchPaged($query, array $inApps = [], $page = 1, $size = 30) {
		$this->initProviders();
		$results = [];
		foreach ($this->providers as $provider) {
			if (! $provider->providesResultsFor($inApps)) {
				continue;
			}
			if ($provider instanceof PagedProvider) {
				$results = array_merge($results, $provider->searchPaged($query, $page, $size));
			} elseif ($provider instanceof Provider) {
				$providerResults = $provider->search($query);
				if ($size > 0) {
					$slicedResults = array_slice($providerResults, ($page - 1) * $size, $size);
					$results = array_merge($results, $slicedResults);
				} else {
					$results = array_merge($results, $providerResults);
				}
			} else {
				\OCP\Server::get(LoggerInterface::class)->warning('Ignoring Unknown search provider', ['provider' => $provider]);
			}
		}
		return $results;
	}

	/**
	 * Remove all registered search providers
	 */
	public function clearProviders() {
		$this->providers = [];
		$this->registeredProviders = [];
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
		$this->providers = [];
	}

	/**
	 * Register a new search provider to search with
	 * @param string $class class name of a OC\Search\Provider
	 * @param array $options optional
	 */
	public function registerProvider($class, array $options = []) {
		$this->registeredProviders[] = ['class' => $class, 'options' => $options];
	}

	/**
	 * Create instances of all the registered search providers
	 */
	private function initProviders() {
		if (! empty($this->providers)) {
			return;
		}
		foreach ($this->registeredProviders as $provider) {
			$class = $provider['class'];
			$options = $provider['options'];
			$this->providers[] = new $class($options);
		}
	}
}
