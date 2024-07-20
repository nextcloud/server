<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Lock;

use OCP\Lock\ILockingProvider;

/**
 * Locking provider that does nothing.
 *
 * To be used when locking is disabled.
 */
class NoopLockingProvider implements ILockingProvider {
	/**
	 * {@inheritdoc}
	 */
	public function isLocked(string $path, int $type): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function acquireLock(string $path, int $type, ?string $readablePath = null): void {
		// do nothing
	}

	/**
	 * {@inheritdoc}
	 */
	public function releaseLock(string $path, int $type): void {
		// do nothing
	}

	/**1
	 * {@inheritdoc}
	 */
	public function releaseAll(): void {
		// do nothing
	}

	/**
	 * {@inheritdoc}
	 */
	public function changeLock(string $path, int $targetType): void {
		// do nothing
	}
}
