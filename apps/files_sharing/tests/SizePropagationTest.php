<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\View;
use OCP\Constants;
use OCP\ITempManager;
use OCP\Server;
use OCP\Share\IShare;
use Test\Traits\UserTrait;

/**
 * Class SizePropagationTest
 *
 *
 * @package OCA\Files_Sharing\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class SizePropagationTest extends TestCase {
	use UserTrait;

	protected function setupUser($name, $password = '') {
		$this->createUser($name, $password);
		$tmpFolder = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		$this->loginAsUser($name);
		return new View('/' . $name . '/files');
	}

	public function testSizePropagationWhenOwnerChangesFile(): void {
		$recipientView = $this->setupUser(self::TEST_FILES_SHARING_API_USER1);

		$ownerView = $this->setupUser(self::TEST_FILES_SHARING_API_USER2);
		$ownerView->mkdir('/sharedfolder/subfolder');
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');

		$this->share(
			IShare::TYPE_USER,
			'/sharedfolder',
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER1,
			Constants::PERMISSION_ALL
		);
		$ownerRootInfo = $ownerView->getFileInfo('', false);

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($recipientView->file_exists('/sharedfolder/subfolder/foo.txt'));
		$recipientRootInfo = $recipientView->getFileInfo('', false);

		// when file changed as owner
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		// size of recipient's root stays the same
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$newRecipientRootInfo = $recipientView->getFileInfo('', false);
		$this->assertEquals($recipientRootInfo->getSize(), $newRecipientRootInfo->getSize());

		// size of owner's root increases
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$newOwnerRootInfo = $ownerView->getFileInfo('', false);
		$this->assertEquals($ownerRootInfo->getSize() + 3, $newOwnerRootInfo->getSize());
	}

	public function testSizePropagationWhenRecipientChangesFile(): void {
		$recipientView = $this->setupUser(self::TEST_FILES_SHARING_API_USER1);

		$ownerView = $this->setupUser(self::TEST_FILES_SHARING_API_USER2);
		$ownerView->mkdir('/sharedfolder/subfolder');
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');

		$this->share(
			IShare::TYPE_USER,
			'/sharedfolder',
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER1,
			Constants::PERMISSION_ALL
		);
		$ownerRootInfo = $ownerView->getFileInfo('', false);

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($recipientView->file_exists('/sharedfolder/subfolder/foo.txt'));
		$recipientRootInfo = $recipientView->getFileInfo('', false);
		$recipientRootInfoWithMounts = $recipientView->getFileInfo('', true);
		$oldRecipientSize = $recipientRootInfoWithMounts->getSize();

		// when file changed as recipient
		$recipientView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		// size of recipient's root stays the same
		$newRecipientRootInfo = $recipientView->getFileInfo('', false);
		$this->assertEquals($recipientRootInfo->getSize(), $newRecipientRootInfo->getSize());

		// but the size including mountpoints increases
		$newRecipientRootInfo = $recipientView->getFileInfo('', true);
		$this->assertEquals($oldRecipientSize + 3, $newRecipientRootInfo->getSize());

		// size of owner's root increases
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$newOwnerRootInfo = $ownerView->getFileInfo('', false);
		$this->assertEquals($ownerRootInfo->getSize() + 3, $newOwnerRootInfo->getSize());
	}
}
