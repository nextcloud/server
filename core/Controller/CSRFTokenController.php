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
use OCP\AppFramework\Http\Attribute\NoTwoFactorRequired;
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
	#[NoTwoFactorRequired]
	public function index(): JSONResponse {
		if (!$this->request->passesStrictCookieCheck()) {
			// [login-diag] A 403 here means cy.login receives no token, so its
			// POST /login then fails the CSRF check -> validate 401. Record it.
			\OCP\Server::get(\Psr\Log\LoggerInterface::class)->error(
				'[login-diag] CSRFTOKEN 403 strictCookieCheck failed remote=' . $this->request->getRemoteAddress()
				. ' cookies=' . implode(',', array_keys($_COOKIE ?? [])),
				['app' => 'login-diag'],
			);
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$requestToken = $this->tokenManager->getToken();

		// [login-diag] Record the session this token was issued into, so a later
		// csrfCheckFailed on /login can be matched against it: if the /login
		// session/sessCookie differs from what /csrftoken issued into, the token
		// legitimately isn't in /login's session (a session-mismatch race).
		try {
			$session = \OCP\Server::get(\OCP\ISession::class);
			$raw = $this->request->getCookie(session_name());
			\OCP\Server::get(\Psr\Log\LoggerInterface::class)->error(
				'[login-diag] CSRFTOKEN issued remote=' . $this->request->getRemoteAddress()
				. ' session=' . substr(md5((string)$session->getId()), 0, 8)
				. ' sessCookie=' . ($raw !== null ? substr(md5($raw), 0, 8) : 'none'),
				['app' => 'login-diag'],
			);
		} catch (\Throwable) {
			// diagnostics must never affect the token endpoint
		}

		return new JSONResponse([
			'token' => $requestToken->getEncryptedValue(),
		]);
	}
}
