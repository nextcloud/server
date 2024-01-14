<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OC\Authentication\Token\RemoteWipe;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
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
