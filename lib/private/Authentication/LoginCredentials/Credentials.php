<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\LoginCredentials;

use OCP\Authentication\LoginCredentials\ICredentials;
use Override;

class Credentials implements ICredentials {
	public function __construct(
		private readonly string $uid,
		private readonly string $loginName,
		private readonly ?string $password,
	) {
	}

	#[Override]
	public function getUID(): string {
		return $this->uid;
	}

	#[Override]
	public function getLoginName(): string {
		return $this->loginName;
	}

	#[Override]
	public function getPassword(): ?string {
		return $this->password;
	}
}
