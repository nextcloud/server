<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISearch;
use OCP\Search\Result;
use Psr\Log\LoggerInterface;

class SearchController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISearch $searcher,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	#[FrontpageRoute(verb: 'GET', url: '/core/search')]
	public function search(string $query, array $inApps = [], int $page = 1, int $size = 30): JSONResponse {
		$results = $this->searcher->searchPaged($query, $inApps, $page, $size);

		$results = array_filter($results, function (Result $result) {
			if (json_encode($result, JSON_HEX_TAG) === false) {
				$this->logger->warning("Skipping search result due to invalid encoding: {type: " . $result->type . ", id: " . $result->id . "}");
				return false;
			} else {
				return true;
			}
		});

		return new JSONResponse($results);
	}
}
