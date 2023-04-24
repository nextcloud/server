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

use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\StrictCookiesRequired;
use OCP\AppFramework\Http\Attribute\SubAdminRequired;

class SecurityMiddlewareController extends \OCP\AppFramework\Controller {
	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testAnnotationNoCSRFRequiredPublicPage() {
	}

	/**
	 * @NoCSRFRequired
	 */
	#[PublicPage]
	public function testAnnotationNoCSRFRequiredAttributePublicPage() {
	}

	/**
	 * @PublicPage
	 */
	#[NoCSRFRequired]
	public function testAnnotationPublicPageAttributeNoCSRFRequired() {
	}

	#[NoCSRFRequired]
	#[PublicPage]
	public function testAttributeNoCSRFRequiredPublicPage() {
	}

	public function testNoAnnotationNorAttribute() {
	}

	/**
	 * @NoCSRFRequired
	 */
	public function testAnnotationNoCSRFRequired() {
	}

	#[NoCSRFRequired]
	public function testAttributeNoCSRFRequired() {
	}

	/**
	 * @PublicPage
	 */
	public function testAnnotationPublicPage() {
	}

	#[PublicPage]
	public function testAttributePublicPage() {
	}

	/**
	 * @PublicPage
	 * @StrictCookieRequired
	 */
	public function testAnnotationPublicPageStrictCookieRequired() {
	}

	/**
	 * @StrictCookieRequired
	 */
	#[PublicPage]
	public function testAnnotationStrictCookieRequiredAttributePublicPage() {
	}

	/**
	 * @PublicPage
	 */
	#[StrictCookiesRequired]
	public function testAnnotationPublicPageAttributeStrictCookiesRequired() {
	}

	#[PublicPage]
	#[StrictCookiesRequired]
	public function testAttributePublicPageStrictCookiesRequired() {
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @StrictCookieRequired
	 */
	public function testAnnotationNoCSRFRequiredPublicPageStrictCookieRequired() {
	}

	#[NoCSRFRequired]
	#[PublicPage]
	#[StrictCookiesRequired]
	public function testAttributeNoCSRFRequiredPublicPageStrictCookiesRequired() {
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function testAnnotationNoAdminRequiredNoCSRFRequired() {
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function testAttributeNoAdminRequiredNoCSRFRequired() {
	}

	/**
	 * @NoCSRFRequired
	 * @SubAdminRequired
	 */
	public function testAnnotationNoCSRFRequiredSubAdminRequired() {
	}

	/**
	 * @SubAdminRequired
	 */
	#[NoCSRFRequired]
	public function testAnnotationNoCSRFRequiredAttributeSubAdminRequired() {
	}

	/**
	 * @NoCSRFRequired
	 */
	#[SubAdminRequired]
	public function testAnnotationSubAdminRequiredAttributeNoCSRFRequired() {
	}

	#[NoCSRFRequired]
	#[SubAdminRequired]
	public function testAttributeNoCSRFRequiredSubAdminRequired() {
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function testAnnotationNoAdminRequiredNoCSRFRequiredPublicPage() {
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[PublicPage]
	public function testAttributeNoAdminRequiredNoCSRFRequiredPublicPage() {
	}
}
