<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\AppData;

use OC\Files\AppData\AppData;
use OC\SystemConfig;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\SimpleFS\ISimpleFolder;

class AppDataTest extends \Test\TestCase {
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;

	/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $systemConfig;

	/** @var IAppData */
	private $appData;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->systemConfig = $this->createMock(SystemConfig::class);
		$this->appData = new AppData($this->rootFolder, $this->systemConfig, 'myApp');

		$this->systemConfig->expects($this->any())
			->method('getValue')
			->with('instanceid', null)
			->willReturn('iid');
	}

	private function setupAppFolder() {
		$appFolder = $this->createMock(Folder::class);

		$this->rootFolder->expects($this->any())
			->method('get')
			->with($this->equalTo('appdata_iid/myApp'))
			->willReturn($appFolder);

		return $appFolder;
	}

	public function testGetFolder(): void {
		$folder = $this->createMock(Folder::class);

		$this->rootFolder->expects($this->once())
			->method('get')
			->with($this->equalTo('appdata_iid/myApp/folder'))
			->willReturn($folder);

		$result = $this->appData->getFolder('folder');
		$this->assertInstanceOf(ISimpleFolder::class, $result);
	}

	public function testNewFolder(): void {
		$appFolder = $this->setupAppFolder();

		$folder = $this->createMock(Folder::class);

		$appFolder->expects($this->once())
			->method('newFolder')
			->with($this->equalTo('folder'))
			->willReturn($folder);

		$result = $this->appData->newFolder('folder');
		$this->assertInstanceOf(ISimpleFolder::class, $result);
	}

	public function testGetDirectoryListing(): void {
		$appFolder = $this->setupAppFolder();

		$file = $this->createMock(File::class);
		$folder = $this->createMock(Folder::class);
		$node = $this->createMock(Node::class);

		$appFolder->expects($this->once())
			->method('getDirectoryListing')
			->willReturn([$file, $folder, $node]);

		$result = $this->appData->getDirectoryListing();

		$this->assertCount(1, $result);
		$this->assertInstanceOf(ISimpleFolder::class, $result[0]);
	}
}
