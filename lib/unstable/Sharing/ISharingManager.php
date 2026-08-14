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
use NCU\Sharing\Permission\SharePermission;
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
	 * @param ?Share $excludeShare If provided, recipients that are already part of the share will not be returned.
	 * @return list<ShareRecipient>
	 * @throws ShareNotFoundException
	 * @experimental 35.0.0
	 */
	public function searchRecipients(ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?Share $forShare = null): array;

	/**
	 * Generate a new secret.
	 *
	 * @return non-empty-string
	 * @experimental 35.0.0
	 */
	public function generateSecret(): string;

	/**
	 * Get the current time
	 *
	 * @experimental 35.0.0
	 */
	public function getTime(): \DateTimeImmutable;

	/**
	 * Create a new share.
	 *
	 * @experimental 35.0.0
	 */
	public function createShare(ShareAccessContext $accessContext): Share;

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
	public function updateShareState(ShareAccessContext $accessContext, Share $share, ShareState $state): Share;

	/**
	 * Add a new source to a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function addShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): Share;

	/**
	 * Remove an existing source from a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function removeShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): Share;

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
	public function addShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): Share;

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function removeShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): Share;

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
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, string $secret): Share;

	/**
	 * Update a property of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function updateShareProperty(ShareAccessContext $accessContext, Share $share, ShareProperty $property): Share;

	/**
	 * Update a permission of a share.
	 *
	 * @throws ShareInvalidException
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function updateSharePermission(ShareAccessContext $accessContext, Share $share, SharePermission $permission): Share;

	/**
	 * Select a permission preset for a share.
	 *
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, Share $share, string $permissionPresetClass): Share;

	/**
	 * Delete a share.
	 *
	 * @throws ShareNotFoundException
	 * @throws ShareOperationForbiddenException
	 * @experimental 35.0.0
	 */
	public function deleteShare(ShareAccessContext $accessContext, Share $share): void;

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
