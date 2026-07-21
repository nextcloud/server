<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IUser;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Exception\ShareOperationForbiddenException;
use OCP\Sharing\Permission\ISharePermissionPreset;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISharingManager {
	/**
	 * Search for recpients that can be added to a share.
	 *
	 * @param ?list<class-string<IShareRecipientType>> $recipientTypeClasses
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @since 35.0.0
	 */
	public function searchRecipients(ShareAccessContext $accessContext, ?array $recipientTypeClasses, string $query, int $limit, int $offset): array;

	/**
	 * Generate a new secret.
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function generateSecret(): string;

	/**
	 * Generate a new timestamp in milliseconds since the UNIX epoch.
	 *
	 * @return non-negative-int
	 * @since 35.0.0
	 */
	public function generateTimestamp(): int;

	/**
	 * Create a new share.
	 *
	 * @since 35.0.0
	 */
	public function createShare(ShareAccessContext $accessContext): string;

	/**
	 * Perform all updates when the owner was deleted.
	 *
	 * @since 35.0.0
	 */
	public function onOwnerDeleted(ShareAccessContext $accessContext, IUser $owner): void;

	/**
	 * Update the state of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void;

	/**
	 * Add a new source to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * Remove an existing source from a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * Perform all updates when the source was deleted.
	 *
	 * @since 35.0.0
	 */
	public function onSourceDeleted(ShareAccessContext $accessContext, ShareSource $source): void;

	/**
	 * Add a new recipient to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the recipient was deleted.
	 *
	 * @since 35.0.0
	 */
	public function onRecipientDeleted(ShareAccessContext $accessContext, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the initiator was deleted.
	 *
	 * @since 35.0.0
	 */
	public function onInitiatorDeleted(ShareAccessContext $accessContext, IUser $initiator): void;

	/**
	 * Update the scecret of a recipient.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): void;

	/**
	 * Update a property of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void;

	/**
	 * Update a permission of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void;

	/**
	 * Select a permission preset for a share.
	 *
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, string $permissionPresetClass): void;

	/**
	 * Delete a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @since 35.0.0
	 */
	public function deleteShare(ShareAccessContext $accessContext, string $id): void;

	/**
	 * Get a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	// TODO: Implement filtering by state.
	/**
	 * Get multiple shares.
	 *
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @param ?positive-int $limit
	 * @return list<Share>
	 * @since 35.0.0
	 */
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array;
}
