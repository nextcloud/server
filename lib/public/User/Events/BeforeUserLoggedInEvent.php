<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\Authentication\IApacheBackend;
use OCP\EventDispatcher\Event;

/**
 * @since 18.0.0
 */
class BeforeUserLoggedInEvent extends Event {
	private string $username;
	private ?string $password;
	private ?IApacheBackend $backend;

	/**
	 * @since 18.0.0
	 * @since 26.0.0 password can be null
	 */
	public function __construct(string $username, ?string $password, ?IApacheBackend $backend = null) {
		parent::__construct();
		$this->username = $username;
		$this->password = $password;
		$this->backend = $backend;
	}

	/**
	 * returns the login name, which must not necessarily match to a user ID
	 *
	 * @since 18.0.0
	 */
	public function getUsername(): string {
		return $this->username;
	}

	/**
	 * @since 18.0.0
	 * @since 26.0.0 value can be null
	 */
	public function getPassword(): ?string {
		return $this->password;
	}

	/**
	 * return backend if available (or null)
	 *
	 * @return IApacheBackend|null
	 * @since 26.0.0
	 */
	public function getBackend(): ?IApacheBackend {
		return $this->backend;
	}
}
