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

namespace OC\Core\Controller;

use OC\Search\SearchComposer;
use OC\Search\SearchQuery;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Route\IRouter;
use OCP\Search\ISearchQuery;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class UnifiedSearchController extends Controller {

	/** @var SearchComposer */
	private $composer;

	/** @var IUserSession */
	private $userSession;

	/** @var IRouter */
	private $router;

	public function __construct(IRequest $request,
								IUserSession $userSession,
								SearchComposer $composer,
								IRouter $router) {
		parent::__construct('core', $request);

		$this->composer = $composer;
		$this->userSession = $userSession;
		$this->router = $router;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $from the url the user is currently at
	 *
	 * @return JSONResponse
	 */
	public function getProviders(string $from = ''): JSONResponse {
		[$route, $parameters] = $this->getRouteInformation($from);

		return new JSONResponse(
			$this->composer->getProviders($route, $parameters)
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $providerId
	 * @param string $term
	 * @param int|null $sortOrder
	 * @param int|null $limit
	 * @param int|string|null $cursor
	 * @param string $from
	 *
	 * @return JSONResponse
	 */
	public function search(string $providerId,
						   string $term = '',
						   ?int $sortOrder = null,
						   ?int $limit = null,
						   $cursor = null,
						   string $from = ''): JSONResponse {
		if (empty(trim($term))) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}
		[$route, $routeParameters] = $this->getRouteInformation($from);

		return new JSONResponse(
			$this->composer->search(
				$this->userSession->getUser(),
				$providerId,
				new SearchQuery(
					$term,
					$sortOrder ?? ISearchQuery::SORT_DATE_DESC,
					$limit ?? SearchQuery::LIMIT_DEFAULT,
					$cursor,
					$route,
					$routeParameters
				)
			)
		);
	}

	protected function getRouteInformation(string $url): array {
		$routeStr = '';
		$parameters = [];

		if ($url !== '') {
			$urlParts = parse_url($url);

			try {
				$parameters = $this->router->findMatchingRoute($urlParts['path']);

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
