<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Share;
use NCU\Sharing\Source\IShareSourceType;
use OCP\IUser;

/**
 * This interface is only temporary and implemented in the files_sharing app.
 */
interface ISharingLegacyBackend {
	/**
	 * @return list<class-string<IShareSourceType>>
	 */
	public function getCompatibleSourceTypes(): array;

	/**
	 * @return list<class-string<IShareRecipientType>>
	 */
	public function getCompatibleRecipientTypes(): array;

	/**
	 * Update a share.
	 */
	public function updateShare(Share $share): void;

	/**
	 * Delete a share.
	 *
	 * @throws ShareNotFoundException
	 */
	public function deleteShare(string $id): void;

	/**
	 * Get a share.
	 *
	 * @throws ShareNotFoundException
	 */
	public function getShare(string $id): Share;

	/**
	 * Get a share by it's legacy provider and id.
	 *
	 * @throws ShareNotFoundException
	 */
	public function getShareByLegacyProviderAndId(string $legacyProvider, string $legacyId): Share;

	/**
	 * Get unmapped shares.
	 *
	 * @return list<Share>
	 */
	public function getUnmappedShares(IUser $user): array;
}
