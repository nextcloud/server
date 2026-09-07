<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing\SharingLegacySync;

use DateTime;
use DateTimeImmutable;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\ShareUserStatus;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OC\Core\Sharing\Property\NoteSharePropertyType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\Share20\ShareAttributes;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\Constants;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group(name: 'DB')]
final class UserStatusTest extends AbstractTests {
	/**
	 * @return list<array{ShareUserStatus, IShare::STATUS_*}>
	 */
	public static function dataShareUserStatus(): array {
		return [
			[ShareUserStatus::Pending, IShare::STATUS_PENDING],
			[ShareUserStatus::Accepted, IShare::STATUS_ACCEPTED],
			[ShareUserStatus::Rejected, IShare::STATUS_REJECTED],
		];
	}

	#[DataProvider('dataShareUserStatus')]
	public function testShareUserStatus(ShareUserStatus $shareUserStatus, int $legacyStatus): void {
		$created = new DateTimeImmutable();

		$share = new Share(
			'123',
			new ShareUser($this->owner->getUID(), null),
			$created,
			$created,
			ShareState::Active,
			$shareUserStatus,
			[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
			[new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), new ShareUser($this->owner->getUID(), null))],
			[
				ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
				NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
			],
			[
				NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
				NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
				ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
				NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
				NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
				NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
			],
		);

		$legacyShare = $this->legacySharingManager->newShare()
			->setProviderId('ocinternal')
			->setNode($this->nodeFolder)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith($this->user1->getUID())
			->setSharedWithDisplayName($this->user1->getDisplayName())
			->setSharedBy($this->owner->getUID())
			->setShareOwner($this->owner->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
			->setStatus($legacyStatus)
			->setTarget('/' . $this->nodeFolder->getName())
			->setShareTime(DateTime::createFromImmutable($created))
			->setMailSend(false);

		$this->assertSyncingWorks($share, [$legacyShare]);
	}
}
