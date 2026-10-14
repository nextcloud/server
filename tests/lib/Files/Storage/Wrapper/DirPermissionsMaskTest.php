<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage\Wrapper;

use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\DirPermissionsMask;
use OCP\Constants;
use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class DirPermissionsMaskTest extends \Test\Files\Storage\Storage {
	// Same mask path the public share wrapper uses for home storages (publicremote.php)
	private const MASKED_PATH = 'files';

	private Temporary $sourceStorage;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->sourceStorage = new Temporary([]);
		$this->instance = $this->getMaskedStorage(Constants::PERMISSION_ALL);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	protected function getMaskedStorage(int $mask): DirPermissionsMask {
		return new DirPermissionsMask([
			'storage' => $this->sourceStorage,
			'mask' => $mask,
			'path' => self::MASKED_PATH,
		]);
	}

	public static function dataRenamePartFile(): array {
		// Upload-only public link: no UPDATE, no DELETE
		$uploadOnly = Constants::PERMISSION_READ | Constants::PERMISSION_CREATE;
		$partialFileName = 'x.ocTransferId123.part';
		return [
			'partial file in same directory' => [
				'mask' => $uploadOnly,
				'sourcePath' => 'files/drop/' . $partialFileName,
				'targetPath' => 'files/drop/x',
				'shouldSucceed' => true,
			],
			'partial file in share root, target nested (part_file_in_storage=false)' => [
				'mask' => $uploadOnly,
				'sourcePath' => 'files/drop/' . $partialFileName,
				'targetPath' => 'files/drop/a/b/x',
				'shouldSucceed' => true,
			],
			'partial file onto existing target without update permission' => [
				'mask' => $uploadOnly,
				'sourcePath' => 'files/drop/' . $partialFileName,
				'targetPath' => 'files/drop/a/x',
				'shouldSucceed' => false,
				'targetExisting' => true,
			],
			'partial file onto existing target with update permission' => [
				'mask' => $uploadOnly | Constants::PERMISSION_UPDATE,
				'sourcePath' => 'files/drop/' . $partialFileName,
				'targetPath' => 'files/drop/a/x',
				'shouldSucceed' => true,
				'targetExisting' => true,
			],
			'partial file without create permission' => [
				'mask' => Constants::PERMISSION_READ,
				'sourcePath' => 'files/drop/' . $partialFileName,
				'targetPath' => 'files/drop/x',
				'shouldSucceed' => false,
			],
			'transfer id without .part extension' => [
				'mask' => $uploadOnly,
				'sourcePath' => 'files/drop/foo.ocTransferId123',
				'targetPath' => 'files/drop/a/x',
				'shouldSucceed' => false,
			],
			'regular file without update or delete permission' => [
				'mask' => $uploadOnly,
				'sourcePath' => 'files/drop/foo',
				'targetPath' => 'files/drop/x',
				'shouldSucceed' => false,
			],
			'outside masked path the mask does not apply' => [
				'mask' => Constants::PERMISSION_READ,
				'sourcePath' => 'cache/foo',
				'targetPath' => 'cache/bar',
				'shouldSucceed' => true,
			],
		];
	}

	#[DataProvider(methodName: 'dataRenamePartFile')]
	public function testRenamePartFile(int $mask, string $sourcePath, string $targetPath, bool $shouldSucceed, bool $targetExisting = false): void {
		// Set up the tree on the unmasked storage so the mask doesn't interfere with the fixtures
		foreach ([dirname($sourcePath), dirname($targetPath)] as $dir) {
			$path = '';
			foreach (explode('/', $dir) as $part) {
				$path .= $part . '/';
				if (!$this->sourceStorage->is_dir($path)) {
					$this->assertTrue($this->sourceStorage->mkdir($path));
				}
			}
		}
		$this->assertNotFalse($this->sourceStorage->file_put_contents($sourcePath, 'new'));
		if ($targetExisting) {
			$this->assertNotFalse($this->sourceStorage->file_put_contents($targetPath, 'existing'));
		}

		$storage = $this->getMaskedStorage($mask);

		$this->assertSame($shouldSucceed, $storage->rename($sourcePath, $targetPath), 'Renaming should ' . ($shouldSucceed ? '' : 'not ') . 'succeed');
		if ($shouldSucceed) {
			$this->assertFalse($this->sourceStorage->file_exists($sourcePath));
			$this->assertSame('new', $this->sourceStorage->file_get_contents($targetPath));
		} else {
			$this->assertTrue($this->sourceStorage->file_exists($sourcePath));
			if ($targetExisting) {
				$this->assertSame('existing', $this->sourceStorage->file_get_contents($targetPath));
				if ($shouldSucceed) {
					$this->assertFalse($this->sourceStorage->file_exists($sourcePath));
					$this->assertSame('new', $this->sourceStorage->file_get_contents($targetPath));
				} else {
					$this->assertTrue($this->sourceStorage->file_exists($sourcePath));
					if ($targetExisting) {
						$this->assertSame('existing', $this->sourceStorage->file_get_contents($targetPath));
					}
				}
			}
		}
	}
}
