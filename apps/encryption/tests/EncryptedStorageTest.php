<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\encryption\tests;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCA\Encryption\KeyManager;
use OCP\Files\Mount\IMountManager;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Server;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

class TemporaryNoEncrypted extends Temporary implements IDisableEncryptionStorage {

}

/**
 * @group DB
 */
class EncryptedStorageTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	public function testMoveFromEncrypted(): void {
		Server::get(KeyManager::class)->validateMasterKey();
		Server::get(KeyManager::class)->validateShareKey();
		$this->createUser('test1', 'test2');
		$this->setupForUser('test1', 'test2');

		$unwrapped = new Temporary();

		$this->registerMount('test1', new TemporaryNoEncrypted(), '/test1/files/unenc');
		$this->registerMount('test1', $unwrapped, '/test1/files/enc');

		$this->loginWithEncryption('test1');

		$view = new View('/test1/files');

		/** @var IMountManager $mountManager */
		$mountManager = Server::get(IMountManager::class);

		$encryptedMount = $mountManager->find('/test1/files/enc');
		$unencryptedMount = $mountManager->find('/test1/files/unenc');
		$encryptedStorage = $encryptedMount->getStorage();
		$unencryptedStorage = $unencryptedMount->getStorage();
		$encryptedCache = $encryptedStorage->getCache();
		$unencryptedCache = $unencryptedStorage->getCache();

		$this->assertTrue($encryptedStorage->instanceOfStorage(Encryption::class));
		$this->assertFalse($unencryptedStorage->instanceOfStorage(Encryption::class));

		$encryptedStorage->file_put_contents('foo.txt', 'bar');
		$this->assertEquals('bar', $encryptedStorage->file_get_contents('foo.txt'));
		$this->assertStringStartsWith('HBEGIN:oc_encryption_module:', $unwrapped->file_get_contents('foo.txt'));

		$this->assertTrue($encryptedCache->get('foo.txt')->isEncrypted());

		$view->rename('enc/foo.txt', 'unenc/foo.txt');

