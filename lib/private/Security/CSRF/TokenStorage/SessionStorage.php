<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security\CSRF\TokenStorage;

use OCP\ISession;

/**
 * Class SessionStorage provides the session storage
 *
 * @package OC\Security\CSRF\TokenStorage
 */
class SessionStorage {
	public function __construct(
		private ISession $session,
	) {
	}

	public function setSession(ISession $session): void {
		$this->session = $session;
	}

	/**
	 * Returns the current token or throws an exception if none is found.
	 *
	 * @throws \Exception
	 */
	public function getToken(): string {
		$token = $this->session->get('requesttoken');
		if (empty($token)) {
			throw new \Exception('Session does not contain a requesttoken');
		}

		return $token;
	}

	/**
	 * Set the valid current token to $value.
	 */
	public function setToken(string $value): void {
		$this->session->set('requesttoken', $value);
	}

	/**
	 * Removes the current token.
	 */
	public function removeToken(): void {
		$this->session->remove('requesttoken');
	}

	/**
	 * Whether the storage has a storage.
	 */
	public function hasToken(): bool {
		return $this->session->exists('requesttoken');
	}
}
