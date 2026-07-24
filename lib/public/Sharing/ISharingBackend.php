<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Implementable;
use OCP\IUser;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Permission\ISharePermissionPreset;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

// TODO: Add ability to start and commit a transaction
/**
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ISharingBackend {
	/**
	 * Create a new share.
	 *
	 * @since 35.0.0
	 */
	public function createShare(IUser $owner): string;

	/**
	 * Perform all updates when the owner was deleted.
	 *
	 * @since 35.0.0
	 */
	public function onOwnerDeleted(IUser $owner): void;

	/**
	 * Update the state of a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function updateShareState(string $id, ShareState $state): void;

	/**
	 * Add a new source to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function addShareSource(string $id, ShareSource $source): void;

	/**
	 * Remove an existing source from a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function removeShareSource(string $id, ShareSource $source): void;

	/**
	 * Perform all updates when the source was deleted.
	 *
	 * @return list<string>
	 * @since 35.0.0
	 */
	public function onSourceDeleted(ShareSource $source): array;

	/**
	 * Add a new recipient to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function addShareRecipient(string $id, IUser $initiator, ShareRecipient $recipient): void;

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function removeShareRecipient(string $id, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the recipient was deleted.
	 *
	 * @return list<string>
	 * @since 35.0.0
	 */
	public function onRecipientDeleted(ShareRecipient $recipient): array;

	/**
	 * Perform all updates when the initiator was deleted.
	 *
	 * @return list<string>
	 * @since 35.0.0
	 */
	public function onInitiatorDeleted(IUser $initiator): array;

	/**
	 * Update the scecret of a recipient.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function updateShareRecipientSecret(string $id, ShareRecipient $recipient, string $secret): void;

	/**
	 * Update a property of a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function updateShareProperty(string $id, ShareProperty $property): void;

	/**
	 * Update a permission of a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function updateSharePermission(string $id, SharePermission $permission): void;

	/**
	 * Select a permission preset for a share.
	 *
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function selectSharePermissionPreset(string $id, string $permissionPresetClass): void;

	/**
	 * Delete a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function deleteShare(string $id): void;

	/**
	 * Get a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	/**
	 * Get multiple shares.
	 *
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @param ?positive-int $limit
	 * @return list<Share>
	 * @since 35.0.0
	 */
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array;

	/**
	 * Check if a share ID belongs to this backend.
	 *
	 * @since 35.0.0
	 */
	public function hasShare(string $id): bool;

	/**
	 * Get the owner of a share.
	 *
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function getShareOwner(string $id): ShareUser;

	/**
	 * Set the last updated timestamp for multiple shares.
	 *
	 * @param non-empty-list<string> $ids
	 * @param non-negative-int $lastUpdated
	 * @throws ShareNotFoundException
	 * @since 35.0.0
	 */
	public function setLastUpdated(array $ids, int $lastUpdated): void;
}
