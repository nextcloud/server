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

#[\PHPUnit\Framework\Attributes\Group('DB')]
class DirPermissionsMaskTest extends \Test\TestCase {
	// Same mask path the public share wrapper uses for home storages (publicremote.php)
	private const MASKED_PATH = 'files';

	private Temporary $sourceStorage;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->sourceStorage = new Temporary([]);
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

	public function testCopyFromStorageOnlyMasksTheDirectory(): void {
		$otherStorage = new Temporary([]);
		$otherStorage->file_put_contents('foo', 'bar');
		$this->sourceStorage->mkdir(self::MASKED_PATH);
		$storage = $this->getMaskedStorage(Constants::PERMISSION_READ);

		$this->assertFalse($storage->copyFromStorage($otherStorage, 'foo', self::MASKED_PATH . '/foo'));
		$this->assertTrue($storage->copyFromStorage($otherStorage, 'foo', 'foo'));
		$otherStorage->cleanUp();
	}
}
