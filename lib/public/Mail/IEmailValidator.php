<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Mail;

/**
 * Validator for email addresses
 *
 * @since 32.0.0
 */
interface IEmailValidator {
	/**
	 * @param string $email Email address to be validated
	 * @return bool True if the mail address is valid, false otherwise
	 * @since 32.0.0
	 */
	public function isValid(string $email): bool;
}
