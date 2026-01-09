<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\encryption\tests;

use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Server;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

class TemporaryNoEncrypted extends Temporary implements IDisableEncryptionStorage {

}

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class EncryptedStorageTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	public function testMoveFromEncrypted(): void {
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
}
