<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Testing\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\JSONResponse;

class RateLimitTestController extends Controller {
	/**
	 * @return JSONResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[UserRateLimit(limit: 5, period: 100)]
	#[AnonRateLimit(limit: 1, period: 100)]
	public function userAndAnonProtected() {
		return new JSONResponse();
	}

	/**
	 * @return JSONResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 1, period: 10)]
	public function onlyAnonProtected() {
		return new JSONResponse();
	}
}
