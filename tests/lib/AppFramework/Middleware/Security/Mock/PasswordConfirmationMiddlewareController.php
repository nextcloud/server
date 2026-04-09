<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security\Mock;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;

class PasswordConfirmationMiddlewareController extends Controller {
	public function testNoAnnotationNorAttribute() {
	}

	/**
	 * @TestAnnotation
	 */
	public function testDifferentAnnotation() {
	}

	/**
	 * @PasswordConfirmationRequired
	 */
	public function testAnnotation() {
	}

	#[PasswordConfirmationRequired]
	public function testAttribute() {
	}

	#[PasswordConfirmationRequired]
	public function testSSO() {
	}
}
