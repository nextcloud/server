<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing\SharingLegacySync;

use NCU\Sharing\ShareAccessContext;
use OC\Share20\ShareAttributes;
use OCP\Constants;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\Group;

#[Group(name: 'DB')]
final class DeleteTest extends AbstractTests {
	public function testDeleteShare(): void {
		$this->legacySharingManager->createShare(
			$this->legacySharingManager->newShare()
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
		);

		$shares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $shares);

		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $shares[0]);

		$this->assertEmpty(iterator_to_array($this->legacySharingManager->getAllShares()));
	}

	public function testDeleteLegacyShare(): void {
		$legacyShare = $this->legacySharingManager->createShare(
			$this->legacySharingManager->newShare()
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
		);

		$shares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $shares);

		$this->legacySharingManager->deleteShare($legacyShare);

		$this->assertEmpty($this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null));
	}
}
