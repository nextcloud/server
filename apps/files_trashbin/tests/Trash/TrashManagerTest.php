<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Tests\Trash;

use OCA\Files_Trashbin\Trash\ITrashBackend;
use OCA\Files_Trashbin\Trash\TrashManager;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TrashManagerTest extends TestCase {

	private TrashManager $manager;
	private IStorage&MockObject $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = new TrashManager();
		$this->storage = $this->createMock(IStorage::class);
		$this->storage->method('instanceOfStorage')
			->with(IStorage::class)
			->willReturn(true);
	}

	private function registerBackend(ITrashBackend $backend): void {
		$this->manager->registerBackend(IStorage::class, $backend);
	}

	public function testMoveToTrashWithoutBackend(): void {
		$this->assertFalse($this->manager->moveToTrash($this->storage, 'files/test.txt'));
	}

	public function testMoveToTrashUsesBackend(): void {
		$backend = $this->createMock(ITrashBackend::class);
		$backend->expects($this->once())
			->method('moveToTrash')
			->with($this->storage, 'files/test.txt')
			->willReturn(true);
		$this->registerBackend($backend);

		$this->assertTrue($this->manager->moveToTrash($this->storage, 'files/test.txt'));
	}

	/**
	 * The backend must not see the delete it performs itself, but the pause has
	 * to be released again afterwards so following deletes still get trashed.
	 */
	public function testMoveToTrashPausesOnlyDuringTheMove(): void {
		$backend = $this->createMock(ITrashBackend::class);
		$backend->expects($this->exactly(2))
			->method('moveToTrash')
			->willReturnCallback(function (): bool {
				$this->assertFalse(
					$this->manager->moveToTrash($this->storage, 'files/nested.txt'),
					'Trash has to be paused while the backend moves a file',
				);
				return true;
			});
		$this->registerBackend($backend);

		$this->assertTrue($this->manager->moveToTrash($this->storage, 'files/first.txt'));
		$this->assertTrue($this->manager->moveToTrash($this->storage, 'files/second.txt'));
	}

	public function testMoveToTrashReleasesPauseOnException(): void {
		$backend = $this->createMock(ITrashBackend::class);
		$backend->method('moveToTrash')
			->willThrowException(new NotFoundException('test not found while trying to get owner'));
		$this->registerBackend($backend);

		$this->expectException(NotFoundException::class);
		try {
			$this->manager->moveToTrash($this->storage, 'files/test.txt');
		} finally {
			$this->assertFalse($this->invokePrivate($this->manager, 'trashPaused'));
		}
	}
}
