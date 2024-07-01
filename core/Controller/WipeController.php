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
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\IRequest;

class WipeController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private RemoteWipe $remoteWipe,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @AnonRateThrottle(limit=10, period=300)
	 *
	 * Check if the device should be wiped
	 *
	 * @param string $token App password
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{wipe: bool}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Device should be wiped
	 * 404: Device should not be wiped
	 */
	#[FrontpageRoute(verb: 'POST', url: '/core/wipe/check')]
	public function checkWipe(?string $token = ''): JSONResponse {
		if (!empty($token)) {
			try {
				if ($this->remoteWipe->start($token)) {
					return new JSONResponse([
						'wipe' => true
					]);
				}
			} catch (InvalidTokenException $e) {
				// do nothing special, handled below
			}
		}

		return new JSONResponse([], Http::STATUS_NOT_FOUND);
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @AnonRateThrottle(limit=10, period=300)
	 *
	 * Finish the wipe
	 *
	 * @param string $token App password
	 *
	 * @return JSONResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Wipe finished successfully
	 * 404: Device should not be wiped
	 */
	#[FrontpageRoute(verb: 'POST', url: '/core/wipe/success')]
	public function wipeDone(?string $token = ''): JSONResponse {
		if (!empty($token)) {
			try {
				if ($this->remoteWipe->finish($token)) {
					return new JSONResponse([]);
				}
			} catch (InvalidTokenException $e) {
				// do nothing special, handled below
			}
		}
		
		return new JSONResponse([], Http::STATUS_NOT_FOUND);
	}
}
