<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted before a user is logged in via remember-me cookies.
 *
 * @since 18.0.0
 */
class BeforeUserLoggedInWithCookieEvent extends Event {
	/** @var string */
	private $username;

	/**
	 * @since 18.0.0
	 */
	public function __construct(string $username) {
		parent::__construct();
		$this->username = $username;
	}

	/**
	 * @since 18.0.0
	 */
	public function getUsername(): string {
		return $this->username;
	}
}
