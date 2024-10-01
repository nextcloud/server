<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted when the authentication fails, but only if the login name can be associated with an existing user.
 *
 * @since 19.0.0
 */
class LoginFailedEvent extends Event {
	/** @var string */
	private $uid;

	/**
	 * @since 19.0.0
	 */
	public function __construct(string $uid) {
		parent::__construct();

		$this->uid = $uid;
	}

	/**
	 * returns the uid of the user that was tried to login against
	 *
	 * @since 19.0.0
	 */
	public function getUid(): string {
		return $this->uid;
	}
}
