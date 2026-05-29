<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote;

use OCP\Remote\ICredentials;

class Credentials implements ICredentials {
	public function __construct(
		private string $user,
		private string $password,
	) {
	}

	#[\Override]
	public function getUsername(): string {
		return $this->user;
	}

	#[\Override]
	public function getPassword(): string {
		return $this->password;
	}
}
