<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Events;

use OCP\EventDispatcher\Event;

/**
 * Dispatched when someone asks for a password reset email, before the email
 * is sent and whether or not the input matches an account.
 *
 * A listener can prevent the email, for example while an account is flooded
 * with reset requests. The response to the requester stays the same, so it
 * does not reveal whether an email was sent.
 *
 * @since 36.0.0
 */
class PasswordResetRequestedEvent extends Event {
	private bool $emailPrevented = false;

	/**
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly string $input,
	) {
		parent::__construct();
	}

	/**
	 * The login name or email address that was entered
	 *
	 * @since 36.0.0
	 */
	public function getInput(): string {
		return $this->input;
	}

	/**
	 * Do not send the password reset email for this request
	 *
	 * @since 36.0.0
	 */
	public function preventEmail(): void {
		$this->emailPrevented = true;
	}

	/**
	 * @since 36.0.0
	 */
	public function isEmailPrevented(): bool {
		return $this->emailPrevented;
	}
}
