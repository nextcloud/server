<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Search;

use InvalidArgumentException;
use OC\AppFramework\Bootstrap\Coordinator;
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
 *
 * The search process is generally split into two steps
 *
 *   1. Get a list of provider (`getProviders`)
 *   2. Get search results of each provider (`search`)
 *
 * The reasoning behind this is that the runtime complexity of a combined search
 * result would be O(n) and linearly grow with each provider added. This comes
 * from the nature of php where we can't concurrently fetch the search results.
 * So we offload the concurrency the client application (e.g. JavaScript in the
 * browser) and let it first get the list of providers to then fetch all results
 * concurrently. The client is free to decide whether all concurrent search
 * results are awaited or shown as they come in.
 *
 * @see IProvider::search() for the arguments of the individual search requests
 */
class SearchComposer {

	/** @var IProvider[] */
	private $providers = [];

	/** @var Coordinator */
	private $bootstrapCoordinator;

	/** @var IServerContainer */
	private $container;

	/** @var ILogger */
	private $logger;

	public function __construct(Coordinator $bootstrapCoordinator,
								IServerContainer $container,
								ILogger $logger) {
		$this->container = $container;
		$this->logger = $logger;
		$this->bootstrapCoordinator = $bootstrapCoordinator;
	}

	/**
	 * Load all providers dynamically that were registered through `registerProvider`
	 *
	 * If a provider can't be loaded we log it but the operation continues nevertheless
	 */
	private function loadLazyProviders(): void {
		$context = $this->bootstrapCoordinator->getRegistrationContext();
		if ($context === null) {
			// Too early, nothing registered yet
			return;
		}

		$registrations = $context->getSearchProviders();
		foreach ($registrations as $registration) {
			try {
				/** @var IProvider $provider */
				$provider = $this->container->query($registration->getService());
				$this->providers[$provider->getId()] = $provider;
			} catch (QueryException $e) {
				// Log an continue. We can be fault tolerant here.
				$this->logger->logException($e, [
					'message' => 'Could not load search provider dynamically: ' . $e->getMessage(),
					'level' => ILogger::ERROR,
					'app' => $registration->getAppId(),
				]);
			}
		}
	}

	/**
	 * Get a list of all provider IDs & Names for the consecutive calls to `search`
	 * Sort the list by the order property
	 *
	 * @param string $route the route the user is currently at
	 * @param array $routeParameters the parameters of the route the user is currently at
	 *
	 * @return array
	 */
	public function getProviders(string $route, array $routeParameters): array {
		$this->loadLazyProviders();

		$providers = array_values(
			array_map(function (IProvider $provider) use ($route, $routeParameters) {
				return [
					'id' => $provider->getId(),
					'name' => $provider->getName(),
					'order' => $provider->getOrder($route, $routeParameters),
				];
			}, $this->providers)
		);

		usort($providers, function ($provider1, $provider2) {
			return $provider1['order'] <=> $provider2['order'];
		});

		/**
		 * Return an array with the IDs, but strip the associative keys
		 */
		return $providers;
	}

	/**
	 * Query an individual search provider for results
	 *
	 * @param IUser $user
	 * @param string $providerId one of the IDs received by `getProviders`
	 * @param ISearchQuery $query
	 *
	 * @return SearchResult
	 * @throws InvalidArgumentException when the $providerId does not correspond to a registered provider
	 */
	public function search(IUser $user,
		string $providerId,
						   ISearchQuery $query): SearchResult {
		$this->loadLazyProviders();

		$provider = $this->providers[$providerId] ?? null;
		if ($provider === null) {
			throw new InvalidArgumentException("Provider $providerId is unknown");
		}
		return $provider->search($user, $query);
	}


	/**
	 * Parsing the $term, removing those that are considered as options from the query string and
	 * filling a ISearchOptions with them
	 *
	 * Options are entries with comma (':')
	 * if search term is "keyword1 option1:value1 keyword2":
	 *   - the search term will be "keyword1 keyword2"
	 *   - the options are ['option1' => 'value1']
	 *
	 * Options Keys will be stored lowercased
	 *
	 * There is a better way to do this by using regex, but at least this one can be fully understood
	 *
	 * @param string $term
	 *
	 * @return SearchOptions
	 */
	public function extractSearchOptions(string &$term): SearchOptions {
		$entries = explode(' ', $term);
		$newTerms = $options = [];
		foreach ($entries as $entry) {
			if (trim($entry) === '') {
				continue;
			}

			$posColon = strpos($entry, ':');
			if (is_bool($posColon) || $posColon === 0) {
				$newTerms[] = $entry;
				continue;
			}

			[$option, $value] = explode(':', $entry, 2);
			$option = strtolower($option);

			$posComma = strpos($value, ',');
			if (!is_bool($posComma) && $posComma > 0) {
				$value = explode(',', $value);
			}

			if (array_key_exists($option, $options)) {
				if (!is_array($value)) {
					$value = [$value];
				}

				if (!is_array($options[$option])) {
					$options[$option] = [$options[$option]];
				}

				$value = array_merge($options[$option], $value);
			}

			$options[$option] = $value;
		}

		$term = implode(' ', $newTerms);

		return new SearchOptions($options);
	}
}
