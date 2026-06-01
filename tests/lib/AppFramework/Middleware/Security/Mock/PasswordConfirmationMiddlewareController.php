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

	// Non-strict — for backend capability and null-user tests
	#[PasswordConfirmationRequired]
	public function testLegacyBackendExempt() {}

	#[PasswordConfirmationRequired]
	public function testIPasswordConfirmationBackendExempt() {}

	#[PasswordConfirmationRequired]
	public function testIPasswordConfirmationBackendNotExempt() {}

	#[PasswordConfirmationRequired]
	public function testNullUser() {}

	// Strict — one controller method per strict-mode test
	#[PasswordConfirmationRequired(strict: true)]
	public function testStrictModeValidCredentials() {}

	#[PasswordConfirmationRequired(strict: true)]
	public function testStrictModeMissingAuthHeader() {}

	#[PasswordConfirmationRequired(strict: true)]
	public function testStrictModeMalformedBase64() {}

	#[PasswordConfirmationRequired(strict: true)]
	public function testStrictModeWrongPassword() {}

	#[PasswordConfirmationRequired(strict: true)]
	public function testStrictModeLegacyBackendExempt() {}

	#[PasswordConfirmationRequired]
	public function testSSO() {
	}
}
