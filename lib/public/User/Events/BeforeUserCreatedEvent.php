<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted before a new user is created on the back-end.
 *
 * @since 18.0.0
 */
class BeforeUserCreatedEvent extends Event {
	/** @var string */
	private $uid;

	/** @var string */
	private $password;

	/**
	 * @since 18.0.0
	 */
	public function __construct(string $uid,
		string $password) {
		parent::__construct();
		$this->uid = $uid;
		$this->password = $password;
	}

	/**
	 * @since 18.0.0
	 */
	public function getUid(): string {
		return $this->uid;
	}

	/**
	 * @since 18.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}
}
