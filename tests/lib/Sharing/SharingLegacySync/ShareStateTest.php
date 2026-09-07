<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing\SharingLegacySync;

use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\Constants;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group(name: 'DB')]
final class ShareStateTest extends AbstractTests {
	/**
	 * @return list<array{ShareState, int}>
	 */
	public static function dataShareState(): array {
		return [
			[ShareState::Active, 1],
			[ShareState::Draft, 0],
			[ShareState::Deleted, 0],
		];
	}

	#[DataProvider('dataShareState')]
	public function testShareStateToLegacy(ShareState $shareState, int $legacySharesCount): void {
		$accessContext = new ShareAccessContext($this->owner);
		$share = $this->sharingManager->createShare($accessContext);
		$share = $this->sharingManager->addShareSource($accessContext, $share, new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId()));
		$share = $this->sharingManager->addShareRecipient($accessContext, $share, new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null));
		$share = $this->sharingManager->updateSharePermission($accessContext, $share, new SharePermission(NodeReadSharePermissionType::class, true));
		$share = $this->sharingManager->updateSharePermission($accessContext, $share, new SharePermission(NodeDownloadSharePermissionType::class, true));

		$this->sharingManager->updateShareState($accessContext, $share, $shareState);

		$legacyShares = array_values(iterator_to_array($this->legacySharingManager->getAllShares()));
		$this->assertCount($legacySharesCount, $legacyShares);
	}

	public function testShareStateFromLegacy(): void {
		$this->legacySharingManager->createShare(
			$this->legacySharingManager->newShare()
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
		);

		$shares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $shares);
		$this->assertEquals(ShareState::Active, $shares[0]->state);
	}
}
