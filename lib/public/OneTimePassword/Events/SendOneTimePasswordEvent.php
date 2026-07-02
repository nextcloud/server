<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword\Events;

use OCP\EventDispatcher\Event;

class SendOneTimePasswordEvent extends Event {
	private bool $wasConsumed = false;
	private ?string $error = null;
	private ?string $message = null;

	public function __construct(
		private readonly string $password,
		private readonly string $provider,
		private readonly string $recipient,
	) {
		parent::__construct();
	}

	public function getPassword(): string {
		return $this->password;
	}

	public function getRecipient(): string {
		return $this->recipient;
	}

	public function getProvider(): string {
		return $this->provider;
	}

	public function markConsumed(): void {
		$this->wasConsumed = true;
	}

	public function getWasConsumed(): bool {
		return $this->wasConsumed;
	}

	public function setError(string $error): void {
		$this->error = $error;
	}

	public function getError(): ?string {
		return $this->error;
	}

	public function setMessage(string $message): void {
		$this->message = $message;
	}

	public function getMessage(): ?string {
		return $this->message;
	}
}
