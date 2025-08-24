<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security\Mock;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\ExAppRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\StrictCookiesRequired;
use OCP\AppFramework\Http\Attribute\SubAdminRequired;

class SecurityMiddlewareController extends Controller {
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

	/**
	 * @ExAppRequired
	 */
	public function testAnnotationExAppRequired() {
	}

	#[ExAppRequired]
	public function testAttributeExAppRequired() {
	}
}
