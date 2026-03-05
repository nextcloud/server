<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Authentication\Token\RemoteWipe;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
class WipeController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private RemoteWipe $remoteWipe,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Check if the device should be wiped
	 *
	 * @param string $token App password
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{wipe: bool}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Device should be wiped
	 * 404: Device should not be wiped
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 10, period: 300)]
	#[FrontpageRoute(verb: 'POST', url: '/core/wipe/check')]
	public function checkWipe(string $token): JSONResponse {
		try {
			if ($this->remoteWipe->start($token)) {
				return new JSONResponse([
					'wipe' => true
				]);
			}

			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		} catch (InvalidTokenException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * Finish the wipe
	 *
	 * @param string $token App password
	 *
	 * @return JSONResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Wipe finished successfully
	 * 404: Device should not be wiped
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 10, period: 300)]
	#[FrontpageRoute(verb: 'POST', url: '/core/wipe/success')]
	public function wipeDone(string $token): JSONResponse {
		try {
			if ($this->remoteWipe->finish($token)) {
				return new JSONResponse([]);
			}

			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		} catch (InvalidTokenException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
	}
}
