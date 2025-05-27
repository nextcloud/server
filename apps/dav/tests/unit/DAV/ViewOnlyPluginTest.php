<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File as DavFile;
use OCA\DAV\DAV\ViewOnlyPlugin;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Versions\Sabre\VersionFile;
use OCA\Files_Versions\Versions\IVersion;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;

class ViewOnlyPluginTest extends TestCase {
	private Tree&MockObject $tree;
	private RequestInterface&MockObject $request;
	private Folder&MockObject $userFolder;
	private ViewOnlyPlugin $plugin;

	public function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(Folder::class);
		$this->request = $this->createMock(RequestInterface::class);
		$this->tree = $this->createMock(Tree::class);
		$server = $this->createMock(Server::class);

		$this->plugin = new ViewOnlyPlugin(
			$this->userFolder,
		);
		$server->tree = $this->tree;

		$this->plugin->initialize($server);
	}

	public function testCanGetNonDav(): void {
		$this->request->expects($this->once())->method('getPath')->willReturn('files/test/target');
		$this->tree->method('getNodeForPath')->willReturn(null);

		$this->assertTrue($this->plugin->checkViewOnly($this->request));
	}

	public function testCanGetNonShared(): void {
		$this->request->expects($this->once())->method('getPath')->willReturn('files/test/target');
		$davNode = $this->createMock(DavFile::class);
		$this->tree->method('getNodeForPath')->willReturn($davNode);

		$file = $this->createMock(File::class);
		$davNode->method('getNode')->willReturn($file);

		$storage = $this->createMock(IStorage::class);
		$file->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with(ISharedStorage::class)->willReturn(false);

		$this->assertTrue($this->plugin->checkViewOnly($this->request));
	}

	public static function providesDataForCanGet(): array {
		return [
			// has attribute permissions-download enabled - can get file
			[false, true, true],
			// has no attribute permissions-download - can get file
			[false, null, true],
			// has attribute permissions-download disabled- cannot get the file
			[false, false, false],
			// has attribute permissions-download enabled - can get file version
			[true, true, true],
			// has no attribute permissions-download - can get file version
			[true, null, true],
			// has attribute permissions-download disabled- cannot get the file version
			[true, false, false],
		];
	}

	/**
	 * @dataProvider providesDataForCanGet
	 */
	public function testCanGet(bool $isVersion, ?bool $attrEnabled, bool $expectCanDownloadFile): void {
		$nodeInfo = $this->createMock(File::class);
		if ($isVersion) {
			$davPath = 'versions/alice/versions/117/123456';
			$version = $this->createMock(IVersion::class);
			$version->expects($this->once())
				->method('getSourceFile')
				->willReturn($nodeInfo);
			$davNode = $this->createMock(VersionFile::class);
			$davNode->expects($this->once())
				->method('getVersion')
				->willReturn($version);

			$currentUser = $this->createMock(IUser::class);
			$currentUser->expects($this->once())
				->method('getUID')
				->willReturn('alice');
			$nodeInfo->expects($this->once())
				->method('getOwner')
				->willReturn($currentUser);

			$nodeInfo = $this->createMock(File::class);
			$owner = $this->createMock(IUser::class);
			$owner->expects($this->once())
				->method('getUID')
				->willReturn('bob');
			$this->userFolder->expects($this->once())
				->method('getById')
				->willReturn([$nodeInfo]);
			$this->userFolder->expects($this->once())
				->method('getOwner')
				->willReturn($owner);
		} else {
			$davPath = 'files/path/to/file.odt';
			$davNode = $this->createMock(DavFile::class);
			$davNode->method('getNode')->willReturn($nodeInfo);
		}

		$this->request->expects($this->once())->method('getPath')->willReturn($davPath);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($davPath)
			->willReturn($davNode);

		$storage = $this->createMock(SharedStorage::class);
		$share = $this->createMock(IShare::class);
		$nodeInfo->expects($this->once())
			->method('getStorage')
			->willReturn($storage);
		$storage->method('instanceOfStorage')->with(ISharedStorage::class)->willReturn(true);
		$storage->method('getShare')->willReturn($share);

		$extAttr = $this->createMock(IAttributes::class);
		$share->method('getAttributes')->willReturn($extAttr);
		$extAttr->expects($this->once())
			->method('getAttribute')
			->with('permissions', 'download')
			->willReturn($attrEnabled);

		if (!$expectCanDownloadFile) {
			$this->expectException(Forbidden::class);
		}
		$this->plugin->checkViewOnly($this->request);
	}
}
