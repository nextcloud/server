<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Test\AppFramework\Middleware\Security\Mock;

use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\PublicPage;

class CORSMiddlewareController extends \OCP\AppFramework\Controller {
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
