<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Sharing\Exception\ShareForbiddenException;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

/**
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
interface IManager {
	// TODO: Allow searching multiple recipient type classes at once
	/**
	 * @param ?class-string<IShareRecipientType> $recipientTypeClass
	 * @param non-empty-string $query
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @throws ShareInvalidException
	 */
	public function searchRecipients(ShareAccessContext $accessContext, ?string $recipientTypeClass, string $query, int $limit, int $offset): array;

	public function createShare(ShareAccessContext $accessContext): string;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 */
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 */
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 */
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 */
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 */
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 */
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 */
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 */
	public function deleteShare(ShareAccessContext $accessContext, string $id): void;

	/**
	 * @throws ShareNotFoundException
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	/**
	 * @param ?class-string<IShareSourceType> $sourceTypeClass
	 * @param ?positive-int $limit
	 * @return list<Share>
	 * @throws ShareInvalidException
	 */
	public function listShares(ShareAccessContext $accessContext, ?string $sourceTypeClass, ?string $lastShareID, ?int $limit): array;
}
