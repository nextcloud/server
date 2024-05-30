<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Events;

use OCP\EventDispatcher\Event;

class LoginFailed extends Event {
	private string $loginName;
	private ?string $password;

	public function __construct(string $loginName, ?string $password) {
		parent::__construct();

		$this->loginName = $loginName;
		$this->password = $password;
	}

	public function getLoginName(): string {
		return $this->loginName;
	}

	public function getPassword(): ?string {
		return $this->password;
	}
}
