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
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class CSRFTokenController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private CsrfTokenManager $tokenManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Returns a new CSRF token.
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{token: string}, array{}>|JSONResponse<Http::STATUS_FORBIDDEN, list<empty>, array{}>
	 *
	 * 200: CSRF token returned
	 * 403: Strict cookie check failed
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/csrftoken')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
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
