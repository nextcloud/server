<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security\Mock;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\PublicPage;

class CORSMiddlewareController extends Controller {
	/**
	 * @CORS
	 */
	public function testSetCORSAPIHeader() {
	}

	#[CORS]
	public function testSetCORSAPIHeaderAttribute() {
	}

	public function testNoAnnotationNoCORSHEADER() {
	}

	/**
	 * @CORS
	 */
	public function testNoOriginHeaderNoCORSHEADER() {
	}

	#[CORS]
	public function testNoOriginHeaderNoCORSHEADERAttribute() {
	}

	/**
	 * @CORS
	 */
	public function testCorsIgnoredIfWithCredentialsHeaderPresent() {
	}

	#[CORS]
	public function testCorsAttributeIgnoredIfWithCredentialsHeaderPresent() {
	}

	/**
	 * CORS must not be enforced for anonymous users on public pages
	 *
	 * @CORS
	 * @PublicPage
	 */
	public function testNoCORSOnAnonymousPublicPage() {
	}

	/**
	 * CORS must not be enforced for anonymous users on public pages
	 *
	 * @CORS
	 */
	#[PublicPage]
	public function testNoCORSOnAnonymousPublicPageAttribute() {
	}

	/**
	 * @PublicPage
	 */
	#[CORS]
	public function testNoCORSAttributeOnAnonymousPublicPage() {
	}

	#[CORS]
	#[PublicPage]
	public function testNoCORSAttributeOnAnonymousPublicPageAttribute() {
	}

	/**
	 * @CORS
	 * @PublicPage
	 */
	public function testCORSShouldNeverAllowCookieAuth() {
	}

	/**
	 * @CORS
	 */
	#[PublicPage]
	public function testCORSShouldNeverAllowCookieAuthAttribute() {
	}

	/**
	 * @PublicPage
	 */
	#[CORS]
	public function testCORSAttributeShouldNeverAllowCookieAuth() {
	}

	#[CORS]
	#[PublicPage]
	public function testCORSAttributeShouldNeverAllowCookieAuthAttribute() {
	}

	/**
	 * @CORS
	 */
	public function testCORSShouldRelogin() {
	}

	#[CORS]
	public function testCORSAttributeShouldRelogin() {
	}

	/**
	 * @CORS
	 */
	public function testCORSShouldFailIfPasswordLoginIsForbidden() {
	}

	#[CORS]
	public function testCORSAttributeShouldFailIfPasswordLoginIsForbidden() {
	}

	/**
	 * @CORS
	 */
	public function testCORSShouldNotAllowCookieAuth() {
	}

	#[CORS]
	public function testCORSAttributeShouldNotAllowCookieAuth() {
	}

	public function testAfterExceptionWithSecurityExceptionNoStatus() {
	}

	public function testAfterExceptionWithSecurityExceptionWithStatus() {
	}


	public function testAfterExceptionWithRegularException() {
	}
}
