<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\LoginCredentials;

use OCP\Authentication\LoginCredentials\ICredentials;

class Credentials implements ICredentials {

	public function __construct(
		private string $uid,
		private string $loginName,
		private string $password,
	) {
	}

	public function getUID(): string {
		return $this->uid;
	}

	public function getLoginName(): string {
		return $this->loginName;
	}

	public function getPassword(): string {
		return $this->password;
	}
}
