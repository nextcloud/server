<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Lock;

/**
 * Class ManuallyLockedException
 *
 * @since 18.0.0
 */
class ManuallyLockedException extends LockedException {
	/**
	 * owner of the lock
	 *
	 * @var string|null
	 */
	private $owner = null;

	/**
	 * estimated timeout for the lock
	 *
	 * @var int
	 * @since 18.0.0
	 */
	private $timeout = -1;


	/**
	 * ManuallyLockedException constructor.
	 *
	 * @param string $path locked path
	 * @param \Exception|null $previous previous exception for cascading
	 * @param string $existingLock
	 * @param string|null $owner
	 * @param int $timeout
	 *
	 * @since 18.0.0
	 */
	public function __construct(string $path, ?\Exception $previous = null, ?string $existingLock = null, ?string $owner = null, int $timeout = -1) {
		parent::__construct($path, $previous, $existingLock);
		$this->owner = $owner;
		$this->timeout = $timeout;
	}


	/**
	 * @return int
	 * @since 18.0.0
	 */
	public function getTimeout(): int {
		return $this->timeout;
	}

	/**
	 * @return string|null
	 * @since 18.0.0
	 */
	public function getOwner(): ?string {
		return $this->owner;
	}
}
