<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use InvalidArgumentException;
use OC\Core\AppInfo\Application;
use OC\Core\AppInfo\ConfigLexicon;
use OC\Core\ResponseDefinitions;
use OC\Search\SearchComposer;
use OC\Search\SearchQuery;
use OC\Search\UnsupportedFilter;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Route\IRouter;
use OCP\Search\ISearchQuery;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @psalm-import-type CoreUnifiedSearchProvider from ResponseDefinitions
 * @psalm-import-type CoreUnifiedSearchResult from ResponseDefinitions
 */
class UnifiedSearchController extends OCSController {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private SearchComposer $composer,
		private IRouter $router,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private IAppConfig $appConfig,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * Get the providers for unified search
	 *
	 * @param string $from the url the user is currently at
	 * @return DataResponse<Http::STATUS_OK, list<CoreUnifiedSearchProvider>, array{}>
	 *
	 * 200: Providers returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/providers', root: '/search')]
	public function getProviders(string $from = ''): DataResponse {
		[$route, $parameters] = $this->getRouteInformation($from);

		$result = $this->composer->getProviders($route, $parameters);
		$response = new DataResponse($result);
		$response->setETag(md5(json_encode($result)));
		return $response;
	}

	/**
	 * Launch a search for a specific search provider.
	 *
	 * Additional filters are available for each provider.
	 * Send a request to /providers endpoint to list providers with their available filters.
	 *
	 * @param string $providerId ID of the provider
	 * @param string $term Term to search
	 * @param int|null $sortOrder Order of entries
	 * @param int|null $limit Maximum amount of entries (capped by configurable unified-search.max-results-per-request, default: 25)
	 * @param int|string|null $cursor Offset for searching
	 * @param string $from The current user URL
	 *
	 * @return DataResponse<Http::STATUS_OK, CoreUnifiedSearchResult, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 *
	 * 200: Search entries returned
	 * 400: Searching is not possible
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/providers/{providerId}/search', root: '/search')]
	public function search(
		string $providerId,
		// Unused parameter for OpenAPI spec generator
		string $term = '',
		?int $sortOrder = null,
		?int $limit = null,
		$cursor = null,
		string $from = '',
	): DataResponse {
		[$route, $routeParameters] = $this->getRouteInformation($from);

		$limit ??= SearchQuery::LIMIT_DEFAULT;
		$maxLimit = $this->appConfig->getValueInt(Application::APP_ID, ConfigLexicon::UNIFIED_SEARCH_MAX_RESULTS_PER_REQUEST);
		$limit = max(1, min($limit, $maxLimit));

		try {
			$filters = $this->composer->buildFilterList($providerId, $this->request->getParams());
		} catch (UnsupportedFilter|InvalidArgumentException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		if ($filters->count() === 0) {
			return new DataResponse($this->l10n->t('No valid filters provided'), Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse(
			$this->composer->search(
				$this->userSession->getUser(),
				$providerId,
				new SearchQuery(
					$filters,
					$sortOrder ?? ISearchQuery::SORT_DATE_DESC,
					$limit,
					$cursor,
					$route,
					$routeParameters
				)
			)->jsonSerialize()
		);
	}

	protected function getRouteInformation(string $url): array {
		$routeStr = '';
		$parameters = [];

		if ($url !== '') {
			$urlParts = parse_url($url);
			$urlPath = $urlParts['path'];

			// Optionally strip webroot from URL. Required for route matching on setups
			// with Nextcloud in a webserver subfolder (webroot).
			$webroot = $this->urlGenerator->getWebroot();
			if ($webroot !== '' && substr($urlPath, 0, strlen($webroot)) === $webroot) {
				$urlPath = substr($urlPath, strlen($webroot));
			}

			try {
				$parameters = $this->router->findMatchingRoute($urlPath);

				// contacts.PageController.index => contacts.Page.index
				$route = $parameters['caller'];
				if (substr($route[1], -10) === 'Controller') {
					$route[1] = substr($route[1], 0, -10);
				}
				$routeStr = implode('.', $route);

				// cleanup
				unset($parameters['_route'], $parameters['action'], $parameters['caller']);
			} catch (ResourceNotFoundException $exception) {
			}

			if (isset($urlParts['query'])) {
				parse_str($urlParts['query'], $queryParameters);
				$parameters = array_merge($parameters, $queryParameters);
			}
		}

		return [
			$routeStr,
			$parameters,
		];
	}
}
