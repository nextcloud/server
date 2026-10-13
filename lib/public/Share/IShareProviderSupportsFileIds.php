<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share;

/**
 * A share provider that can look up the shares of many files at once
 *
 * @since 36.0.0
 */
interface IShareProviderSupportsFileIds extends IShareProvider {
	/**
	 * Get the shares the user created on these files, like `getSharesBy()` without reshares for each file
	 *
	 * @param IShare::TYPE_* $shareType
	 * @param non-empty-list<int> $fileIds at most 1000
	 * @return list<IShare>
	 * @since 36.0.0
	 */
	public function getSharesByFileIds(string $userId, int $shareType, array $fileIds): array;

	/**
	 * Get the shares of these files the user received, like `getSharedWith()` for each file
	 *
	 * @param IShare::TYPE_* $shareType
	 * @param non-empty-list<int> $fileIds at most 1000
	 * @return list<IShare>
	 * @since 36.0.0
	 */
	public function getSharedWithByFileIds(string $userId, int $shareType, array $fileIds): array;
}
