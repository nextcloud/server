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
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\Source\IShareSourceType;

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
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	/**
	 * Get multiple shares.
	 *
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @param ?positive-int $limit
	 * @return list<Share>
	 */
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array;
}
