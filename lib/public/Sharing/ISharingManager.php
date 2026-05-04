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
use OCP\Sharing\Permission\SharePermissionPreset;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

/**
 * @since 35.0.0
 */
// TODO: Move to transaction based API
// 1. Read share from DB
// 2. Apply operations on object
// 3. Validate object
// 4. Submit operations to DB
#[Consumable(since: '35.0.0')]
interface ISharingManager {
	/**
	 * @param ?list<class-string<IShareRecipientType>> $recipientTypeClasses
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function searchRecipients(ShareAccessContext $accessContext, ?array $recipientTypeClasses, string $query, int $limit, int $offset): array;

	/**
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function generateSecret(): string;

	/**
	 * @since 35.0.0
	 */
	public function createShare(ShareAccessContext $accessContext): string;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, SharePermissionPreset $permissionPreset): void;

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function deleteShare(ShareAccessContext $accessContext, string $id): void;

	/**
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	/**
	 * @param ?class-string<IShareSourceType> $sourceTypeClass
	 * @param ?positive-int $limit
	 * @return list<Share>
	 * @throws ShareInvalidException
	 * @since 35.0.0
	 */
	public function listShares(ShareAccessContext $accessContext, ?string $sourceTypeClass, ?string $lastShareID, ?int $limit): array;
}
