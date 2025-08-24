<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Session;

use OCP\ISession;

/**
 * @template-implements \ArrayAccess<string,mixed>
 */
abstract class Session implements \ArrayAccess, ISession {
	/**
	 * @var bool
	 */
	protected $sessionClosed = false;

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool {
		return $this->exists($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	/**
	 * Close the session and release the lock
	 */
	public function close() {
		$this->sessionClosed = true;
	}
}
