<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security;

/**
 * Define the context in which a password is used.
 * This allows setting a context for password validation and password generation.
 *
 * @package OCP\Security
 * @since 31.0.0
 */
enum PasswordContext {
	/**
	 * Password used for an user account
	 * @since 31.0.0
	 */
	case ACCOUNT;

	/**
	 * Password used for (public) shares
	 * @since 31.0.0
	 */
	case SHARING;
}
