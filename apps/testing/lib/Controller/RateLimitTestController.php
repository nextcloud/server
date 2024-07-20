<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Testing\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class RateLimitTestController extends Controller {
	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @UserRateThrottle(limit=5, period=100)
	 * @AnonRateThrottle(limit=1, period=100)
	 *
	 * @return JSONResponse
	 */
	public function userAndAnonProtected() {
		return new JSONResponse();
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @AnonRateThrottle(limit=1, period=10)
	 *
	 * @return JSONResponse
	 */
	public function onlyAnonProtected() {
		return new JSONResponse();
	}
}
