<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\UserMigration;

use OC\Share20\Share;
use OCA\Files_Sharing\Tests\TestCase;
use OCA\Files_Sharing\UserMigration\SharesMigrator;
use OCP\Files\Node;
use OCP\IUserManager;
use OCP\Share\IShare;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class SharesMigratorTest extends TestCase {
	private IUserManager $userManager;
	private SharesMigrator $migrator;

	private OutputInterface&MockObject $output;

	private Node $userFolder;
	private IShare $userShare;
	private IShare $groupShare;
	private IShare $linkShare;

	private const ASSETS_PATH = __DIR__ . '/assets/shares.json';

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = \OCP\Server::get(IUserManager::class);
		$this->migrator = \OCP\Server::get(SharesMigrator::class);
		$this->output = $this->createMock(OutputInterface::class);
		$this->loginHelper(static::TEST_FILES_SHARING_API_USER1);

		$this->userFolder = $this->rootFolder->getUserFolder(static::TEST_FILES_SHARING_API_USER1);

		$this->userFolder->newFile('test-file-user-share.txt', $this->data);
		$this->userFolder->newFile('test-file-group-share.txt', $this->data);
		$this->userFolder->newFile('test-file-public-share.txt', $this->data);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->userFolder->get('test-file-user-share.txt')->delete();
		$this->userFolder->get('test-file-group-share.txt')->delete();
		$this->userFolder->get('test-file-public-share.txt')->delete();
	}

	public function testImportExport(): void {
		$initiator = $this->userManager->get(static::TEST_FILES_SHARING_API_USER1);
		$importSource = $this->createMock(IImportSource::class);

		$importSource
			->expects($this->once())
			->method('getMigratorVersion')
			->willReturn(1);

		$importSource
			->expects($this->once())
			->method('pathExists')
			->willReturn(true);

		$assetContents = [];
		$importSource
			->expects($this->once())
			->method('getFileContents')
			->willReturnCallback(function (string $path) use (&$assetContents) {
				$contents = file_get_contents(static::ASSETS_PATH);
				$assetContents = json_decode($contents, true);
				return $contents;
			});

		$this->migrator->import($initiator, $importSource, $this->output);
		$assetContents = array_filter($assetContents, function (array $shareData) {
			return $shareData['path'] !== '/' . static::TEST_FILES_SHARING_API_USER1 . '/files/test-file-deleted-share.txt';
		});
		$assetContents = array_values($assetContents);

		$exportDestination = $this->createMock(IExportDestination::class);
		$exportDestination
			->expects($this->once())
			->method('addFileContents')
			->willReturnCallback(function (string $path, string $content) use (&$exportedShares) {
				$shares = $this->invokePrivate($this->migrator, 'getShares', [static::TEST_FILES_SHARING_API_USER1]);
				$exportedShares = $shares;
				return $shares;
			});

		$this->migrator->export($initiator, $exportDestination, $this->output);

		$this->assertEquals(count($assetContents), count($exportedShares));
		$this->assertEquals($assetContents[0]['path'], $exportedShares[0]['path']);
		$this->assertEquals($assetContents[0]['shareType'], $exportedShares[0]['shareType']);
		$this->assertEquals($assetContents[0]['token'], $exportedShares[0]['token']);
		$this->assertEquals($assetContents[1]['path'], $exportedShares[1]['path']);
		$this->assertEquals($assetContents[1]['shareType'], $exportedShares[1]['shareType']);
		$this->assertEquals($assetContents[1]['token'], $exportedShares[1]['token']);
		$this->assertEquals($assetContents[2]['path'], $exportedShares[2]['path']);
		$this->assertEquals($assetContents[2]['shareType'], $exportedShares[2]['shareType']);
		$this->assertEquals($assetContents[2]['token'], $exportedShares[2]['token']);
	}

	/**
	 * @param string $path The path to share relative to $initiators root
	 * @param string $initiator
	 * @param int $permissions
	 * @return IShare
	 */
	protected function shareLink(string $path, string $initiator, int $permissions): IShare {
		$userFolder = $this->rootFolder->getUserFolder($initiator);
		$node = $userFolder->get($path);

		$share = $this->shareManager->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedBy($initiator)
			->setNode($node)
			->setPermissions($permissions)
			->setStatus(IShare::STATUS_PENDING);
		$share = $this->shareManager->createShare($share);

		return $share;
	}
}
