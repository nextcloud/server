<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files;

use OC\Files\FileInfo;
use OC\Files\Mount\HomeMountPoint;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Home;
use OC\Files\Storage\Temporary;
use OCP\IConfig;
use OCP\IUser;
use Test\TestCase;
use Test\Traits\UserTrait;

class FileInfoTest extends TestCase {
	use UserTrait;

	private $config;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->createUser('foo', 'foo');
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
	}

	private function makeFileInfo(array $data): FileInfo {
		$storage = new Temporary();
		return new FileInfo('', $storage, '', $data, new MountPoint($storage, '/foo/files'));
	}

	public function testGetSizeEncryptedZeroByte(): void {
		$info = $this->makeFileInfo(['encrypted' => true, 'size' => 8192, 'unencrypted_size' => 0]);
		// Both paths must report the true plaintext size (0), not the on-disk encrypted size (8192)
		$this->assertSame(0, $info->getSize(true));
		$this->assertSame(0, $info->getSize(false));
	}

	public function testGetSizeEncryptedNonZero(): void {
		$info = $this->makeFileInfo(['encrypted' => true, 'size' => 16384, 'unencrypted_size' => 5000]);
		$this->assertSame(5000, $info->getSize(true));
		$this->assertSame(5000, $info->getSize(false));
	}

	public function testGetSizeNonEncrypted(): void {
		$info = $this->makeFileInfo(['encrypted' => false, 'size' => 100]);
		$this->assertSame(100, $info->getSize(true));
		$this->assertSame(100, $info->getSize(false));
	}

	public function testIsMountedHomeStorage(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('foo');
		$user->method('getHome')
			->willReturn('foo');
		$storage = new Home(['user' => $user]);

		$fileInfo = new FileInfo(
			'',
			$storage,
			'',
			[],
			new HomeMountPoint($user, $storage, '/foo/files')
		);
		$this->assertFalse($fileInfo->isMounted());
	}

	public function testIsMountedNonHomeStorage(): void {
		$storage = new Temporary();
		$fileInfo = new FileInfo(
			'',
			$storage,
			'',
			[],
			new MountPoint($storage, '/foo/files/bar')
		);
		$this->assertTrue($fileInfo->isMounted());
	}
}
