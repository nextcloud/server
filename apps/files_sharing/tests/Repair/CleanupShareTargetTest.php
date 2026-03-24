<?php

/**
 * SPDX-FileCopyrightText: 2026
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Repair;

use OC\Migration\NullOutput;
use OCA\Files_Sharing\Repair\CleanupShareTarget;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\Files\NotFoundException;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\Group;

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
#[Group(name: 'DB')]
class CleanupShareTargetTest extends TestCase {
	public const TEST_FOLDER_NAME = '/folder_share_api_test';

	private CleanupShareTarget $cleanupShareTarget;

	protected function setUp(): void {
		parent::setUp();
		$this->cleanupShareTarget = Server::get(CleanupShareTarget::class);
	}

	private function createUserShare(string $by, string $target = self::TEST_FOLDER_NAME): IShare {
		$userFolder = $this->rootFolder->getUserFolder($by);

		try {
			$node = $userFolder->get(self::TEST_FOLDER_NAME);
		} catch (NotFoundException $e) {
			$node = $userFolder->newFolder(self::TEST_FOLDER_NAME);
		}
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node)
			->setSharedBy($by)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share = $this->shareManager->createShare($share1);
		$share->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share);

		$share->setTarget($target);
		$this->shareManager->moveShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertEquals($target, $share->getTarget());

		return $share;
	}

	public function testBasicRepair() {
		$share = $this->createUserShare(self::TEST_FILES_SHARING_API_USER1, self::TEST_FOLDER_NAME . ' (2) (2) (2) (2)');

		$this->cleanupShareTarget->run(new NullOutput());

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertEquals(self::TEST_FOLDER_NAME, $share->getTarget());
	}

	public function testRepairConflictFile() {
		$share = $this->createUserShare(self::TEST_FILES_SHARING_API_USER1, self::TEST_FOLDER_NAME . ' (2) (2) (2) (2)');

		$userFolder2 = $this->rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER2);
		$folder = $userFolder2->newFolder(self::TEST_FOLDER_NAME);

		$this->cleanupShareTarget->run(new NullOutput());
		$folder->delete();

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertEquals(self::TEST_FOLDER_NAME . ' (2)', $share->getTarget());
	}

	public function testRepairConflictShare() {
		$share = $this->createUserShare(self::TEST_FILES_SHARING_API_USER1, self::TEST_FOLDER_NAME . ' (2) (2) (2) (2)');

		$share2 = $this->createUserShare(self::TEST_FILES_SHARING_API_USER3);

		$this->cleanupShareTarget->run(new NullOutput());

		$share2 = $this->shareManager->getShareById($share2->getFullId());
		$this->assertEquals(self::TEST_FOLDER_NAME, $share2->getTarget());
		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertEquals(self::TEST_FOLDER_NAME . ' (2)', $share->getTarget());
	}

	public function testRepairMultipleConflicting() {
		$share = $this->createUserShare(self::TEST_FILES_SHARING_API_USER1, self::TEST_FOLDER_NAME . ' (2) (2) (2) (2)');
		$share2 = $this->createUserShare(self::TEST_FILES_SHARING_API_USER3, self::TEST_FOLDER_NAME . ' (2) (2) (2) (2) (2)');

		$this->cleanupShareTarget->run(new NullOutput());

		$share = $this->shareManager->getShareById($share->getFullId());
		$share2 = $this->shareManager->getShareById($share2->getFullId());

		// there is no guarantee for what order the 2 shares got repaired by
		$targets = [
			$share->getTarget(),
			$share2->getTarget(),
		];
		sort($targets);
		$this->assertEquals([
			self::TEST_FOLDER_NAME,
			self::TEST_FOLDER_NAME . ' (2)'
		], $targets);
	}
}
