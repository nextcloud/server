<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\GlobalScale;

use OCP\GlobalScale\ILookupClient;

class DefaultLookupClient implements ILookupClient {

	public function info(): array {
		return [];
	}

	public function search(): array {
		return [];
	}

	public function registerUser(): bool {
		return true;
	}

	public function unregisterUser(): bool {
		return true;
	}

	public function updateUsers(): array {
		return [];
	}

	public function dropUsers(): void {
	}
}

