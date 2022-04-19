<?php

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 25.0.0
 */
class UserIdUnassignedEvent extends Event {
	private string $userId;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $userId) {
		parent::__construct();
		$this->userId = $userId;
	}

	/**
	 * @since 25.0.0
	 */
	public function getUserId(): string {
		return $this->userId;
	}
}
