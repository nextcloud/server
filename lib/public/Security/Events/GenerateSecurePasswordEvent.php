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
 * Event to request a secure password to be generated
 * @since 18.0.0
 */
class GenerateSecurePasswordEvent extends Event {
	private ?string $password;

	/**
	 * Request a secure password to be generated.
	 *
	 * By default passwords are generated for the user account context,
	 * this can be adjusted by passing another `PasswordContext`.
	 * @since 31.0.0
	 */
	public function __construct(
		private PasswordContext $context = PasswordContext::ACCOUNT,
	) {
		parent::__construct();
		$this->password = null;
	}

	/**
	 * Get the generated password.
	 *
	 * If a password generator is registered and successfully generated a password
	 * that password can get read back. Otherwise `null` is returned.
	 * @since 18.0.0
	 */
	public function getPassword(): ?string {
		return $this->password;
	}

	/**
	 * Set the generated password.
	 *
	 * This is used by password generators to set the generated password.
	 * @since 18.0.0
	 */
	public function setPassword(string $password): void {
		$this->password = $password;
	}

	/**
	 * Get the context this password should generated for.
	 * @since 31.0.0
	 */
	public function getContext(): PasswordContext {
		return $this->context;
	}
}
