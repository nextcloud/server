<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Search;

use InvalidArgumentException;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Core\ResponseDefinitions;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\FilterDefinition;
use OCP\Search\IFilter;
use OCP\Search\IFilteringProvider;
use OCP\Search\IInAppSearch;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;

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
 * @psalm-import-type CoreUnifiedSearchProvider from ResponseDefinitions
 */
class SearchComposer {
	/**
	 * @var array<string, array{appId: string, provider: IProvider}>
	 */
	private array $providers = [];

	private array $commonFilters;
	private array $customFilters = [];

	private array $handlers = [];

	public function __construct(
		private Coordinator $bootstrapCoordinator,
		private ContainerInterface $container,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
	) {
		$this->commonFilters = [
			IFilter::BUILTIN_TERM => new FilterDefinition(IFilter::BUILTIN_TERM, FilterDefinition::TYPE_STRING),
			IFilter::BUILTIN_SINCE => new FilterDefinition(IFilter::BUILTIN_SINCE, FilterDefinition::TYPE_DATETIME),
			IFilter::BUILTIN_UNTIL => new FilterDefinition(IFilter::BUILTIN_UNTIL, FilterDefinition::TYPE_DATETIME),
			IFilter::BUILTIN_TITLE_ONLY => new FilterDefinition(IFilter::BUILTIN_TITLE_ONLY, FilterDefinition::TYPE_BOOL, false),
			IFilter::BUILTIN_PERSON => new FilterDefinition(IFilter::BUILTIN_PERSON, FilterDefinition::TYPE_PERSON),
			IFilter::BUILTIN_PLACES => new FilterDefinition(IFilter::BUILTIN_PLACES, FilterDefinition::TYPE_STRINGS, false),
			IFilter::BUILTIN_PROVIDER => new FilterDefinition(IFilter::BUILTIN_PROVIDER, FilterDefinition::TYPE_STRING, false),
		];
	}

	/**
	 * Load all providers dynamically that were registered through `registerProvider`
	 *
	 * If $targetProviderId is provided, only this provider is loaded
	 * If a provider can't be loaded we log it but the operation continues nevertheless
	 */
	private function loadLazyProviders(?string $targetProviderId = null): void {
		$context = $this->bootstrapCoordinator->getRegistrationContext();
		if ($context === null) {
			// Too early, nothing registered yet
			return;
		}

		$registrations = $context->getSearchProviders();
		foreach ($registrations as $registration) {
			try {
				/** @var IProvider $provider */
				$provider = $this->container->get($registration->getService());
				$providerId = $provider->getId();
				if ($targetProviderId !== null && $targetProviderId !== $providerId) {
					continue;
				}
				$this->providers[$providerId] = [
					'appId' => $registration->getAppId(),
					'provider' => $provider,
				];
				$this->handlers[$providerId] = [$providerId];
				if ($targetProviderId !== null) {
					break;
				}
			} catch (ContainerExceptionInterface $e) {
				// Log an continue. We can be fault tolerant here.
				$this->logger->error('Could not load search provider dynamically: ' . $e->getMessage(), [
					'exception' => $e,
					'app' => $registration->getAppId(),
				]);
			}
		}

		$this->providers = $this->filterProviders($this->providers);

		$this->loadFilters();
	}

	private function loadFilters(): void {
		foreach ($this->providers as $providerId => $providerData) {
			$appId = $providerData['appId'];
			$provider = $providerData['provider'];
			if (!$provider instanceof IFilteringProvider) {
				continue;
			}

			foreach ($provider->getCustomFilters() as $filter) {
				$this->registerCustomFilter($filter, $providerId);
			}
			foreach ($provider->getAlternateIds() as $alternateId) {
				$this->handlers[$alternateId][] = $providerId;
			}
			foreach ($provider->getSupportedFilters() as $filterName) {
				if ($this->getFilterDefinition($filterName, $providerId) === null) {
					throw new InvalidArgumentException('Invalid filter ' . $filterName);
				}
			}
		}
	}

	private function registerCustomFilter(FilterDefinition $filter, string $providerId): void {
		$name = $filter->name();
		if (isset($this->commonFilters[$name])) {
			throw new InvalidArgumentException('Filter name is already used');
		}

		if (isset($this->customFilters[$providerId])) {
			$this->customFilters[$providerId][$name] = $filter;
		} else {
			$this->customFilters[$providerId] = [$name => $filter];
		}
	}

