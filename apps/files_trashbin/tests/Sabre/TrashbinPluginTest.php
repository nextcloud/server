<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Tests\Sabre;

use OC\Files\FileInfo;
use OCA\Files_Trashbin\Sabre\ITrash;
use OCA\Files_Trashbin\Sabre\RestoreFolder;
use OCA\Files_Trashbin\Sabre\TrashbinPlugin;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IPreview;
use OCP\IUser;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Test\TestCase;

class TrashbinPluginTest extends TestCase {
	private Server $server;

	protected function setUp(): void {
		parent::setUp();

		$tree = $this->createMock(Tree::class);
		$this->server = new Server($tree);
	}

	/**
	 * @dataProvider quotaProvider
	 */
	public function testQuota(int $quota, int $fileSize, bool $expectedResult): void {
		$fileInfo = $this->createMock(ITrashItem::class);
		$fileInfo->method('getSize')->willReturn($fileSize);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('test');
		$fileInfo->method('getUser')
			->willReturn($user);
		$fileInfo->method('getOriginalLocation')
			->willReturn('relative/original/location');

		$trashNode = $this->createMock(ITrash::class);
		$trashNode->method('getFileInfo')->willReturn($fileInfo);

		$restoreNode = $this->createMock(RestoreFolder::class);

		$this->server->tree->method('getNodeForPath')->willReturn($trashNode, $restoreNode);

		$previewManager = $this->createMock(IPreview::class);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFullPath')
			->with('relative/original') // the parent path
			->willReturn('/full/path/to/original');
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')
			->willReturn($userFolder);

		$storage = $this->createMock(IStorage::class);
		$storage->method('free_space')->with('path/to/original')
			->willReturn($quota);
		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->with('/full/path/to/original')
			->willReturn('path/to/original');
		$mountManager = $this->createMock(IMountManager::class);
		$mountManager->method('find')
			->with('/full/path/to/original')
			->willReturn($mount);

		$plugin = new TrashbinPlugin($previewManager, $rootFolder, $mountManager);
		$plugin->initialize($this->server);

		$sourcePath = 'trashbin/test/trash/file1';
		$destinationPath = 'trashbin/test/restore/file1';
		$this->assertEquals($expectedResult, $plugin->beforeMove($sourcePath, $destinationPath));
	}

	public function quotaProvider(): array {
		return [
			[ 1024, 512, true ],
			[ 512, 513, false ],
			[ FileInfo::SPACE_NOT_COMPUTED, 1024, true ],
			[ FileInfo::SPACE_UNKNOWN, 1024, true],
			[ FileInfo::SPACE_UNLIMITED, 1024, true ]
		];
	}
}
