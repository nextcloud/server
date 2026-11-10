<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests;

use OC\Files\View;
use OCA\Files_Sharing\Helper;
use OCA\Files_Sharing\ViewOnly;
use OCP\Constants;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\Group;

#[Group(name: 'DB')]
class ReshareAttributesTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Helper::registerHooks();
	}

	protected function setUp(): void {
		parent::setUp();

		$this->view->mkdir('folder');
		$this->view->mkdir('folder/sub');
		$this->view->file_put_contents('folder/sub/file.txt', $this->data);
	}

	private function createShare(int $type, string $path, string $initiator, ?string $recipient = null): IShare {
		$this->loginHelper($initiator);
		if ($recipient !== null) {
			return $this->share($type, $path, $initiator, $recipient, Constants::PERMISSION_ALL);
		}

		$share = $this->shareManager->newShare()
			->setShareType($type)
			->setSharedBy($initiator)
			->setNode($this->rootFolder->getUserFolder($initiator)->get($path))
			->setPermissions(Constants::PERMISSION_READ);
		return $this->shareManager->createShare($share);
	}

	private function setDownload(IShare $share, bool $canDownload): void {
		$this->loginHelper($share->getSharedBy());
		$share = $this->shareManager->getShareById($share->getFullId());
		$attributes = $share->getAttributes() ?? $share->newAttributes();
		$attributes->setAttribute('permissions', 'download', $canDownload);
		$share->setAttributes($attributes);
		$this->shareManager->updateShare($share);
	}

	private function canDownload(IShare $share): bool {
		return $this->shareManager->getShareById($share->getFullId())->canDownload();
	}

	/**
	 * Moving between storages on object store primary storage creates new
	 * file ids, so the shares of the moved node no longer resolve.
	 */
	private function assertCannotServeDownloads(IShare $share): void {
		$share = $this->shareManager->getShareById($share->getFullId());
		try {
			$share->getNode();
			$nodeExists = true;
		} catch (NotFoundException) {
			$nodeExists = false;
		}
		$this->assertFalse($nodeExists && $share->canDownload());
	}

	public function testRestrictingDownloadRestrictsReshareChain(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$reshare = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3);
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);
		$fileLink = $this->createShare(IShare::TYPE_LINK, 'folder/sub/file.txt', self::TEST_FILES_SHARING_API_USER2);
		$chainedLink = $this->createShare(IShare::TYPE_LINK, 'folder/sub', self::TEST_FILES_SHARING_API_USER3);

		$this->setDownload($share, false);

		$this->assertFalse($this->canDownload($reshare));
		$this->assertFalse($this->canDownload($link));
		$this->assertFalse($this->canDownload($fileLink));
		$this->assertFalse($this->canDownload($chainedLink));
	}

	public function testRestrictingDownloadKeepsSharesOfTheOwner(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$ownerShare = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER3);
		$ownerLink = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER1);

		$this->setDownload($share, false);

		$this->assertTrue($this->canDownload($ownerShare));
		$this->assertTrue($this->canDownload($ownerLink));
	}

	public function testRestrictingDownloadKeepsResharesWithAnotherAllowingShare(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$groupShare = $this->createShare(IShare::TYPE_GROUP, 'folder', self::TEST_FILES_SHARING_API_USER1, 'group1');
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);

		$this->setDownload($share, false);
		$this->assertTrue($this->canDownload($link));

		$this->setDownload($groupShare, false);
		$this->assertFalse($this->canDownload($link));
	}

	public function testReceivedNodeIsDownloadableIfAnyReceivedShareAllowsIt(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$this->setDownload($share, false);
		$this->createShare(IShare::TYPE_GROUP, 'folder', self::TEST_FILES_SHARING_API_USER1, 'group1');

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$node = $this->rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER2)->get('folder/sub/file.txt');
		$this->assertTrue((new ViewOnly(null))->isDownloadable($node));
	}

	public function testRestrictingDownloadRestrictsCyclicReshares(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3);
		$cyclicShare = $this->createShare(IShare::TYPE_GROUP, 'folder', self::TEST_FILES_SHARING_API_USER3, 'group1');
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);

		$this->setDownload($share, false);

		$this->assertFalse($this->canDownload($cyclicShare));
		$this->assertFalse($this->canDownload($link));
	}

	public function testRestrictingDownloadKeepsCyclicResharesReachableFromTheOwner(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$otherShare = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER3);
		$this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER4);
		$this->createShare(IShare::TYPE_GROUP, 'folder', self::TEST_FILES_SHARING_API_USER3, 'group1');
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);

		$this->setDownload($share, false);
		$this->assertTrue($this->canDownload($link));

		$this->setDownload($otherShare, false);
		$this->assertFalse($this->canDownload($link));
	}

	public function testRestrictingDownloadOfReshareRestrictsItsReshares(): void {
		$this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$reshare = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3);
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);
		$chainedLink = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER3);

		$this->setDownload($reshare, false);

		$this->assertTrue($this->canDownload($link));
		$this->assertFalse($this->canDownload($chainedLink));
	}

	public function testAllowingDownloadAgainKeepsResharesRestricted(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);

		$this->setDownload($share, false);
		$this->setDownload($share, true);

		$this->assertFalse($this->canDownload($link));
	}

	public function testUpdatingPermissionsKeepsResharesUnchanged(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$share = $this->shareManager->getShareById($share->getFullId());
		$share->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE);
		$this->shareManager->updateShare($share);

		$this->assertNull($this->shareManager->getShareById($link->getFullId())->getAttributes());
	}

	public function testRestrictingDownloadRestrictsResharesOfUnresolvableSharers(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$link = $this->createShare(IShare::TYPE_LINK, 'folder', self::TEST_FILES_SHARING_API_USER2);

		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->update('share')
			->set('uid_initiator', $qb->createNamedParameter('non-existing-user'))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($link->getId())))
			->executeStatement();

		$this->setDownload($share, false);

		$this->assertFalse($this->canDownload($link));
	}

	public function testMovingIntoRestrictedShareRestrictsReshares(): void {
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$this->setDownload($share, false);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view->mkdir('own');
		$view->file_put_contents('own/file.txt', $this->data);
		$link = $this->createShare(IShare::TYPE_LINK, 'own', self::TEST_FILES_SHARING_API_USER2);
		$reshare = $this->createShare(IShare::TYPE_USER, 'own', self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3);
		$chainedLink = $this->createShare(IShare::TYPE_LINK, 'own', self::TEST_FILES_SHARING_API_USER3);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view->rename('own', 'folder/own');

		$this->assertCannotServeDownloads($link);
		$this->assertCannotServeDownloads($reshare);
		$this->assertCannotServeDownloads($chainedLink);
	}

	public function testMovingByOwnerIntoRestrictedShareRestrictsReshares(): void {
		$this->view->mkdir('open');
		$this->view->mkdir('open/moved');
		$share = $this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$this->createShare(IShare::TYPE_USER, 'open', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);
		$this->setDownload($share, false);
		$link = $this->createShare(IShare::TYPE_LINK, 'open/moved', self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($this->canDownload($link));

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->rename('open/moved', 'folder/moved');

		$this->assertFalse($this->canDownload($link));
	}

	public function testMovingIntoAllowingShareKeepsReshares(): void {
		$this->createShare(IShare::TYPE_USER, 'folder', self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view->mkdir('own');
		$link = $this->createShare(IShare::TYPE_LINK, 'own', self::TEST_FILES_SHARING_API_USER2);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view->rename('own', 'folder/own');

		$this->assertTrue($this->canDownload($link));
	}
}