	/**
	 * Get a list of all provider IDs & Names for the consecutive calls to `search`
	 * Sort the list by the order property
	 *
	 * @param string $route the route the user is currently at
	 * @param array $routeParameters the parameters of the route the user is currently at
	 *
	 * @return list<CoreUnifiedSearchProvider>
	 */
	public function getProviders(string $route, array $routeParameters): array {
		$this->loadLazyProviders();

		$providers = array_map(
			function (array $providerData) use ($route, $routeParameters) {
				$appId = $providerData['appId'];
				$provider = $providerData['provider'];
				$order = $provider->getOrder($route, $routeParameters);
				if ($order === null) {
					return;
				}
				$triggers = [$provider->getId()];
				if ($provider instanceof IFilteringProvider) {
					$triggers += $provider->getAlternateIds();
					$filters = $provider->getSupportedFilters();
				} else {
					$filters = [IFilter::BUILTIN_TERM];
				}

				return [
					'id' => $provider->getId(),
					'appId' => $appId,
					'name' => $provider->getName(),
					'icon' => $this->fetchIcon($appId, $provider->getId()),
					'order' => $order,
					'triggers' => array_values($triggers),
					'filters' => $this->getFiltersType($filters, $provider->getId()),
					'inAppSearch' => $provider instanceof IInAppSearch,
				];
			},
			$this->providers,
		);
		$providers = array_filter($providers);

		// Sort providers by order and strip associative keys
		usort($providers, function ($provider1, $provider2) {
			return $provider1['order'] <=> $provider2['order'];
		});

		return $providers;
	}

	/**
	 * Filter providers based on 'unified_search.providers_allowed' core app config array
	 * @param array $providers
	 * @return array
	 */
	private function filterProviders(array $providers): array {
		$allowedProviders = $this->appConfig->getValueArray('core', 'unified_search.providers_allowed');

		if (empty($allowedProviders)) {
			return $providers;
		}

		return array_values(array_filter($providers, function ($p) use ($allowedProviders) {
			return in_array($p['id'], $allowedProviders);
		}));
	}

	private function fetchIcon(string $appId, string $providerId): string {
		$icons = [
			[$providerId, $providerId . '.svg'],
			[$providerId, 'app.svg'],
			[$appId, $providerId . '.svg'],
			[$appId, $appId . '.svg'],
			[$appId, 'app.svg'],
			['core', 'places/default-app-icon.svg'],
		];
		if ($appId === 'settings' && $providerId === 'users') {
			// Conflict:
			// the file /apps/settings/users.svg is already used in black version by top right user menu
			// Override icon name here
			$icons = [['settings', 'users-white.svg']];
		}
		foreach ($icons as $i => $icon) {
			try {
				return $this->urlGenerator->imagePath(... $icon);
			} catch (RuntimeException $e) {
				// Ignore error
			}
		}

		return '';
	}

	/**
	 * @param $filters string[]
	 * @return array<string, string>
	 */
	private function getFiltersType(array $filters, string $providerId): array {
		$filterList = [];
		foreach ($filters as $filter) {
			$filterList[$filter] = $this->getFilterDefinition($filter, $providerId)->type();
		}

		return $filterList;
	}

	private function getFilterDefinition(string $name, string $providerId): ?FilterDefinition {
		if (isset($this->commonFilters[$name])) {
			return $this->commonFilters[$name];
		}
		if (isset($this->customFilters[$providerId][$name])) {
			return $this->customFilters[$providerId][$name];
		}

		return null;
	}

	/**
	 * @param array<string, string> $parameters
	 */
	public function buildFilterList(string $providerId, array $parameters): FilterCollection {
		$this->loadLazyProviders($providerId);

		$list = [];
		foreach ($parameters as $name => $value) {
			$filter = $this->buildFilter($name, $value, $providerId);
			if ($filter === null) {
				continue;
			}
			$list[$name] = $filter;
		}

		return new FilterCollection(... $list);
	}

	private function buildFilter(string $name, string $value, string $providerId): ?IFilter {
		$filterDefinition = $this->getFilterDefinition($name, $providerId);
		if ($filterDefinition === null) {
			$this->logger->debug('Unable to find {name} definition', [
				'name' => $name,
				'value' => $value,
			]);

			return null;
		}

		if (!$this->filterSupportedByProvider($filterDefinition, $providerId)) {
			// FIXME Use dedicated exception and handle it
			throw new UnsupportedFilter($name, $providerId);
		}

		return FilterFactory::get($filterDefinition->type(), $value);
	}

	private function filterSupportedByProvider(FilterDefinition $filterDefinition, string $providerId): bool {
		// Non exclusive filters can be ommited by apps
		if (!$filterDefinition->exclusive()) {
			return true;
		}

		$provider = $this->providers[$providerId]['provider'];
		$supportedFilters = $provider instanceof IFilteringProvider
			? $provider->getSupportedFilters()
			: [IFilter::BUILTIN_TERM];

		return in_array($filterDefinition->name(), $supportedFilters, true);
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
	public function search(
		IUser $user,
		string $providerId,
		ISearchQuery $query,
	): SearchResult {
		$this->loadLazyProviders($providerId);

		$provider = $this->providers[$providerId]['provider'] ?? null;
		if ($provider === null) {
			throw new InvalidArgumentException("Provider $providerId is unknown");
		}

		return $provider->search($user, $query);
	}
}
