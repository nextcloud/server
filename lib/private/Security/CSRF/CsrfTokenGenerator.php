<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security\CSRF;

use OCP\Security\ISecureRandom;

/**
 * Class CsrfTokenGenerator is used to generate a cryptographically secure
 * pseudo-random number for the token.
 *
 * @package OC\Security\CSRF
 */
class CsrfTokenGenerator {
	public function __construct(
		private ISecureRandom $random,
	) {
	}

	/**
	 * Generate a new CSRF token.
	 *
	 * @param int $length Length of the token in characters.
	 */
	public function generateToken(int $length = 32): string {
		return $this->random->generate($length);
	}
}
