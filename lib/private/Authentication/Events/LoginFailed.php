<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Events;

use OCP\EventDispatcher\Event;

class LoginFailed extends Event {
	public function __construct(
		private string $loginName,
		private ?string $password,
	) {
		parent::__construct();
	}

	public function getLoginName(): string {
		return $this->loginName;
	}

	public function getPassword(): ?string {
		return $this->password;
	}
}
