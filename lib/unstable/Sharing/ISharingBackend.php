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
interface ISharingBackend {
	/**
	 * Create a new share.
	 *
	 * @param non-empty-string $id
	 * @experimental 35.0.0
	 */
	public function createShare(string $id, ShareUser $owner, \DateTimeImmutable $lastUpdated): void;

	/**
	 * Perform all updates when the owner was deleted.
	 *
	 * @return list<non-empty-string>
	 * @experimental 35.0.0
	 */
	public function onOwnerDeleted(ShareUser $owner): array;

	/**
	 * Update the state of a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareState(string $id, ShareState $state): void;

	/**
	 * Update the user status for a share.
	 *
	 * @param non-empty-string $id
	 * @param non-empty-string $userId
	 * @experimental 35.0.0
	 */
	public function updateShareUserStatus(string $id, string $userId, ShareUserStatus $userStatus): void;

	/**
	 * Add a new source to a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function addShareSource(string $id, ShareSource $source): void;

	/**
	 * Remove an existing source from a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function removeShareSource(string $id, ShareSource $source): void;

	/**
	 * Perform all updates when the source was deleted.
	 *
	 * @return list<non-empty-string>
	 * @experimental 35.0.0
	 */
	public function onSourceDeleted(ShareSource $source): array;

	/**
	 * Add a new recipient to a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function addShareRecipient(string $id, ShareRecipient $recipient): void;

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function removeShareRecipient(string $id, ShareRecipient $recipient): void;

	/**
	 * Perform all updates when the recipient was deleted.
	 *
	 * @return list<non-empty-string>
	 * @experimental 35.0.0
	 */
	public function onRecipientDeleted(ShareRecipient $recipient): array;

	/**
	 * Perform all updates when the initiator was deleted.
	 *
	 * @return list<non-empty-string>
	 * @experimental 35.0.0
	 */
	public function onInitiatorDeleted(ShareUser $initiator): array;

	/**
	 * Update the secret of a recipient.
	 *
	 * @param non-empty-string $id
	 * @param non-empty-string $secret
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareRecipientSecret(string $id, ShareRecipient $recipient, string $secret): void;

	/**
	 * Update a property of a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareProperty(string $id, ShareProperty $property): void;

	/**
	 * Get the value of a property of a share.
	 *
	 * @param non-empty-string $id
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function getSharePropertyValue(string $id, string $propertyTypeClass): ?string;

	/**
	 * Update a permission of a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateSharePermission(string $id, SharePermission $permission): void;

	/**
	 * Update a permission for a recipient of a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function updateShareRecipientPermission(string $id, ShareRecipient $recipient, SharePermission $permission): void;

	/**
	 * Select a permission preset for a share.
	 *
	 * @param non-empty-string $id
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function selectSharePermissionPreset(string $id, string $permissionPresetClass): void;

	/**
	 * Delete a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function deleteShare(string $id): void;

	/**
	 * Get a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function getShare(ShareAccessContext $accessContext, string $id): Share;

	/**
	 * Get multiple shares.
	 *
	 * @param ?non-empty-list<non-empty-string> $filterShareIDs
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @param ?non-empty-string $filterSourceTypeValue
	 * @param ?non-empty-string $lastShareID
	 * @param ?positive-int $limit
	 * @return list<Share>
	 * @experimental 35.0.0
	 */
	public function getShares(ShareAccessContext $accessContext, ?array $filterShareIDs, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?ShareState $filterState, ?ShareUserStatus $filterUserStatus, ?string $lastShareID, ?int $limit): array;

	/**
	 * Get the owner of a share.
	 *
	 * @param non-empty-string $id
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function getShareOwner(string $id): ShareUser;

	/**
	 * Set the last updated timestamp for multiple shares.
	 *
	 * @param non-empty-list<non-empty-string> $ids
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function setLastUpdated(array $ids, \DateTimeImmutable $lastUpdated): void;

	/**
	 * Ensure that all relevant properties and permissions have a value set, inserting defaults when needed
	 *
	 * @param non-empty-array<Share> $shares
	 * @return non-empty-array<Share>
	 * @experimental 35.0.0
	 */
	public function ensureDefaults(array $shares): array;

	/**
	 * Get a list of recipients a user has shared with, ordered by share count
	 *
	 * "shared with" includes both shares owned by the user, and reshares initiated by the user
	 *
	 * @param ?non-empty-list<class-string<IShareRecipientType>> $filterRecipientTypeClasses
	 * @param null|non-empty-string $notInShare
	 * @param non-negative-int $count
	 * @return list<ShareRecipient>
	 * @experimental 35.0.0
	 */
	public function getRecommendedRecipients(
		ShareUser $user,
		?array $filterRecipientTypeClasses = null,
		?string $notInShare = null,
		int $count = 5,
		int $offset = 0,
	): array;
}
