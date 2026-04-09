<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Lock;

use OCP\Lock\LockedException;

/**
 * @since 24.0.0
 */
class OwnerLockedException extends LockedException {
	private ILock $lock;

	/**
	 * @since 24.0.0
	 */
	public function __construct(ILock $lock) {
		$this->lock = $lock;
		$path = '';
		$readablePath = '';
		parent::__construct($path, null, $lock->getOwner(), $readablePath);
	}

	/**
	 * @since 24.0.0
	 */
	public function getLock(): ILock {
		return $this->lock;
	}
}
