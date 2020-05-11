<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Search;

use OCP\AppFramework\QueryException;
use OCP\ILogger;
use OCP\IServerContainer;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use function array_map;

/**
 * Queries individual \OCP\Search\IProvider implementations and composes a
 * unified search result for the user's search term
 */
class SearchComposer {

	/** @var string[] */
	private $lazyProviders = [];

	/** @var IProvider[] */
	private $providers = [];

	/** @var IServerContainer */
	private $container;

	/** @var ILogger */
	private $logger;

	public function __construct(IServerContainer $container,
								ILogger $logger) {
		$this->container = $container;
		$this->logger = $logger;
	}

	public function registerProvider(string $class): void {
		$this->lazyProviders[] = $class;
	}

	/**
	 * Load all providers dynamically that were registered through `registerProvider`
	 *
	 * If a provider can't be loaded we log it but the operation continues nevertheless
	 */
	private function loadLazyProviders(): void {
		$classes = $this->lazyProviders;
		foreach ($classes as $class) {
			try {
				/** @var IProvider $provider */
				$provider = $this->container->query($class);
				$this->providers[$provider->getId()] = $provider;
			} catch (QueryException $e) {
				// Log an continue. We can be fault tolerant here.
				$this->logger->logException($e, [
					'message' => 'Could not load search provider dynamically: ' . $e->getMessage(),
					'level' => ILogger::ERROR,
				]);
			}
		}
		$this->lazyProviders = [];
	}

	public function getProviders(): array {
		$this->loadLazyProviders();

		/**
		 * Return an array with the IDs, but strip the associative keys
		 */
		return array_values(
			array_map(function (IProvider $provider) {
				return $provider->getId();
			}, $this->providers));
	}

	public function search(IUser $user,
						   string $providerId,
						   ISearchQuery $query): SearchResult {
		$this->loadLazyProviders();

		return $this->providers[$providerId]->search($user, $query);
	}
}
