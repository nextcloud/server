<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NextcloudIntegration;

use RuntimeException;

/**
 * User fixtures created through the provisioning API.
 */
final class Users {
	public const DEFAULT_PASSWORD = '123456';

	/**
	 * @param ApiClient $admin Client authenticated as an administrator
	 */
	public function __construct(
		private readonly ApiClient $admin,
	) {
	}

	public function exists(string $userId): bool {
		return $this->admin->ocs('GET', '/cloud/users/' . rawurlencode($userId))->getStatusCode() === 200;
	}

	public function ensureExists(string $userId, string $password = self::DEFAULT_PASSWORD): void {
		if ($this->exists($userId)) {
			return;
		}

		$response = $this->admin->ocs('POST', '/cloud/users', [
			'form_params' => [
				'userid' => $userId,
				'password' => $password,
			],
		]);
		if ($response->getStatusCode() !== 200) {
			throw new RuntimeException(
				sprintf('Could not create user "%s": HTTP %d %s', $userId, $response->getStatusCode(), (string)$response->getBody())
			);
		}

		// Log in once so that the home storage is set up, matching what the
		// Behat step "user :user exists" does.
		$this->admin->asUser($userId, $password)->ocs('GET', '/cloud/users/' . rawurlencode($userId));
	}
}
