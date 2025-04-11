<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Events;

use OCP\EventDispatcher\Event;
use OCP\Security\PasswordContext;

/**
 * This event can be emitted to request a validation of a password.
 *
 * If a password policy app is installed and the password
 * is invalid, an `\OCP\HintException` will be thrown.
 * @since 18.0.0
 */
class ValidatePasswordPolicyEvent extends Event {

	/**
	 * @since 18.0.0
	 * @since 31.0.0 - $context parameter added
	 */
	public function __construct(
		private string $password,
		private PasswordContext $context = PasswordContext::ACCOUNT,
	) {
		parent::__construct();
	}

	/**
	 * Get the password that should be validated.
	 * @since 18.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * Get the context this password should validated for.
	 * @since 31.0.0
	 */
	public function getContext(): PasswordContext {
		return $this->context;
	}
}
