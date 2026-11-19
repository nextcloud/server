<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Test\Traits\UserTrait;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class TestCaseTest extends TestCase {
	use UserTrait;

	// This test cannot fail if this suite is run standalone.
	// It will only fail if the mount provider collection wasn't correctly reset after a different test suite was executed before this one.
	public function testMountProviderCollectionReset(): void {
		$owner = $this->createUser('owner', 'password');
		$receiver = $this->createUser('receiver', 'password');

		$ownerUserFolder = Server::get(IRootFolder::class)->getUserFolder($owner->getUID());
		$ownerNode = $ownerUserFolder->newFolder('foo');
		$this->assertInstanceOf(Folder::class, $ownerNode);

		$manager = Server::get(IManager::class);
		$manager->createShare(
			$manager->newShare()
				->setShareType(IShare::TYPE_USER)
				->setShareOwner($owner->getUID())
				->setSharedBy($owner->getUID())
				->setSharedWith($receiver->getUID())
				->setNode($ownerNode)
				->setPermissions(Constants::PERMISSION_ALL)
		);

		$receiverUserFolder = Server::get(IRootFolder::class)->getUserFolder($receiver->getUID());
		$receiverNode = $receiverUserFolder->get($ownerNode->getName());
		$this->assertInstanceOf(Folder::class, $receiverNode);
	}
}
