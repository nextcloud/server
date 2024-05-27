<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Security\CSRF\CsrfTokenManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class CSRFTokenController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private CsrfTokenManager $tokenManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	#[FrontpageRoute(verb: 'GET', url: '/csrftoken')]
	public function index(): JSONResponse {
		if (!$this->request->passesStrictCookieCheck()) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$requestToken = $this->tokenManager->getToken();

		return new JSONResponse([
			'token' => $requestToken->getEncryptedValue(),
		]);
	}
}
