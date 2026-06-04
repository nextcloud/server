<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Http\WellKnown\RequestManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class WellKnownController extends Controller {
	public function __construct(
		IRequest $request,
		private RequestManager $requestManager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @return Response
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '.well-known/{service}')]
	public function handle(string $service): Response {
		$response = $this->requestManager->process(
			$service,
			$this->request
		);

		if ($response === null) {
			$httpResponse = new JSONResponse(['message' => "$service not supported"], Http::STATUS_NOT_FOUND);
		} else {
			$httpResponse = $response->toHttpResponse();
		}

		// We add a custom header so that setup checks can detect if their requests are answered by this controller
		return $httpResponse->addHeader('X-NEXTCLOUD-WELL-KNOWN', '1');
	}
}
