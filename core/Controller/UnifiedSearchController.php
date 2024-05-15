<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OC\Core\Controller;

use InvalidArgumentException;
use OC\Search\SearchComposer;
use OC\Search\SearchQuery;
use OC\Search\UnsupportedFilter;
use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
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
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the providers for unified search
	 *
	 * @param string $from the url the user is currently at
	 * @return DataResponse<Http::STATUS_OK, CoreUnifiedSearchProvider[], array{}>
	 *
	 * 200: Providers returned
	 */
	public function getProviders(string $from = ''): DataResponse {
		[$route, $parameters] = $this->getRouteInformation($from);

		$result = $this->composer->getProviders($route, $parameters);
		$response = new DataResponse($result);
		$response->setETag(md5(json_encode($result)));
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Launch a search for a specific search provider.
	 *
	 * Additional filters are available for each provider.
	 * Send a request to /providers endpoint to list providers with their available filters.
	 *
	 * @param string $providerId ID of the provider
	 * @param string $term Term to search
	 * @param int|null $sortOrder Order of entries
	 * @param int|null $limit Maximum amount of entries, limited to 25
	 * @param int|string|null $cursor Offset for searching
	 * @param string $from The current user URL
	 *
	 * @return DataResponse<Http::STATUS_OK, CoreUnifiedSearchResult, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 *
	 * 200: Search entries returned
	 * 400: Searching is not possible
	 */
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
		$limit = max(1, min($limit, 25));

		try {
			$filters = $this->composer->buildFilterList($providerId, $this->request->getParams());
		} catch (UnsupportedFilter|InvalidArgumentException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
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
