<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 35.0.0
 */
class SendOneTimePasswordEvent extends Event {
	private bool $wasConsumed = false;
	private ?string $error = null;
	private ?string $message = null;

	/**
	 * Create a new SendOneTimePasswordEvent
	 *
	 * @param string $password the plaintext password
	 * @param string $provider the provider ID
	 * @param string $recipient the recipient identifier
	 * @since 35.0.0
	 */
	public function __construct(
		private readonly string $password,
		private readonly string $provider,
		private readonly string $recipient,
	) {
		parent::__construct();
	}

	/**
	 * Get the OTP password
	 *
	 * @return string the password
	 * @since 35.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * Get the OTP recipient
	 *
	 * @return string the recipient identifier
	 * @since 35.0.0
	 */
	public function getRecipient(): string {
		return $this->recipient;
	}

	/**
	 * Get the OTP provider id
	 *
	 * @return string the provider id
	 * @since 35.0.0
	 */
	public function getProvider(): string {
		return $this->provider;
	}

	/**
	 * Mark the event as consumed
	 *
	 * @return void
	 * @since 35.0.0
	 */
	public function markConsumed(): void {
		$this->wasConsumed = true;
	}

	/**
	 * Get whether the event has already been consumed
	 *
	 * @return bool whether or not the event has been consumed
	 * @since 35.0.0
	 */
	public function getWasConsumed(): bool {
		return $this->wasConsumed;
	}

	/**
	 * set a processing error
	 *
	 * @param string $error the error message
	 * @return void
	 * @since 35.0.0
	 */
	public function setError(string $error): void {
		$this->error = $error;
	}

	/**
	 * Get the event processing error
	 *
	 * @return string|null the error message or null
	 * @since 35.0.0
	 */
	public function getError(): ?string {
		return $this->error;
	}

	/**
	 * Set a processing message
	 *
	 * @param string $message the message
	 * @return void
	 * @since 35.0.0
	 */
	public function setMessage(string $message): void {
		$this->message = $message;
	}

	/**
	 * Get the processing message if set
	 *
	 * @return string|null the message or null
	 * @since 35.0.0
	 */
	public function getMessage(): ?string {
		return $this->message;
	}
}
