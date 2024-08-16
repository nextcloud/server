<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Lock;

use OCP\PreConditionNotMetException;

/**
 * @since 24.0.0
 */
interface ILockProvider {
	/**
	 * @throws PreConditionNotMetException
	 * @throws NoLockProviderException
	 * @psalm-return list<ILock>
	 * @since 24.0.0
	 */
	public function getLocks(int $fileId): array;

	/**
	 * @throws PreConditionNotMetException
	 * @throws OwnerLockedException
	 * @throws NoLockProviderException
	 * @since 24.0.0
	 */
	public function lock(LockContext $lockInfo): ILock;

	/**
	 * @throws PreConditionNotMetException
	 * @throws NoLockProviderException
	 * @since 24.0.0
	 */
	public function unlock(LockContext $lockInfo): void;
}
