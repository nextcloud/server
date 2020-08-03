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
use OCP\Search\ISearchQuery;

class UnifiedSearchController extends Controller {

	/** @var SearchComposer */
	private $composer;

	/** @var IUserSession */
	private $userSession;

	public function __construct(IRequest $request,
								IUserSession $userSession,
								SearchComposer $composer) {
		parent::__construct('core', $request);

		$this->composer = $composer;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getProviders(): JSONResponse {
		return new JSONResponse(
			$this->composer->getProviders()
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
	 *
	 * @return JSONResponse
	 */
	public function search(string $providerId,
						   string $term = '',
						   ?int $sortOrder = null,
						   ?int $limit = null,
						   $cursor = null): JSONResponse {
		if (empty(trim($term))) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(
			$this->composer->search(
				$this->userSession->getUser(),
				$providerId,
				new SearchQuery(
					$term,
					$sortOrder ?? ISearchQuery::SORT_DATE_DESC,
					$limit ?? SearchQuery::LIMIT_DEFAULT,
					$cursor
				)
			)
		);
	}
}
