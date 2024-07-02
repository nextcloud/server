<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Session;

use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Class Internal
 *
 * store session data in an in-memory array, not persistent
 *
 * @package OC\Session
 */
class Memory extends Session {
	protected $data;

	/**
	 * @param string $key
	 * @param integer $value
	 */
	public function set(string $key, $value) {
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key) {
		if (!$this->exists($key)) {
			return null;
		}
		return $this->data[$key];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists(string $key): bool {
		return isset($this->data[$key]);
	}

	/**
	 * @param string $key
	 */
	public function remove(string $key) {
		unset($this->data[$key]);
	}

	public function clear() {
		$this->data = [];
	}

	/**
	 * Stub since the session ID does not need to get regenerated for the cache
	 *
	 * @param bool $deleteOldSession
	 */
	public function regenerateId(bool $deleteOldSession = true, bool $updateToken = false) {
	}

	/**
	 * Wrapper around session_id
	 *
	 * @return string
	 * @throws SessionNotAvailableException
	 * @since 9.1.0
	 */
	public function getId(): string {
		throw new SessionNotAvailableException('Memory session does not have an ID');
	}

	/**
	 * Helper function for PHPUnit execution - don't use in non-test code
	 */
	public function reopen(): bool {
		$reopened = $this->sessionClosed;
		$this->sessionClosed = false;
		return $reopened;
	}
}
