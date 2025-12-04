<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Charles Taborin <charles.taborin@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Events;

use OCP\EventDispatcher\Event;

class AfterAuthTokenCreatedEvent extends Event {
	public function __construct(
		private string $token,
	) {
	}

	public function getToken(): string {
		return $this->token;
	}

	public function setToken(string $token): void {
		$this->token = $token;
	}
}