		$this->assertEquals('bar', $unencryptedStorage->file_get_contents('foo.txt'));
		$this->assertFalse($unencryptedCache->get('foo.txt')->isEncrypted());
	}

	/**
	 * The metadata only move between storages sharing an object store must not be taken
	 * for an encrypted source: the ciphertext would stay in the object store while the
	 * cache entry loses its `encrypted` mark.
	 */
	public function testMoveFromEncryptedObjectStore(): void {
		[
			'view' => $view,
			'objectStore' => $objectStore,
			'unencryptedStorage' => $unencryptedStorage,
		] = $this->setUpSharedObjectStoreMounts();

		$view->file_put_contents('enc/foo.txt', 'bar');
		$this->assertEquals('bar', $view->file_get_contents('enc/foo.txt'));

		$view->rename('enc/foo.txt', 'unenc/foo.txt');

		$this->assertEquals('bar', $view->file_get_contents('unenc/foo.txt'));
		$this->assertFalse($unencryptedStorage->getCache()->get('foo.txt')->isEncrypted());
		$this->assertStringStartsNotWith(
			'HBEGIN:',
			$this->readRawObject($objectStore, $unencryptedStorage, 'foo.txt'),
			'the object was moved verbatim and is still encrypted at rest'
		);
		// a move must not leave the source behind, neither on disk nor in the cache
		$this->assertFalse($view->file_exists('enc/foo.txt'), 'the source file still exists after the move');
	}

	/**
	 * Same as above for the copy shortcut, which hands the ciphertext to the object
	 * store's server side copy.
	 */
	public function testCopyFromEncryptedObjectStore(): void {
		[
			'view' => $view,
			'objectStore' => $objectStore,
			'unencryptedStorage' => $unencryptedStorage,
		] = $this->setUpSharedObjectStoreMounts();

		$view->file_put_contents('enc/foo.txt', 'bar');

		$view->copy('enc/foo.txt', 'unenc/foo.txt');

		$this->assertEquals('bar', $view->file_get_contents('enc/foo.txt'));
		$this->assertEquals('bar', $view->file_get_contents('unenc/foo.txt'));
		$this->assertFalse($unencryptedStorage->getCache()->get('foo.txt')->isEncrypted());
		$this->assertStringStartsNotWith(
			'HBEGIN:',
			$this->readRawObject($objectStore, $unencryptedStorage, 'foo.txt'),
			'the object was copied verbatim and is still encrypted at rest'
		);
	}

	/**
	 * A file without the `encrypted` mark holds plain content even on a wrapped storage
	 * (only some paths encrypt, e.g. not uploads/) and must keep the metadata only move.
	 */
	public function testMoveUnencryptedFileFromEncryptionWrappedObjectStore(): void {
		[
			'view' => $view,
			'unencryptedStorage' => $unencryptedStorage,
			'encryptedBackingStorage' => $encryptedBackingStorage,
		] = $this->setUpSharedObjectStoreMounts();

		// bypasses the encryption wrapper: plain content, no `encrypted` mark
		$encryptedBackingStorage->file_put_contents('plain.txt', 'plain content');
		$sourceEntry = $encryptedBackingStorage->getCache()->get('plain.txt');
		$this->assertFalse($sourceEntry->isEncrypted());

		$view->rename('enc/plain.txt', 'unenc/plain.txt');

		$this->assertEquals('plain content', $view->file_get_contents('unenc/plain.txt'));
		$this->assertSame(
			$sourceEntry->getId(),
			$unencryptedStorage->getCache()->get('plain.txt')->getId(),
			'a plain file must keep the metadata only move that preserves the file id'
		);
		$this->assertFalse($view->file_exists('enc/plain.txt'), 'the source file still exists after the move');
	}

	/**
	 * A folder carries no `encrypted` mark of its own while any of its children may be
	 * encrypted, so a folder move must always take the encryption aware path.
	 */
	public function testMoveFolderFromEncryptedObjectStore(): void {
		[
			'view' => $view,
			'objectStore' => $objectStore,
			'unencryptedStorage' => $unencryptedStorage,
		] = $this->setUpSharedObjectStoreMounts();

		$view->mkdir('enc/dir');
		$view->file_put_contents('enc/dir/foo.txt', 'bar');

		$view->rename('enc/dir', 'unenc/dir');

		$this->assertEquals('bar', $view->file_get_contents('unenc/dir/foo.txt'));
		$this->assertFalse($unencryptedStorage->getCache()->get('dir/foo.txt')->isEncrypted());
		$this->assertStringStartsNotWith(
			'HBEGIN:',
			$this->readRawObject($objectStore, $unencryptedStorage, 'dir/foo.txt'),
			'the folder took the metadata only move and left the child encrypted at rest'
		);
		$this->assertFalse($view->file_exists('enc/dir'), 'the source folder still exists after the move');
	}

	/**
	 * Two object store storages backed by the same object store, one mounted with and one
	 * without the encryption wrapper.
	 *
	 * @return array{view: View, objectStore: IObjectStore, unencryptedStorage: ObjectStoreStorage, encryptedBackingStorage: ObjectStoreStorage}
	 */
	private function setUpSharedObjectStoreMounts(): array {
		Server::get(KeyManager::class)->validateMasterKey();
		Server::get(KeyManager::class)->validateShareKey();
		$this->createUser('test1', 'test2');
		$this->setupForUser('test1', 'test2');

		// a shared object store instance makes the storage ids match, enabling the shortcuts
		$objectStore = new StorageObjectStore(new Temporary());
		$encrypted = new ObjectStoreStorage(['objectstore' => $objectStore, 'storageid' => 'test-enc']);
		$unencrypted = new ObjectStoreNoEncrypted(['objectstore' => $objectStore, 'storageid' => 'test-unenc']);

		$this->registerMount('test1', $encrypted, '/test1/files/enc');
		$this->registerMount('test1', $unencrypted, '/test1/files/unenc');

		$this->loginWithEncryption('test1');

		return [
			'view' => new View('/test1/files'),
			'objectStore' => $objectStore,
			'unencryptedStorage' => $unencrypted,
			'encryptedBackingStorage' => $encrypted,
		];
	}

	private function readRawObject(IObjectStore $objectStore, ObjectStoreStorage $storage, string $path): string {
		$fileId = $storage->getCache()->get($path)->getId();
		$handle = $objectStore->readObject($storage->getURN($fileId));
		$content = stream_get_contents($handle);
		fclose($handle);

		return $content;
	}
}
