<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OCP\Files\GenericFileException;
use OCP\Files\ObjectStore\IObjectStore;
use Test\TestCase;

/**
 * Verifies that {@see \OC\Files\ObjectStore\ObjectStoreStorage::writeStream()}
 * uses conditional writes only when creating a file on a store that supports
 * them, and that a refused write neither loses data nor leaves cache entries.
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class ObjectStoreStorageConditionalWriteTest extends TestCase {
	private ConditionalWriteObjectStore $objectStore;
	private ObjectStoreStorageOverwrite $storage;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->objectStore = new ConditionalWriteObjectStore(new StorageObjectStore(new Temporary()));
		$this->storage = new ObjectStoreStorageOverwrite(['objectstore' => $this->objectStore]);
	}

	#[\Override]
	protected function tearDown(): void {
		if (isset($this->storage)) {
			$this->storage->getCache()->clear();
		}
		parent::tearDown();
	}

	public function testNewFileUsesConditionalWrite(): void {
		$this->storage->file_put_contents('/new.txt', 'hello');

		self::assertSame(['writeObjectIfNotExists'], $this->objectStore->writeCalls);
		self::assertSame('hello', $this->storage->file_get_contents('/new.txt'));
	}

	public function testOverwriteUsesUnconditionalWrite(): void {
		$this->storage->file_put_contents('/file.txt', 'first');
		$this->objectStore->writeCalls = [];

		$this->storage->file_put_contents('/file.txt', 'second');

		self::assertSame(['writeObject'], $this->objectStore->writeCalls);
		self::assertSame('second', $this->storage->file_get_contents('/file.txt'));
	}

	public function testUnsupportedStoreUsesUnconditionalWrite(): void {
		$this->objectStore->setSupported(false);

		$this->storage->file_put_contents('/new.txt', 'hello');

		self::assertSame(['writeObject'], $this->objectStore->writeCalls);
		self::assertSame('hello', $this->storage->file_get_contents('/new.txt'));
	}

	public function testExistingObjectOnCreateIsRefusedWithoutDataLoss(): void {
		// Simulate the file cache and bucket being out of sync: an object already
		// occupies the urn that the newly created file will target.
		$this->objectStore->setSimulateExistingObject(true);

		$threw = false;
		try {
			$this->storage->file_put_contents('/ghost.txt', 'payload');
		} catch (GenericFileException) {
			$threw = true;
		}

		self::assertTrue($threw, 'A create onto an already occupied urn must fail');
		self::assertSame(['writeObjectIfNotExists'], $this->objectStore->writeCalls);
		// The failed create must not leave the file (or a stray .part entry) behind.
		self::assertFalse($this->storage->file_exists('/ghost.txt'));
		self::assertFalse($this->storage->getCache()->inCache('ghost.txt'));
		self::assertFalse($this->storage->getCache()->inCache('ghost.txt.part'));
	}

	public function testStoreWithoutConditionalInterfaceUsesUnconditionalWrite(): void {
		// A backend that does not advertise IObjectStoreConditionalWrite must keep the
		// previous behaviour: a plain write, no conditional dispatch.
		$recorder = new class(new StorageObjectStore(new Temporary())) implements IObjectStore {
			/** @var list<string> */
			public array $writeCalls = [];

			public function __construct(
				private IObjectStore $wrapped,
			) {
			}

			#[\Override]
			public function getStorageId() {
				return $this->wrapped->getStorageId();
			}

			#[\Override]
			public function readObject($urn) {
				return $this->wrapped->readObject($urn);
			}

			#[\Override]
			public function writeObject($urn, $stream, ?string $mimetype = null) {
				$this->writeCalls[] = 'writeObject';
				$this->wrapped->writeObject($urn, $stream, $mimetype);
			}

			#[\Override]
			public function deleteObject($urn) {
				$this->wrapped->deleteObject($urn);
			}

			#[\Override]
			public function objectExists($urn) {
				return $this->wrapped->objectExists($urn);
			}

			#[\Override]
			public function copyObject($from, $to) {
				$this->wrapped->copyObject($from, $to);
			}

			#[\Override]
			public function preSignedUrl(string $urn, \DateTimeInterface $expiration): ?string {
				return null;
			}
		};

		$storage = new ObjectStoreStorageOverwrite(['objectstore' => $recorder]);
		try {
			$storage->file_put_contents('/plain.txt', 'hello');

			self::assertSame(['writeObject'], $recorder->writeCalls);
			self::assertSame('hello', $storage->file_get_contents('/plain.txt'));
		} finally {
			$storage->getCache()->clear();
		}
	}
}
