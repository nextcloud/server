<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted when the authentication fails
 *
 * @since 26.0.0
 */
class AnyLoginFailedEvent extends Event {
	private string $loginName;
	private ?string $password;

	/**
	 * @since 26.0.0
	 */
	public function __construct(string $loginName, ?string $password) {
		parent::__construct();

		$this->loginName = $loginName;
		$this->password = $password;
	}

	/**
	 * @since 26.0.0
	 */
	public function geLoginName(): string {
		return $this->loginName;
	}

	/**
	 * @since 26.0.0
	 */
	public function getPassword(): ?string {
		return $this->password;
	}
}
