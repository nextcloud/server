<?php

declare(strict_types=1);

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
