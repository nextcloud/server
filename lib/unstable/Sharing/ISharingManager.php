<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use NCU\Sharing\Exception\ShareInvalidException;
use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\Exception\ShareOperationForbiddenException;
use NCU\Sharing\Permission\ISharePermissionPreset;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSource;
use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISharingManager {
	/**
	 * Search for recipients that can be added to a share.
	 *
	 * @param ?list<class-string<IShareRecipientType>> $filterRecipientTypeClasses
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @param ?string $id If provided, recipients that are already part of the share will not be returned.
	 * @return list<ShareRecipient>
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function searchRecipients(ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?string $id = null): array;

	/**
	 * Generate a new secret.
	 *
	 * @return non-empty-string
	 * @experimental 35.0.0
	 */
	public function generateSecret(): string;

	/**
	 * Generate a new timestamp in milliseconds since the UNIX epoch.
	 *
	 * @return non-negative-int
	 * @experimental 35.0.0
	 */
	public function generateTimestamp(): int;

	/**
	 * Create a new share.
	 *
	 * @experimental 35.0.0
	 */
	public function createShare(ShareAccessContext $accessContext): string;

	/**
	 * Perform all updates when the owner was deleted.
	 *
	 * @experimental 35.0.0
	 */
	public function onOwnerDeleted(ShareAccessContext $accessContext, ShareUser $owner): void;

	/**
	 * Update the state of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void;

	/**
	 * Add a new source to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * Remove an existing source from a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void;

	/**
	 * Perform all updates when the source was deleted.
	 *
	 * @experimental 35.0.0
	 */
	public function onSourceDeleted(ShareAccessContext $accessContext, ShareSource $source): void;

	/**
	 * Add a new recipient to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the recipient was deleted.
	 *
	 * @experimental 35.0.0
	 */
	public function onRecipientDeleted(ShareAccessContext $accessContext, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the initiator was deleted.
	 *
	 * @experimental 35.0.0
	 */
	public function onInitiatorDeleted(ShareAccessContext $accessContext, ShareUser $initiator): void;

	/**
	 * Update the secret of a recipient.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function createSharePropertyDefaultValue(Share $share, string $propertyTypeClass): Share;

	/**
	 * Update a property of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void;

	/**
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function createSharePermissionDefaultValue(Share $share, string $permissionTypeClass): Share;

	/**
	 * Update a permission of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void;

	/**
	 * Select a permission preset for a share.
	 *
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, string $permissionPresetClass): void;

	/**
	 * Delete a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function deleteShare(ShareAccessContext $accessContext, string $id): void;

	/**
	 * Get a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	// TODO: Implement filtering by state.
	/**
	 * Get multiple shares.
	 *
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @param ?non-empty-string $filterSourceTypeValue
	 * @param ?positive-int $limit
	 * @return list<Share>
	 * @experimental 35.0.0
	 */
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array;
}
