<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use NCU\Sharing\Exception\ShareInvalidException;
use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\Permission\ISharePermissionPreset;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSource;
use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISharingBackend {
	/**
	 * Create a new share.
	 *
	 * @experimental 35.0.0
	 */
	public function createShare(string $id, ShareUser $owner, \DateTimeImmutable $lastUpdated): void;

	/**
	 * Perform all updates when the owner was deleted.
	 *
	 * @return list<string>
	 * @experimental 35.0.0
	 */
	public function onOwnerDeleted(ShareUser $owner): array;

	/**
	 * Update the state of a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareState(string $id, ShareState $state): void;

	/**
	 * Add a new source to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function addShareSource(string $id, ShareSource $source): void;

	/**
	 * Remove an existing source from a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function removeShareSource(string $id, ShareSource $source): void;

	/**
	 * Perform all updates when the source was deleted.
	 *
	 * @return list<string>
	 * @experimental 35.0.0
	 */
	public function onSourceDeleted(ShareSource $source): array;

	/**
	 * Add a new recipient to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function addShareRecipient(string $id, ShareRecipient $recipient): void;

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function removeShareRecipient(string $id, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the recipient was deleted.
	 *
	 * @return list<string>
	 * @experimental 35.0.0
	 */
	public function onRecipientDeleted(ShareRecipient $recipient): array;

	/**
	 * Perform all updates when the initiator was deleted.
	 *
	 * @return list<string>
	 * @experimental 35.0.0
	 */
	public function onInitiatorDeleted(ShareUser $initiator): array;

	/**
	 * Update the secret of a recipient.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareRecipientSecret(string $id, ShareRecipient $recipient, string $secret): void;

	/**
	 * Insert a property for a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function createShareProperty(string $id, ShareProperty $property): void;

	/**
	 * Update a property of a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareProperty(string $id, ShareProperty $property): void;

	/**
	 * Insert a permission for a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function createSharePermission(string $id, SharePermission $permission): void;

	/**
	 * Update a permission of a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateSharePermission(string $id, SharePermission $permission): void;

	/**
	 * Select a permission preset for a share.
	 *
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function selectSharePermissionPreset(string $id, string $permissionPresetClass): void;

	/**
	 * Delete a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function deleteShare(string $id): void;

	/**
	 * Get a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

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

	/**
	 * Check if a share ID belongs to this backend.
	 *
	 * @experimental 35.0.0
	 */
	public function hasShare(string $id): bool;

	/**
	 * Get the owner of a share.
	 *
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function getShareOwner(string $id): ShareUser;

	/**
	 * Set the last updated timestamp for multiple shares.
	 *
	 * @param non-empty-list<string> $ids
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function setLastUpdated(array $ids, \DateTimeImmutable $lastUpdated): void;
}
