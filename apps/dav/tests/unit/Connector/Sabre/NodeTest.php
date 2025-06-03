<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Files\FileInfo;
use OC\Files\Mount\MountPoint;
use OC\Files\Node\Folder;
use OC\Files\View;
use OC\Share20\ShareAttributes;
use OCA\DAV\Connector\Sabre\File;
use OCA\Files_Sharing\SharedMount;
use OCA\Files_Sharing\SharedStorage;
use OCP\Constants;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\ICache;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class NodeTest
 *
 * @group DB
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class NodeTest extends \Test\TestCase {
	public static function davPermissionsProvider(): array {
		return [
			[Constants::PERMISSION_ALL, 'file', false, Constants::PERMISSION_ALL, false, 'test', 'RGDNVW'],
			[Constants::PERMISSION_ALL, 'dir', false, Constants::PERMISSION_ALL, false, 'test', 'RGDNVCK'],
			[Constants::PERMISSION_ALL, 'file', true, Constants::PERMISSION_ALL, false, 'test', 'SRGDNVW'],
			[Constants::PERMISSION_ALL, 'file', true, Constants::PERMISSION_ALL, true, 'test', 'SRMGDNVW'],
			[Constants::PERMISSION_ALL, 'file', true, Constants::PERMISSION_ALL, true, '' , 'SRMGDNVW'],
			[Constants::PERMISSION_ALL, 'file', true, Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE, true, '' , 'SRMGDNV'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_SHARE, 'file', true, Constants::PERMISSION_ALL, false, 'test', 'SGDNVW'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE, 'file', false, Constants::PERMISSION_ALL, false, 'test', 'RGD'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE, 'file', false, Constants::PERMISSION_ALL, false, 'test', 'RGNVW'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE, 'file', false, Constants::PERMISSION_ALL, false, 'test', 'RGDNVW'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_READ, 'file', false, Constants::PERMISSION_ALL, false, 'test', 'RDNVW'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE, 'dir', false, Constants::PERMISSION_ALL, false, 'test', 'RGDNV'],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_READ, 'dir', false, Constants::PERMISSION_ALL, false, 'test', 'RDNVCK'],
		];
	}

	/**
	 * @dataProvider davPermissionsProvider
	 */
	public function testDavPermissions(int $permissions, string $type, bool $shared, int $shareRootPermissions, bool $mounted, string $internalPath, string $expected): void {
		$info = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()
			->onlyMethods(['getPermissions', 'isShared', 'isMounted', 'getType', 'getInternalPath', 'getStorage', 'getMountPoint'])
			->getMock();
		$info->method('getPermissions')
			->willReturn($permissions);
		$info->method('isShared')
			->willReturn($shared);
		$info->method('isMounted')
			->willReturn($mounted);
		$info->method('getType')
			->willReturn($type);
		$info->method('getInternalPath')
			->willReturn($internalPath);
		$info->method('getMountPoint')
			->willReturnCallback(function () use ($shared) {
				if ($shared) {
					return $this->createMock(SharedMount::class);
				} else {
					return $this->createMock(MountPoint::class);
				}
			});
		$storage = $this->createMock(IStorage::class);
		if ($shared) {
			$storage->method('instanceOfStorage')
				->willReturn(true);
			$cache = $this->createMock(ICache::class);
			$storage->method('getCache')
				->willReturn($cache);
			$shareRootEntry = $this->createMock(ICacheEntry::class);
			$cache->method('get')
				->willReturn($shareRootEntry);
			$shareRootEntry->method('getPermissions')
				->willReturn($shareRootPermissions);
		} else {
			$storage->method('instanceOfStorage')
				->willReturn(false);
		}
		$info->method('getStorage')
			->willReturn($storage);
		$view = $this->createMock(View::class);

		$node = new  File($view, $info);
		$this->assertEquals($expected, $node->getDavPermissions());
	}

	public static function sharePermissionsProvider(): array {
		return [
			[\OCP\Files\FileInfo::TYPE_FILE, null, 1, 1],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 3, 3],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 5, 1],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 7, 3],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 9, 1],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 11, 3],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 13, 1],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 15, 3],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 17, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 19, 19],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 21, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 23, 19],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 25, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 27, 19],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 29, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 30, 18],
			[\OCP\Files\FileInfo::TYPE_FILE, null, 31, 19],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 1, 1],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 3, 3],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 5, 5],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 7, 7],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 9, 9],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 11, 11],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 13, 13],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 15, 15],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 17, 17],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 19, 19],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 21, 21],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 23, 23],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 25, 25],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 27, 27],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 29, 29],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 30, 30],
			[\OCP\Files\FileInfo::TYPE_FOLDER, null, 31, 31],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 'shareToken', 7, 7],
		];
	}

	/**
	 * @dataProvider sharePermissionsProvider
	 */
	public function testSharePermissions(string $type, ?string $user, int $permissions, int $expected): void {
		$storage = $this->createMock(IStorage::class);
		$storage->method('getPermissions')->willReturn($permissions);

		$mountpoint = $this->createMock(IMountPoint::class);
		$mountpoint->method('getMountPoint')->willReturn('myPath');
		$shareManager = $this->createMock(IManager::class);
		$share = $this->createMock(IShare::class);

		if ($user === null) {
			$shareManager->expects($this->never())->method('getShareByToken');
			$share->expects($this->never())->method('getPermissions');
		} else {
			$shareManager->expects($this->once())->method('getShareByToken')->with($user)
				->willReturn($share);
			$share->expects($this->once())->method('getPermissions')->willReturn($permissions);
		}

		$info = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()
			->onlyMethods(['getStorage', 'getType', 'getMountPoint', 'getPermissions'])
			->getMock();

		$info->method('getStorage')->willReturn($storage);
		$info->method('getType')->willReturn($type);
		$info->method('getMountPoint')->willReturn($mountpoint);
		$info->method('getPermissions')->willReturn($permissions);

		$view = $this->createMock(View::class);

		$node = new File($view, $info);
		$this->invokePrivate($node, 'shareManager', [$shareManager]);
		$this->assertEquals($expected, $node->getSharePermissions($user));
	}

	public function testShareAttributes(): void {
		$storage = $this->getMockBuilder(SharedStorage::class)
			->disableOriginalConstructor()
			->onlyMethods(['getShare'])
			->getMock();

		$shareManager = $this->createMock(IManager::class);
		$share = $this->createMock(IShare::class);

		$storage->expects($this->once())
			->method('getShare')
			->willReturn($share);

		$attributes = new ShareAttributes();
		$attributes->setAttribute('permissions', 'download', false);

		$share->expects($this->once())->method('getAttributes')->willReturn($attributes);

		/** @var Folder&MockObject $info */
		$info = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->onlyMethods(['getStorage', 'getType'])
			->getMock();

		$info->method('getStorage')->willReturn($storage);
		$info->method('getType')->willReturn(FileInfo::TYPE_FOLDER);

		/** @var View&MockObject $view */
		$view = $this->createMock(View::class);

		$node = new File($view, $info);
		$this->invokePrivate($node, 'shareManager', [$shareManager]);
		$this->assertEquals($attributes->toArray(), $node->getShareAttributes());
	}

	public function testShareAttributesNonShare(): void {
		$storage = $this->createMock(IStorage::class);
		$shareManager = $this->createMock(IManager::class);

		/** @var Folder&MockObject */
		$info = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->onlyMethods(['getStorage', 'getType'])
			->getMock();

		$info->method('getStorage')->willReturn($storage);
		$info->method('getType')->willReturn(FileInfo::TYPE_FOLDER);

		/** @var View&MockObject */
		$view = $this->createMock(View::class);

		$node = new File($view, $info);
		$this->invokePrivate($node, 'shareManager', [$shareManager]);
		$this->assertEquals([], $node->getShareAttributes());
	}

	public static function sanitizeMtimeProvider(): array {
		return [
			[123456789, 123456789],
			['987654321', 987654321],
		];
	}

	/**
	 * @dataProvider sanitizeMtimeProvider
	 */
	public function testSanitizeMtime(string|int $mtime, int $expected): void {
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();
		$info = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()
			->getMock();

		$node = new File($view, $info);
		$result = $this->invokePrivate($node, 'sanitizeMtime', [$mtime]);
		$this->assertEquals($expected, $result);
	}

	public static function invalidSanitizeMtimeProvider(): array {
		return [
			[-1337], [0], ['abcdef'], ['-1337'], ['0'], [12321], [24 * 60 * 60 - 1],
		];
	}

	/**
	 * @dataProvider invalidSanitizeMtimeProvider
	 */
	public function testInvalidSanitizeMtime(int|string $mtime): void {
		$this->expectException(\InvalidArgumentException::class);

		$view = $this->createMock(View::class);
		$info = $this->createMock(FileInfo::class);

		$node = new File($view, $info);
		self::invokePrivate($node, 'sanitizeMtime', [$mtime]);
	}
}
